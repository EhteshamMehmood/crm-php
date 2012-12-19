<?php
/* Copyright (C) 2005-2012	Regis Houssin		<regis@dolibarr.fr>
 * Copyright (C) 2007-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
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
 *       \file       htdocs/core/ajax/box.php
 *       \brief      File to return Ajax response on Box move or close
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';

$boxid=GETPOST('boxid','int');
$boxorder=GETPOST('boxorder');
$userid=GETPOST('userid');
$zone=GETPOST('zone','int');
$userid=GETPOST('userid','int');


/*
 * View
 */

// Ajout directives pour resoudre bug IE
//header('Cache-Control: Public, must-revalidate');
//header('Pragma: public');

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead();

print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

// Add a box
if ($boxid > 0 && $zone !='' && $userid > 0)
{
	$tmp=explode('-',$boxorder);
	$nbboxonleft=substr_count($tmp[0],',');
	$nbboxonright=substr_count($tmp[1],',');
	print $nbboxonleft.'-'.$nbboxonright;
	if ($nbboxonleft > $nbboxonright) $boxorder=preg_replace('/B:/','B:'.$boxid.',',$boxorder);    // Insert id of new box into list
    else $boxorder=preg_replace('/^A:/','A:'.$boxid.',',$boxorder);    // Insert id of new box into list
}

// Registering the location of boxes after a move
if ($boxorder && $zone != '' &&  $userid > 0)
{
	// boxorder value is the target order: "A:idboxA1,idboxA2,A-B:idboxB1,idboxB2,B"
	dol_syslog("AjaxBox boxorder=".$boxorder." zone=".$zone." userid=".$userid, LOG_DEBUG);

	//$infobox=new InfoBox($db);
	$result=InfoBox::saveboxorder($db,$zone,$boxorder,$userid);
}

?>
