<?php

/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011-2012 Herve Prot           <herve.prot@symeos.com>
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
 * 	    \file       htdocs/contact/list.php
 *      \ingroup    societe
 * 		\brief      Page to list all contacts
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

$langs->load("companies");
$langs->load("suppliers");

// Security check
$contactid = GETPOST('id', 'alpha');
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'contact', $contactid, '');

$type = GETPOST("type");
$view = GETPOST("view");

$sall = GETPOST("contactname");
$userid = GETPOST('userid', 'int');

$langs->load("companies");
$titre = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("ListOfContacts") : $langs->trans("ListOfContactsAddresses"));
if ($type == "c") {
    $titre.='  (' . $langs->trans("ThirdPartyCustomers") . ')';
    $urlfiche = "fiche.php";
} else if ($type == "p") {
    $titre.='  (' . $langs->trans("ThirdPartyProspects") . ')';
    $urlfiche = "prospect/fiche.php";
} else if ($type == "f") {
    $titre.=' (' . $langs->trans("ThirdPartySuppliers") . ')';
    $urlfiche = "fiche.php";
} else if ($type == "o") {
    $titre.=' (' . $langs->trans("OthersNotLinkedToThirdParty") . ')';
    $urlfiche = "";
}

$object = new Contact($db);
$soc = new Societe($db);

if ($_GET['json'] == "list") {
    $output = array(
        "sEcho" => intval($_GET['sEcho']),
        "iTotalRecords" => 0,
        "iTotalDisplayRecords" => 0,
        "aaData" => array()
    );

    $keystart[0] = $viewname;
    $keyend[0] = $viewname;
    $keyend[1] = new stdClass();

    $result = array();
    try {
        $resultsoc = $soc->getView("listByCommercial", array("key" => $user->id));
    } catch (Exception $exc) {
        print $exc->getMessage();
    }

    if (count($resultsoc->rows)) {
        foreach ($resultsoc->rows as $aRow) {
            $resultcontact = $object->getView("listSociete", array("key" => $aRow->id));
            if (count($resultcontact->rows)) {
                foreach ($resultcontact->rows as $row) {
                    $result[] = $row;
                }
            }
        }
    }
    //print_r($result);
    //exit;

    $iTotal = count($result);
    $output["iTotalRecords"] = $iTotal;
    $output["iTotalDisplayRecords"] = $iTotal;
    $i = 0;
    foreach ($result as $aRow) {
        $output["aaData"][] = $aRow->value;
        unset($element);
    }

    header('Content-type: application/json');
    echo json_encode($output);
    exit;
}

/*
 * View
 */

$title = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));
llxHeader('', $title, 'EN:Module_Third_Parties|FR:Module_Tiers|ES:M&oacute;dulo_Empresas');

print_fiche_titre($title);
print '<div class="with-padding">';

/*
 * Barre d'actions
 *
 */

print '<p class="button-height right">';
print '<a class="button icon-star" href="' . strtolower(get_class($object)) . '/fiche.php?action=create">' . $langs->trans("NewContact") . '</a>';
print "</p>";

$i = 0;
$obj = new stdClass();
print '<table class="display dt_act" id="list_contacts" >';
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
print $langs->trans("Lastname");
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "name";
$obj->aoColumns[$i]->bUseRendered = false;
$obj->aoColumns[$i]->bSearchable = true;
$obj->aoColumns[$i]->fnRender = $object->datatablesFnRender("name", "url");
$i++;
/* print'<th class="essential">';
  print $langs->trans("Firstname");
  print'</th>';
  $obj->aoColumns[$i]->mDataProp = "firstname";
  $obj->aoColumns[$i]->bUseRendered = false;
  $obj->aoColumns[$i]->bSearchable = true;
  $obj->aoColumns[$i]->sDefaultContent = "";
  $i++; */
