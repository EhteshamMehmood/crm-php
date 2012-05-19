<?php

/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011      Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2011-2012 Herve Prot           <herve.prot@symeos.com>
 * Copyright (C) 2011      Patrick Mary         <laube@hotmail.fr>
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
 *	\file       htdocs/comm/list.php
 *	\ingroup    commercial societe
 *	\brief      List of customers
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");

$langs->load("companies");
$langs->load("customers");
$langs->load("suppliers");
$langs->load("commercial");

// Security check
$socid = GETPOST("socid");
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'societe', $socid, '');

$type = GETPOST("type", 'int');
$pstcomm = GETPOST("pstcomm");
$search_sale = GETPOST("search_sale");

$object = new Societe($couchdb);

if($_GET['json']=="list")
{
	$output = $object->getList();
	
    header('Content-type: application/json');
    echo json_encode($output);
    exit;
}

if($_GET['json']=="Status"){
    $value = $_GET['value'];
    
    foreach ($object->fk_extrafields->fields->Status->values as $key => $aRow)
    {
	if($aRow->enable)
        {   
	    $array[$key] = $langs->trans($key);
        }
    }
    $array['selected'] = "ST_PCOLD";
    
    print json_encode($array);
    exit;
}
/*edit cell value */
if($_GET['json']=="edit"){
    $key = $_POST['key'];
    $id = $_POST['id'];
    $value = $_POST['value'];
    
    try {
		$object->id = $id;
		$res = $object->set($key, $value);
	    if( $res == $value )
	    {
			if($key == 'Status')
				print $object->LibStatut($value);
			else
				print $value;
	    }
	    else
	    {
		print $res."</br>";
			print_r($object->errors);
	    }
        exit;
	} catch (Exception $exc) {
		print $exc->getTraceAsString();
        exit;
	}
}

// Select every potentiels.
$sql = "SELECT code, label, sortorder";
$sql.= " FROM ".MAIN_DB_PREFIX."c_prospectlevel";
$sql.= " WHERE active > 0";
$sql.= " ORDER BY sortorder";
$resql = $db->query($sql);
if ($resql)
{
    $tab_level = array();
    while ($obj = $db->fetch_object($resql))
        {     
            $level=$obj->code;
            // Put it in the array sorted by sortorder
            $tab_level[$obj->sortorder] = $level;
        }

 // Added by Matelli (init list option)
   $options = '<option value="">&nbsp;</option>';
   foreach ($tab_level as $tab_level_label)
     {
     $options .= '<option value="'.$tab_level_label.'">';
     $options .= $langs->trans($tab_level_label);
     $options .= '</option>';
     }        
}

/*
 * View
 */

$htmlother = new FormOther($db);


llxHeader('', $langs->trans("ThirdParty"), $help_url, '', '', '', '');

if ($type != '') {
    if ($type == 0)
        $titre = $langs->trans("ListOfSuspects");
    elseif ($type == 1)
        $titre = $langs->trans("ListOfProspects");
    else
        $titre = $langs->trans("ListOfCustomers");
}
else
    $titre = $langs->trans("ListOfAll");

print '<div class="row">';

print start_box($titre,"twelve","16-Companies.png");

$i=0;
$obj=new stdClass();

print '<table class="display dt_act" id="societe" >';
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
print $langs->trans("Company");
print'</th>';
$obj->aoColumns[$i]->mDataProp = "ThirdPartyName";
$obj->aoColumns[$i]->bUseRendered = false;
$obj->aoColumns[$i]->bSearchable = true;
$obj->aoColumns[$i]->fnRender= $object->datatablesFnRender("ThirdPartyName", "url");
$i++;
foreach ($object->fk_extrafields->longList as $aRow)
{
	print'<th class="essential">';
	print $langs->trans($aRow);
	print'</th>';
	$obj->aoColumns[$i] = $object->fk_extrafields->fields->$aRow->aoColumns;
	if(isset($object->fk_extrafields->$aRow->default))
		$obj->aoColumns[$i]->sDefaultContent = $object->fk_extrafields->$aRow->default;
	else
		$obj->aoColumns[$i]->sDefaultContent = "";
	$obj->aoColumns[$i]->mDataProp = $aRow;
	$i++;
}
print'<th class="essential">';
print $langs->trans('Categories');
print'</th>';
$obj->aoColumns[$i]->mDataProp = "tag";
$obj->aoColumns[$i]->sDefaultContent = "";
$obj->aoColumns[$i]->sClass = "edit";
$i++;
print'<th class="essential">';
print $langs->trans("Status");
print'</th>';
$obj->aoColumns[$i]->mDataProp = "Status";
$obj->aoColumns[$i]->sClass = "select center";
$obj->aoColumns[$i]->sWidth = "100px";
$obj->aoColumns[$i]->sDefaultContent = "ST_NEVER";
$obj->aoColumns[$i]->fnRender = $object->datatablesFnRender("Status", "status");
$i++;
print'<th class="essential">';
print $langs->trans("Date");
print'</th>';
$obj->aoColumns[$i]->mDataProp = "tms";
$obj->aoColumns[$i]->sType="date";
$obj->aoColumns[$i]->sClass = "center";
$obj->aoColumns[$i]->sWidth = "200px";
$obj->aoColumns[$i]->fnRender = $object->datatablesFnRender("tms", "date");
print'</tr>';
print'</thead>';
print'<tfoot>';
/* input search view */
$i=0;
print'<tr>';
print'<th id="'.$i.'"></th>';
$i++;
print'<th id="'.$i.'"><input type="text" placeholder="' . $langs->trans("Search Company") . '" /></th>';
$i++;
foreach ($object->fk_extrafields->longList as $aRow)
{
	if($object->fk_extrafields->fields->$aRow->aoColumns->bSearchable = true)
		print'<th id="'.$i.'"><input type="text" placeholder="' . $langs->trans("Search ".$aRow) . '" /></th>';
	else
		print'<th id="'.$i.'"></th>';
	$i++;
}
print'<th id="'.$i.'"><input type="text" placeholder="' . $langs->trans("Search category") . '" /></th>';
$i++;
print'<th id="'.$i.'"><input type="text" placeholder="' . $langs->trans("Search status") . '" /></th>';
$i++;
print'<th id="'.$i.'"></th>';
$i++;
print'</tr>';
print'</tfoot>';
print'<tbody>';
print'</tbody>';

print "</table>";

$object->datatablesCreate($obj,"societe",true,true);

print end_box();
print '</div>'; // end row

llxFooter();
?>
 
