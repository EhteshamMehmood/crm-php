<?php

/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012	   Regis Houssin
 * Copyright (C) 2012-2012 Herve Prot           <herve.prot@symeos.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/societe/admin/societe_extrafields.php
 * 		\ingroup    societe
 * 		\brief      Page to setup extra fields of third party
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/societe/lib/societe.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

$langs->load("companies");
$langs->load("admin");

$extrafields = new ExtraFields($db);
$form = new Form($db);

// List of supported format
$tmptype2label = getStaticMember(get_class($extrafields), 'type2label');
$type2label = array('');
foreach ($tmptype2label as $key => $val)
    $type2label[$key] = $langs->trans($val);

$action = GETPOST('action', 'alpha');
$attrname = GETPOST('attrname', 'alpha');
$elementtype = 'company';

if (!$user->admin)
    accessforbidden();

$acts[0] = "activate";
$acts[1] = "disable";
$actl[0] = img_picto($langs->trans("Disabled"), 'switch_off');
$actl[1] = img_picto($langs->trans("Activated"), 'switch_on');

/*
 * Actions
 */

require DOL_DOCUMENT_ROOT . '/core/admin_extrafields.inc.php';

/*
 * View
 */

$textobject = $langs->transnoentitiesnoconv("ThirdParty");

$help_url = 'EN:Module Third Parties setup|FR:Paramétrage_du_module_Tiers';
llxHeader('', $langs->trans("CompanySetup"), $help_url);


$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';

print_fiche_titre($langs->trans("CompanySetup"), $linkback, 'setup');
print '<div class="with-padding">';
print '<div class="columns">';

$object = new Societe($db);

print start_box($langs->trans($langs->trans("CompanySetup")), "twelve", '16-Alert-2.png', false);

$head = societe_admin_prepare_head(null);

dol_fiche_head($head, 'attributes', $langs->trans("ThirdParties"), 0, 'company');


print $langs->trans("DefineHereComplementaryAttributes", $textobject) . '<br>' . "\n";
print '<br>';

dol_htmloutput_errors($mesg);

/* * ************************************************************************* */
/*                                                                            */
/* Creation d'un champ optionnel
  /* */
/* * ************************************************************************* */

if ($action == 'create') {
    print "<br>";
    print_titre($langs->trans('NewAttribute'));

    require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_add.tpl.php';
}

/* * ************************************************************************* */
/*                                                                            */
/* Edition d'un champ optionnel                                               */
/*                                                                            */
/* * ************************************************************************* */
if ($action == 'edit' && !empty($attrname)) {
    print "<br>";
    print_titre($langs->trans("FieldEdition", $attrname));

    require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_edit.tpl.php';
}

dol_fiche_end();

/*
 * Barre d'actions
 *
 */
if ($action != 'create' && $action != 'edit') {
    print '<p class="button-height right">';
    print '<span class="button-group">';
    print '<a class="button compact" href=' . $_SERVER["PHP_SELF"] . '?action=create&fields=' . $key . ' ><span class="button-icon blue-gradient glossy"><span class="icon-star"></span></span>' . $langs->trans("NewAttribute") . '</a>';
    print "</span>";
    print "</p>";
}

$i = 0;
$obj = new stdClass();
//print '<div class="datatable">';
print '<table class="display dt_act" id="list_fields" >';
// Ligne des titres 
print'<thead>';
print'<tr>';
print'<th>';
print'</th>';
$obj->aoColumns[$i]->mDataProp = "_id";
$obj->aoColumns[$i]->bUseRendered = false;
$obj->aoColumns[$i]->bSearchable = false;
$obj->aoColumns[$i]->bVisible = false;
$i++;
print'<th class="essential">';
print $langs->trans("Position");
print'</th>';
$obj->aoColumns[$i]->mDataProp = "order";
$obj->aoColumns[$i]->bSearchable = false;
$obj->aoColumns[$i]->sDefaultContent = "";
$i++;
print'<th class="essential">';
print $langs->trans("Label");
print'</th>';
$obj->aoColumns[$i]->mDataProp = "label";
$obj->aoColumns[$i]->bSearchable = true;
//$obj->aoColumns[$i]->sDefaultContent = "";
$i++;
print'<th class="essential">';
print $langs->trans("AttributeCode");
print'</th>';
$obj->aoColumns[$i]->mDataProp = "key";
$obj->aoColumns[$i]->bSearchable = false;
//$obj->aoColumns[$i]->sDefaultContent = "";
$i++;
print'<th class="essential">';
print $langs->trans("Type");
print'</th>';
$obj->aoColumns[$i]->mDataProp = "type";
$obj->aoColumns[$i]->bSearchable = false;
$obj->aoColumns[$i]->sDefaultContent = "";
$i++;
/* print'<th class="essential">';
  print $langs->trans("Size");
  print'</th>';
  $obj->aoColumns[$i]->mDataProp = "size";
  $obj->aoColumns[$i]->bSearchable = false;
  $obj->aoColumns[$i]->sDefaultContent = "";
  $i++; */
print'<th class="essential">';
print $langs->trans("Action");
print'</th>';
$obj->aoColumns[$i]->mDataProp = "action";
$obj->aoColumns[$i]->sClass = "center content_actions";
$obj->aoColumns[$i]->bSearchable = false;
print "</tr>";
print "</thead>";
print "<tbody>";

foreach ($object->fk_extrafields->fields as $key => $aRow) {
    if (is_object($aRow) && $aRow->edit) {
        print "<tr>";
        print '<td>' . $key . '</td>';
        print '<td>' . $aRow->order . '</td>';
        print "<td>" . (empty($aRow->label) ? $langs->trans($key) : $langs->trans($aRow->label)) . "</td>";
        print "<td>" . $key . "</td>";
        print "<td>" . $aRow->type . "</td>";
        // print '<td>' . $aRow->length . '</td>';
        print '<td>';
        print '<a class="sepV_a" href="' . $_SERVER["PHP_SELF"] . '?' . '&fields=' . $key . '&attrname=' . $key1 . '&action=' . $acts[$aRow->enable] . '">' . $actl[$aRow->enable] . '</a>';
        if ($aRow->edit) {

            print '<a class="sepV_a" href="' . $_SERVER["PHP_SELF"] . '?action=edit&fields=' . $key . '&attrname=' . $key1 . '">' . img_edit() . '</a>';
            print '<a class="sepV_a" href="' . $_SERVER["PHP_SELF"] . '?action=delete&fields=' . $key . '&attrname=' . $key1 . '">' . img_delete() . '</a>';
        }
        print '</td>';
        print "</tr>";
    }
}

print "</tbody>";
print "</table>";
//print '</div>';

$obj->iDisplayLength = 100;
print $object->datatablesCreate($obj, "list_fields");

print end_box();
print '</div>';
print '</div>';

llxFooter();

$db->close();
?>
