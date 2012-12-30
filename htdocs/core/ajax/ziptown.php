<?php
/* Copyright (C) 2012 Regis Houssin       <regis.houssin@capnetworks.com>
 * Copyright (C) 2011 Laurent Destailleur <eldy@users.sourceforge.net>
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
 *       \file      htdocs/core/ajax/ziptown.php
 *       \ingroup	core
 *       \brief     File to return Ajax response on zipcode or town request
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL',1); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';



/*
 * View
 */

// Ajout directives pour resoudre bug IE
//header('Cache-Control: Public, must-revalidate');
//header('Pragma: public');

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead();

//print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

dol_syslog("GET is ".join(',',$_GET).', MAIN_USE_ZIPTOWN_DICTIONNARY='.(empty($conf->global->MAIN_USE_ZIPTOWN_DICTIONNARY)?'':$conf->global->MAIN_USE_ZIPTOWN_DICTIONNARY));
//var_dump($_GET);

// Generation of list of zip-town
if (! empty($_GET['zipcode']) || ! empty($_GET['town']))
{
	$return_arr = array();
	$formcompany = new FormCompany($db);

	// Define filter on text typed
	$zipcode = $_GET['zipcode']?$_GET['zipcode']:'';
	$town = $_GET['town']?$_GET['town']:'';

	if (! empty($conf->global->MAIN_USE_ZIPTOWN_DICTIONNARY))   // Use zip-town table
	{
    	$sql = "SELECT z.rowid, z.zip, z.town, z.fk_county, z.fk_pays as fk_country";
    	$sql.= ", p.rowid as fk_country, p.code as country_code, p.libelle as country";
    	$sql.= ", d.rowid as fk_county, d.code_departement as county_code, d.nom as county";
    	$sql.= " FROM (".MAIN_DB_PREFIX."c_ziptown as z,".MAIN_DB_PREFIX."c_pays as p)";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX ."c_departements as d ON z.fk_county = d.rowid";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_regions as r ON d.fk_region = r.code_region";
    	$sql.= " WHERE z.fk_pays = p.rowid";
    	$sql.= " AND z.active = 1 AND p.active = 1";
    	if ($zipcode) $sql.=" AND z.zip LIKE '" . $db->escape($zipcode) . "%'";
    	if ($town)    $sql.=" AND z.town LIKE '%" . $db->escape($town) . "%'";
    	$sql.= " ORDER BY z.zip, z.town";
        $sql.= $db->plimit(50); // Avoid pb with bad criteria
	}
	else                                               // Use table of third parties
	{
        $sql = "SELECT DISTINCT s.cp as zip, s.ville as town, s.fk_departement as fk_county, s.fk_pays as fk_country";
        $sql.= ", p.code as country_code, p.libelle as country";
        $sql.= ", d.code_departement as county_code , d.nom as county";
        $sql.= " FROM ".MAIN_DB_PREFIX.'societe as s';
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX ."c_departements as d ON fk_departement = d.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX.'c_pays as p ON fk_pays = p.rowid';
        $sql.= " WHERE";
        if ($zipcode) $sql.= " s.cp LIKE '".$db->escape($zipcode)."%'";
        if ($town)    $sql.= " s.ville LIKE '%" . $db->escape($town) . "%'";
        $sql.= " ORDER BY s.fk_pays, s.cp, s.ville";
        $sql.= $db->plimit(50); // Avoid pb with bad criteria
	}

    //print $sql;
	$resql=$db->query($sql);
	//var_dump($db);
	if ($resql)
	{
		while ($row = $db->fetch_array($resql))
		{
			$country = $row['fk_country']?($langs->trans('Country'.$row['country_code'])!='Country'.$row['country_code']?$langs->trans('Country'.$row['country_code']):$row['country']):'';
			$county = $row['fk_county']?($langs->trans($row['county_code'])!=$row['county_code']?$langs->trans($row['county_code']):($row['county']!='-'?$row['county']:'')):'';

			$row_array['label'] = $row['zip'].' '.$row['town'];
			$row_array['label'] .= ($county || $country)?' (':'';
            $row_array['label'] .= $county;
			$row_array['label'] .= ($county && $country?' - ':'');
            $row_array['label'] .= $country;
            $row_array['label'] .= ($county || $country)?')':'';
            if ($zipcode)
			{
				$row_array['value'] = $row['zip'];
				$row_array['town'] = $row['town'];
			}
			if ($town)
			{
				$row_array['value'] = $row['town'];
				$row_array['zipcode'] = $row['zip'];
			}
			$row_array['departement_id'] = $row['fk_county'];    // deprecated
			$row_array['selectcountry_id'] = $row['fk_country'];
			$row_array['state_id'] = $row['fk_county'];

			$row_array['states'] = $formcompany->select_state('',$row['fk_country'],'');

			array_push($return_arr,$row_array);
		}
	}

	echo json_encode($return_arr);
}
else
{

}

$db->close();

?>
