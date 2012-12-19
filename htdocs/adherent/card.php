<?PHP
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2011	Laurent Destailleur		<eldy@uers.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis@dolibarr.fr>
 * Copyright (C) 2010-2011	Patrick Mary			<laube@hotmail.fr>
 * Copyright (C) 2011-2012	Herve Prot				<herve.prot@symeos.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php');
require_once(DOL_DOCUMENT_ROOT . "/core/class/CMailFile.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/adherent/class/adherent_card.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formother.class.php");

$langs->load("members");
$langs->load("mails");

if (!$user->rights->adherent->configurer)
    accessforbidden();

$id = "licenseCard";
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$message = '';

$object = new AdherentCard($db);

// Action update emailing
if ($action == 'update' && empty($_POST["removedfile"]) && empty($_POST["cancel"])) {
    require_once(DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php");

    try {
        $object->load($id);

        $object->title = trim($_POST["title"]);
        $object->resume = trim($_POST["resume"]);
        $object->body = trim($_POST["body"]);
        $object->tms = dol_now();

        if (!$object->title)
            $message.=($message ? '<br>' : '') . $langs->trans("ErrorFieldRequired", $langs->trans("MailTopic"));
        if (!$object->body)
            $message.=($message ? '<br>' : '') . $langs->trans("ErrorFieldRequired", $langs->trans("MailBody"));

        if (!$message) {
            $object->record();
            Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id());
            exit;
        }
    } catch (Exception $e) {
        $message = "Something weird happened: " . $e->getMessage() . " (errcode=" . $e->getCode() . ")\n";
        dol_syslog("Ego::Update " . $message, LOG_ERR);
        $action = "edit";
    }

    $message = '<div class="error">' . $message . '</div>';
    $action = "edit";
}

if (!empty($_POST["cancel"])) {
    $action = '';
}



/*
 * View
 */

$help_url = 'EN:Module_EMailing|FR:Module_Mailing|ES:M&oacute;dulo_Mailing';
llxHeader('', $langs->trans("Messaging"), $help_url);

$form = new Form($db);
$htmlother = new FormOther($db);

try {
    $object->load($id);
} catch (Exception $e) {
    $message = "Something weird happened: " . $e->getMessage() . " (errcode=" . $e->getCode() . ")\n";
    dol_syslog("Ego::Create " . $message, LOG_ERR);
    print '<div class="error">' . $message . '</div>';
    exit;
}

dol_htmloutput_mesg($message);

if ($action != 'edit') {
    /*
     * Mailing en mode visu
     */

    // Print mail content
    $titre = $langs->trans("CardMember");

    print_fiche_titre($titre);
    print '<div class="with-padding">';
    print '<div class="columns">';

    print start_box($titre, "twelve", "16-iPhone-4.png");
    print '<table class="border" width="100%">';

    // Subject
    print '<tr><td width="25%">' . $langs->trans("CardTitle") . '</td><td colspan="3">' . $object->title . '</td></tr>';

    // Resume
    print '<tr><td width="25%">' . $langs->trans("Year") . '</td><td colspan="3">' . $object->resume . '</td></tr>';

    print '</table></br>';

    // Message
    ?>
    <div class="columns">
        <div class="twelve-columns">
            <div class="six-columns centered-columns">
                <?php
                print $object->body;
                ?>
            </div>
        </div>
    </div>
    <?php
    /*
     * Boutons d'action
     */

    if (GETPOST("cancel") || $confirm == 'no' || $action == '' || in_array($action, array('valid', 'delete', 'sendall'))) {
        print "\n\n<div class=\"tabsAction\">\n";

        print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=edit&amp;id=' . $object->id() . '">' . $langs->trans("Edit") . '</a>';

        print '<br><br></div>';
    }

    print end_box();

    print '</div></div>';
} else {
    /*
     * Mailing en mode edition
     */

    $titre = $langs->trans("CardMember");
    print_fiche_titre($titre);
    print '<div class="with-padding">';
    print '<div class="columns">';

    print start_box($titre, "twelve", "16-iPhone-4.png", false);

    print "\n";
    print '<form name="edit_mailing" action="' . $_SERVER['PHP_SELF'] . '" method="post" enctype="multipart/form-data">' . "\n";
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="' . $object->id . '">';

    // Print mail content
    //print_fiche_titre($langs->trans("Message"), '', '');
    print '<table class="border" width="100%">';

    // Subject
    print '<tr><td width="25%" class="fieldrequired">' . $langs->trans("CardTitle") . '</td><td colspan="3"><input class="flat" type="text" size=40 name="title" value="' . $object->title . '"></td></tr>';

    // Resume
    print '<tr><td width="25%" class="fieldrequired">' . $langs->trans("Year") . '</td><td colspan="3"><input class="flat" type="text" size=40 name="resume" value="' . $object->resume . '"></td></tr>';

    // Message
    print '<tr><td width="25%" valign="top">' . $langs->trans("Card") . '<br>';
    print '<br><i>' . $langs->trans("CommonSubstitutions") . ':<br>';
    print '__ID__ = ' . $langs->trans("IdLicense") . '<br>';
    print '__STATUS__ = ' . $langs->trans("Status") . '<br>';
    print '__EMAIL__ = ' . $langs->trans("EMail") . '<br>';
    print '__LASTNAME__ = ' . $langs->trans("Lastname") . '<br>';
    print '__FIRSTNAME__ = ' . $langs->trans("Firstname") . '<br>';
    print '__PHOTO__ = ' . $langs->trans("Photo") . '<br>';
    print '</i></td>';
    print '<td colspan="3">';
    // Editeur wysiwyg
    require_once(DOL_DOCUMENT_ROOT . "/core/class/doleditor.class.php");
    $doleditor = new DolEditor('body', $object->body, '', 320, 'dolibarr_mailings', '', true, true, true, 20, 70);
    $doleditor->Create();
    print '</td></tr>';

    print '<tr><td colspan="4" align="center">';
    print '<input type="submit" class="button small nice" value="' . $langs->trans("Save") . '" name="save">';
    print ' &nbsp; ';
    print '<input type="submit" class="button small nice black" value="' . $langs->trans("Cancel") . '" name="cancel">';
    print '</td></tr>';

    print '</table>';

    print '</form>';

    print end_box();
    print '</div></div>';
}

llxFooter();
?>
