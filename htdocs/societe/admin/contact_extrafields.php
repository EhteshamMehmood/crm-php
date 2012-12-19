<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis@dolibarr.fr>
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

/**
 *      \file       htdocs/societe/admin/contact_extrafields.php
 *		\ingroup    societe
 *		\brief      Page to setup extra fields of contact
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/lib/societe.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

$langs->load("companies");
$langs->load("admin");

$extrafields = new ExtraFields($db);
$form = new Form($db);

// List of supported format
$tmptype2label=getStaticMember(get_class($extrafields),'type2label');
$type2label=array('');
foreach ($tmptype2label as $key => $val) $type2label[$key]=$langs->trans($val);

$action=GETPOST('action', 'alpha');
$attrname=GETPOST('attrname', 'alpha');
$elementtype='contact';

if (!$user->admin) accessforbidden();


/*
 * Actions
 */

require DOL_DOCUMENT_ROOT.'/core/admin_extrafields.inc.php';

/*
 * View
 */

$textobject=$langs->transnoentitiesnoconv("ContactsAddresses");

$help_url='EN:Module Third Parties setup|FR:Paramétrage_du_module_Tiers';
llxHeader('',$langs->trans("CompanySetup"),$help_url);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("CompanySetup"),$linkback,'setup');


$head = societe_admin_prepare_head(null);

dol_fiche_head($head, 'attributes_contacts', $langs->trans("ThirdParties"), 0, 'company');


print $langs->trans("DefineHereComplementaryAttributes",$textobject).'<br>'."\n";
print '<br>';

dol_htmloutput_errors($mesg);

// Load attribute_label
$extrafields->fetch_name_optionals_label($elementtype);

print "<table summary=\"listofattributes\" class=\"noborder\" width=\"100%\">";

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Label").'</td>';
print '<td>'.$langs->trans("AttributeCode").'</td>';
print '<td>'.$langs->trans("Type").'</td>';
print '<td align="right">'.$langs->trans("Size").'</td>';
print '<td align="right">'.$langs->trans("Unique").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

$var=True;
foreach($extrafields->attribute_type as $key => $value)
{
    $var=!$var;
    print "<tr ".$bc[$var].">";
    print "<td>".$extrafields->attribute_label[$key]."</td>\n";
    print "<td>".$key."</td>\n";
    print "<td>".$type2label[$extrafields->attribute_type[$key]]."</td>\n";
    print '<td align="right">'.$extrafields->attribute_size[$key]."</td>\n";
    print '<td align="right">'.yn($extrafields->attribute_unique[$key])."</td>\n";
    print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=edit&attrname='.$key.'">'.img_edit().'</a>';
    print "&nbsp; <a href=\"".$_SERVER["PHP_SELF"]."?action=delete&attrname=$key\">".img_delete()."</a></td>\n";
    print "</tr>";
    //      $i++;
}

print "</table>";

dol_fiche_end();


// Buttons
if ($action != 'create' && $action != 'edit')
{
    print '<div class="tabsAction">';
    print "<a class=\"butAction\" href=\"".$_SERVER["PHP_SELF"]."?action=create\">".$langs->trans("NewAttribute")."</a>";
    print "</div>";
}


/* ************************************************************************** */
/*                                                                            */
/* Creation d'un champ optionnel
 /*                                                                            */
/* ************************************************************************** */

if ($action == 'create')
{
    print "<br>";
    print_titre($langs->trans('NewAttribute'));

    require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_add.tpl.php';
}

/* ************************************************************************** */
/*                                                                            */
/* Edition d'un champ optionnel                                               */
/*                                                                            */
/* ************************************************************************** */
if ($action == 'edit' && ! empty($attrname))
{
    print "<br>";
    print_titre($langs->trans("FieldEdition", $attrname));

    /*
     * formulaire d'edition
     */
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?attrname='.$attrname.'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="attrname" value="'.$attrname.'">';
    print '<input type="hidden" name="action" value="update">';
    print '<table summary="listofattributes" class="border" width="100%">';

    // Label
    print '<tr>';
    print '<td class="fieldrequired" required>'.$langs->trans("Label").'</td><td class="valeur"><input type="text" name="label" size="40" value="'.$extrafields->attribute_label[$attrname].'"></td>';
    print '</tr>';
    // Code
    print '<tr>';
    print '<td class="fieldrequired" required>'.$langs->trans("AttributeCode").'</td>';
    print '<td class="valeur">'.$attrname.'&nbsp;</td>';
    print '</tr>';
    // Type
    $type=$extrafields->attribute_type[$attrname];
    $size=$extrafields->attribute_size[$attrname];
    print '<tr><td class="fieldrequired" required>'.$langs->trans("Type").'</td>';
    print '<td class="valeur">';
    print $type2label[$type];
    print '<input type="hidden" name="type" value="'.$type.'">';
    print '</td></tr>';
    // Size
    print '<tr><td class="fieldrequired" required>'.$langs->trans("Size").'</td><td class="valeur"><input type="text" name="size" size="5" value="'.$size.'"></td></tr>';

    print '</table>';

    print '<center><br><input type="submit" name="button" class="button" value="'.$langs->trans("Save").'"> &nbsp; ';
    print '<input type="submit" name="button" class="button" value="'.$langs->trans("Cancel").'"></center>';

    print "</form>";

}

llxFooter();

$db->close();
?>
