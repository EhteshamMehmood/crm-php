<?php

/* Copyright (C) 2011-2012 Herve Prot           <herve.prot@symeos.com>
 *
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/useradmin/class/useradmin.class.php';

if (!$user->superadmin)
    accessforbidden();

$langs->load("users");
$langs->load("companies");

// Security check (for external users)
$socid = 0;
if ($user->societe_id > 0)
    $socid = $user->societe_id;

$object = new UserAdmin($db);
$companystatic = new Societe($db);


/*
 * View
 */

llxHeader();

$title = $langs->trans("ListOfUsers");

print_fiche_titre($title);
print '<div class="with-padding">';
print '<div class="columns">';
print start_box($title, "twelve", "16-User.png", false);

/*
 * Barre d'actions
 *
 */

print '<p class="button-height right">';
print '<span class="button-group">';
print '<a class="button icon-star" href="useradmin/fiche.php?action=create">' . $langs->trans("CreateUser") . '</a>';
print "</span>";
print "</p>";

$i = 0;
$obj = new stdClass();

print '<table class="display dt_act" id="user" >';
// Ligne des titres
print'<thead>';
print'<tr>';
print'<th>';
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "_id";
$obj->aoColumns[$i]->bUseRendered = false;
$obj->aoColumns[$i]->bSearchable = false;
$obj->aoColumns[$i]->bVisible = false;
$i++;
print'<th class="essential">';
print $langs->trans("Login");
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "name";
$obj->aoColumns[$i]->bUseRendered = false;
$obj->aoColumns[$i]->bSearchable = true;

$url = strtolower(get_class($object)) . '/fiche.php?id=';
$key = "name";
$obj->aoColumns[$i]->fnRender = 'function(obj) {
				var ar = [];
				ar[ar.length] = "<img src=\"theme/' . $conf->theme . '/img/ico/icSw2/' . $object->fk_extrafields->ico . '\" border=\"0\" alt=\"' . $langs->trans("See " . get_class($object)) . ' : ";
				ar[ar.length] = obj.aData.' . $key . '.toString();
				ar[ar.length] = "\" title=\"' . $langs->trans("See " . get_class($object)) . ' : ";
				ar[ar.length] = obj.aData.' . $key . '.toString();
				ar[ar.length] = "\"> <a href=\"' . $url . '";
				ar[ar.length] = obj.aData._id;
				ar[ar.length] = "\">";
				ar[ar.length] = obj.aData.' . $key . '.toString();
				ar[ar.length] = "</a> ";
				if(obj.aData.admin) {
					ar[ar.length] = "<img src=\"theme/' . $conf->theme . '/img/redstar.png\" border=\"0\" ";
					ar[ar.length] = "\" title=\"' . $langs->trans("SuperAdmin") . '";
					ar[ar.length] = "\">";
				}
				var str = ar.join("");
				return str;
			}';
$i++;
print'<th class="essential">';
print $langs->trans('LastName');
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "Lastname";
$obj->aoColumns[$i]->sDefaultContent = "";
$obj->aoColumns[$i]->sClass = "";
$i++;
print'<th class="essential">';
print $langs->trans('FirstName');
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "Firstname";
$obj->aoColumns[$i]->sDefaultContent = "";
$obj->aoColumns[$i]->sClass = "";
$i++;
print'<th class="essential">';
print $langs->trans('Database');
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "entityList";
$obj->aoColumns[$i]->sDefaultContent = "";
$obj->aoColumns[$i]->sClass = "center";
$obj->aoColumns[$i]->fnRender = $object->datatablesFnRender("entityList", "tag");
$i++;
print'<th class="essential">';
print $langs->trans('LastConnexion');
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "NewConnection";
$obj->aoColumns[$i]->sType = "date";
$obj->aoColumns[$i]->sDefaultContent = "";
$obj->aoColumns[$i]->sClass = "center";
$obj->aoColumns[$i]->sWidth = "200px";
$obj->aoColumns[$i]->fnRender = $object->datatablesFnRender("NewConnection", "datetime");
$i++;
print'<th class="essential">';
print $langs->trans('Status');
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "Status";
$obj->aoColumns[$i]->sClass = "dol_select center";
$obj->aoColumns[$i]->sWidth = "100px";
$obj->aoColumns[$i]->sDefaultContent = "DISABLE";
$obj->aoColumns[$i]->fnRender = $object->datatablesFnRender("Status", "status");
$i++;
print'</tr>';
print'</thead>';
print'<tfoot>';
print'</tfoot>';
print'<tbody>';
print'</tbody>';

print "</table>";

$obj->sDom = 'l<fr>t<\"clear\"rtip>';
$obj->sAjaxSource = 'useradmin/ajax/list.php?json=list';

$object->datatablesCreate($obj, "user", true);



print end_box();
print '<div>';

llxFooter();
?>