print'<th class="essential">';
print $langs->trans("PostOrFunction");
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "poste";
$obj->aoColumns[$i]->bUseRendered = false;
$obj->aoColumns[$i]->bSearchable = true;
$obj->aoColumns[$i]->sDefaultContent = "";
$i++;
print'<th class="essential">';
print $langs->trans('Company');
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "societe";
$obj->aoColumns[$i]->sDefaultContent = "";
$obj->aoColumns[$i]->fnRender = $soc->datatablesFnRender("societe.name", "url", array('id' => "societe.id"));
$i++;
print'<th class="essential">';
print $langs->trans('Phone');
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "phone";
$obj->aoColumns[$i]->sDefaultContent = "";
$i++;
print'<th class="essential">';
print $langs->trans('Mobile');
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "phone_mobile";
$obj->aoColumns[$i]->sDefaultContent = "";
$i++;
print'<th class="essential">';
print $langs->trans('EMail');
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "email";
$obj->aoColumns[$i]->sDefaultContent = "";
$obj->aoColumns[$i]->fnRender = $object->datatablesFnRender("email", "email");
$i++;
/*print'<th class="essential">';
print $langs->trans('DateModificationShort');
print'</th>';
$obj->aoColumns[$i]->mDataProp = "tms";
$obj->aoColumns[$i]->sClass = "center";
$obj->aoColumns[$i]->sDefaultContent = "";
$obj->aoColumns[$i]->fnRender = $object->datatablesFnRender("tms", "date");
//$obj->aoColumns[$i]->sClass = "edit";
$i++;*/
print'<th class="essential">';
print $langs->trans('Categories');
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "Tag";
$obj->aoColumns[$i]->sClass = "center";
$obj->aoColumns[$i]->sDefaultContent = "";
$obj->aoColumns[$i]->fnRender = $object->datatablesFnRender("Tag", "tag");
$i++;
print'<th class="essential">';
print $langs->trans("Status");
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "Status";
$obj->aoColumns[$i]->sClass = "dol_select center";
$obj->aoColumns[$i]->sWidth = "100px";
$obj->aoColumns[$i]->sDefaultContent = $object->fk_extrafields->fields->Status->default;
$obj->aoColumns[$i]->fnRender = $object->datatablesFnRender("Status", "status");
$i++;
print'<th class="essential">';
print $langs->trans('Action');
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "";
$obj->aoColumns[$i]->sClass = "center content_actions";
$obj->aoColumns[$i]->sWidth = "60px";
$obj->aoColumns[$i]->bSortable = false;
$obj->aoColumns[$i]->sDefaultContent = "";

$url = "contact/fiche.php";
$obj->aoColumns[$i]->fnRender = 'function(obj) {
	var ar = [];
	ar[ar.length] = "<a href=\"' . $url . '?id=";
	ar[ar.length] = obj.aData._id.toString();
	ar[ar.length] = "&action=edit&backtopage=' . $_SERVER['PHP_SELF'] . '\" class=\"sepV_a\" title=\"' . $langs->trans("Edit") . '\"><img src=\"' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/edit.png\" alt=\"\" /></a>";
	ar[ar.length] = "<a href=\"\"";
	ar[ar.length] = " class=\"delEnqBtn\" title=\"' . $langs->trans("Delete") . '\"><img src=\"' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/delete.png\" alt=\"\" /></a>";
	var str = ar.join("");
	return str;
}';
print'</tr>';
print'</thead>';
print'<tfoot>';
/* input search view */
$i = 0; //Doesn't work with bServerSide
print'<tr>';
print'<th id="' . $i . '"></th>';
$i++;
print'<th id="' . $i . '"><input type="text" placeholder="' . $langs->trans("Search Name") . '" /></th>';
$i++;
//print'<th id="' . $i . '"><input type="text" placeholder="' . $langs->trans("Search Firstname") . '" /></th>';
//$i++;
print'<th id="' . $i . '"><input type="text" placeholder="' . $langs->trans("Search Poste") . '" /></th>';
$i++;
print'<th id="' . $i . '"><input type="text" placeholder="' . $langs->trans("Search Company") . '" /></th>';
$i++;
print'<th id="' . $i . '"></th>';
$i++;
print'<th id="' . $i . '"></th>';
$i++;
print'<th id="' . $i . '"></th>';
$i++;
/*print'<th id="' . $i . '"></th>';
$i++;*/
print'<th id="' . $i . '"><input type="text" placeholder="' . $langs->trans("Search Category") . '" /></th>';
$i++;
print'<th id="' . $i . '"><input type="text" placeholder="' . $langs->trans("Search Status") . '" /></th>';
$i++;
print'<th id="' . $i . '"></th>';
$i++;
print'</tr>';
print'</tfoot>';
print'<tbody>';
print'</tbody>';

print "</table>";

//$obj->bServerSide = true;
if (!$user->rights->societe->client->voir)
    $obj->sAjaxSource = $_SERVER["PHP_SELF"] . "?json=list&class=" . get_class($object) . "&key=" . $user->id;

$object->datatablesCreate($obj, "list_contacts", true, true);

print '</div>'; // end

llxFooter();
$db->close();
?>
