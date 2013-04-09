<?php

/* Copyright (C) 2003-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2013	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2013	Herve Prot				<herve.prot@symeos.com>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

$langs->load("errors");
$langs->load("admin");

if (empty($user->admin))
	accessforbidden();

$action = GETPOST('action', 'alpha');

$object = new DolibarrModules($db);

$filename = array();
$modules = array();
$orders = array();
$categ = array();
$dirmod = array();
$i = 0; // is a sequencer of modules found
$j = 0; // j is module number. Automatically affected if module number not defined.
$modNameLoaded = array();

$mesg = $object->load_modules_files($filename, $modules, $orders, $categ, $dirmod, $modNameLoaded);

if (!empty($mesg))
	setEventMessage($mesg, 'errors');


/*
 * Actions
 */



/*
 * View
 */


llxHeader('', $langs->trans("Setup"));

print_fiche_titre($langs->trans("Setup"));

print '<div class="with-padding">';
print '<div class="columns">';

print start_box($langs->trans("ModulesSetup"), 'icon-object-config', '', false);


$obj = new stdClass();
$i = 0;

print '<table class="display dt_act" id="list_modules">';

print'<thead>';
print'<tr>';

print'<th>';
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "id";
$obj->aoColumns[$i]->sDefaultContent = "";
$obj->aoColumns[$i]->bVisible = false;
$i++;

print'<th class="essential">';
print $langs->trans("Family");
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "family";
$obj->aoColumns[$i]->sDefaultContent = "other";
$obj->aoColumns[$i]->bVisible = false;
$i++;

print'<th class="essential">';
print $langs->trans("Module");
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "name";
$obj->aoColumns[$i]->sDefaultContent = "";
$i++;

print'<th>';
print $langs->trans("Description");
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "desc";
$obj->aoColumns[$i]->sDefaultContent = "";
$obj->aoColumns[$i]->bVisible = true;
$i++;
print'<th class="essential">';
print $langs->trans("Version");
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "version";
$obj->aoColumns[$i]->sDefaultContent = "false";
$obj->aoColumns[$i]->sClass = "center";
$obj->aoColumns[$i]->sWidth = "100px";
$i++;
print'<th class="essential">';
print $langs->trans("Status");
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "Status";
$obj->aoColumns[$i]->sDefaultContent = "false";
$obj->aoColumns[$i]->sClass = "center";
$i++;
print'<th class="essential">';
print $langs->trans("SetupShort");
print'</th>';
$obj->aoColumns[$i] = new stdClass();
$obj->aoColumns[$i]->mDataProp = "setup";
$obj->aoColumns[$i]->sDefaultContent = "";
$obj->aoColumns[$i]->bSortable = false;
$obj->aoColumns[$i]->sClass = "center";
print'</tr>';

print'</thead>';
$obj->fnDrawCallback = "
	function(oSettings) {
		if ( oSettings.aiDisplay.length == 0 ) {
			return;
		}
		var nTrs = jQuery('#list_modules tbody tr');
		var iColspan = nTrs[0].getElementsByTagName('td').length;
		var sLastGroup = '';
		for ( var i=0 ; i<nTrs.length ; i++ ) {
			var iDisplayIndex = oSettings._iDisplayStart + i;
			var sGroup = oSettings.aoData[ oSettings.aiDisplay[iDisplayIndex] ]._aData['family'];
			if (sGroup!=null && sGroup!='' && sGroup != sLastGroup) {
				var nGroup = document.createElement('tr');
				var nCell = document.createElement('td');
				nCell.colSpan = iColspan;
				nCell.className = 'group';
				nCell.innerHTML = sGroup;
				nGroup.appendChild( nCell );
				nTrs[i].parentNode.insertBefore( nGroup, nTrs[i] );
				sLastGroup = sGroup;
			}
		}
	}";

print'<tfoot>';
print'</tfoot>';
print'<tbody>';

// Affichage liste modules

$var = true;
$oldfamily = '';

$familylib = array(
		'base' => $langs->trans("ModuleFamilyBase"),
		'crm' => $langs->trans("ModuleFamilyCrm"),
		'products' => $langs->trans("ModuleFamilyProducts"),
		'hr' => $langs->trans("ModuleFamilyHr"),
		'projects' => $langs->trans("ModuleFamilyProjects"),
		'financial' => $langs->trans("ModuleFamilyFinancial"),
		'ecm' => $langs->trans("ModuleFamilyECM"),
		'technic' => $langs->trans("ModuleFamilyTechnic"),
		'other' => $langs->trans("ModuleFamilyOther")
);

