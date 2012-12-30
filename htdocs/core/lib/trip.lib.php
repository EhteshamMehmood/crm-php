<?php
/* Copyright (C) 2011	Regis Houssin	<regis.houssin@capnetworks.com>
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
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/core/lib/trip.lib.php
 *		\brief      Ensemble de fonctions de base pour les deplacements
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function trip_prepare_head($object)
{
	global $langs, $conf;

	$h = 0;
	$head = array();
	
	$head[$h][0] = DOL_URL_ROOT . '/compta/deplacement/fiche.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;
	
	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf,$langs,$object,$head,$h,'trip');
	
	$head[$h][0] = DOL_URL_ROOT . '/compta/deplacement/info.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	return $head;
}

?>