foreach ($orders as $key => $value) {
	$tab = explode('_', $value);
	$family = $tab[0];
	$numero = $tab[1];

	$modName = $filename[$key];
	$objMod = $modules[$key];
	//var_dump($objMod);

	if (!$objMod->getName()) {
		continue;
	}

	$const_name = 'MAIN_MODULE_' . strtoupper(preg_replace('/^mod/i', '', get_class($objMod)));

	// Load all lang files of module
	if (isset($objMod->langfiles) && is_array($objMod->langfiles)) {
		foreach ($objMod->langfiles as $domain) {
			$langs->load($domain);
		}
	}

	//print "\n<!-- Module ".$objMod->numero." ".$objMod->getName()." found into ".$dirmod[$key]." -->\n";
	print '<tr>';

	// Id
	print '<td>';
	print $objMod->numero;
	print '</td>';

	// Family
	print '<td>';
	$family = $objMod->family;
	print $familytext = empty($familylib[$family]) ? $family : $familylib[$family];
	print "</td>\n";

	// Picto
	print '  <td>';
	$alttext = '';
	//if (is_array($objMod->need_dolibarr_version)) $alttext.=($alttext?' - ':'').'Dolibarr >= '.join('.',$objMod->need_dolibarr_version);
	//if (is_array($objMod->phpmin)) $alttext.=($alttext?' - ':'').'PHP >= '.join('.',$objMod->phpmin);
	if (!empty($objMod->picto)) {
		if (preg_match('/^\//i', $objMod->picto))
			print img_picto($alttext, $objMod->picto, ' width="14px"', 1);
		else
			print img_object($alttext, $objMod->picto, ' width="14px"');
	}
	else {
		print img_object($alttext, 'generic');
	}


	// Name
	print ' ' . $objMod->getName();
	print "</td>\n";

	// Desc
	print "<td>";
	print nl2br($objMod->getDesc());
	print "</td>\n";

	// Version
	print "<td>";
	print $objMod->getVersion();
	print "</td>\n";

	// Activate/Disable and Setup (2 columns)
	$name = strtolower($objMod->name);
	
	print '<td>';
	print ajax_moduleonoff($objMod->name, $key, $objMod->version);
	print '</td>' . "\n";
	
	print '<td>';
	if (!empty($objMod->config_page_url)) {
		print '<div id="config_' . $key . '" class="hideobject">';
		if (is_array($objMod->config_page_url)) {
			$i = 0;
			foreach ($objMod->config_page_url as $page) {
				$urlpage = $page;
				if ($i++) {
					print '<a href="' . $_SERVER['PHP_SELF'] . '/' . $urlpage . '" title="' . $langs->trans($page) . '">' . img_picto(ucfirst($page), "setup") . '</a>&nbsp;';
					//    print '<a href="'.$page.'">'.ucfirst($page).'</a>&nbsp;';
				} else {
					if (preg_match('/^([^@]+)@([^@]+)$/i', $urlpage, $regs)) {
						print '<a href="' . dol_buildpath('/' . $regs[2] . '/admin/' . $regs[1], 1) . '" title="' . $langs->trans("Setup") . '">' . img_picto($langs->trans("Setup"), "setup") . '</a>&nbsp;';
					} else {
						print '<a href="' . DOL_URL_ROOT . '/admin/' . $urlpage . '" title="' . $langs->trans("Setup") . '">' . img_picto($langs->trans("Setup"), "setup") . '</a>&nbsp;';
					}
				}
			}
		} else if (preg_match('/^([^@]+)@([^@]+)$/i', $objMod->config_page_url, $regs)) {
			print '<a href="' . dol_buildpath('/' . $regs[2] . '/admin/' . $regs[1], 1) . '" title="' . $langs->trans("Setup") . '">' . img_picto($langs->trans("Setup"), "setup") . '</a>';
		} else {
			print '<a href="' . $objMod->config_page_url . '" title="' . $langs->trans("Setup") . '">' . img_picto($langs->trans("Setup"), "setup") . '</a>';
		}
		print '</div>';
	}
	print "</td>\n";

	print "</tr>\n";
}
print'</tbody>';
print'</table>';

$obj->aaSorting = array(array(1, 'asc'));
$obj->sDom = 'l<fr>t<\"clear\"rtip>';
$obj->iDisplayLength = 100;
$obj->bServerSide = false;

print $object->datatablesCreate($obj, "list_modules");

print end_box();
print '</div></div>';

llxFooter();
?>