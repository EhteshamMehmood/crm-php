<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/**
 * 	    \file       htdocs/boutique/critiques/index.php
 * 		\ingroup    boutique
 * 		\brief      Page gestion critiques OSCommerce
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/boutique/osc_master.inc.php';


/*
 * View
 */

llxHeader();

if ($sortfield == "") {
	$sortfield="date_added";
}
if ($sortorder == "") {
	$sortorder="DESC";
}


if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

print_barre_liste("Critiques", $page, "index.php");

$sql = "SELECT r.reviews_id, r.reviews_rating, d.reviews_text, p.products_name FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."reviews as r, ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."reviews_description as d, ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."products_description as p";
$sql .= " WHERE r.reviews_id = d.reviews_id AND r.products_id=p.products_id";
$sql .= " AND p.language_id = ".$conf->global->OSC_LANGUAGE_ID. " AND d.languages_id=".$conf->global->OSC_LANGUAGE_ID;
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $dbosc->plimit($limit,$offset);

print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
print '<TR class="liste_titre">';
print "<td>Produit</td>";
print "<td>Critique</td>";
print "<td align=\"center\">Note</td>";
print "<TD align=\"right\"></TD>";
print "</TR>\n";

$resql=$dbosc->query($sql);
if ($resql) {
	$num = $dbosc->num_rows($resql);
	$i = 0;

	$var=True;
	while ($i < $num) {
		$objp = $dbosc->fetch_object($resql);
		$var=!$var;
		print "<TR $bc[$var]>";
		print "<TD>".substr($objp->products_name, 0, 30)."</TD>\n";
		print '<TD><a href="fiche.php?id='.$objp->reviews_id.'">'.substr($objp->reviews_text, 0, 40)." ...</a></td>\n";
		print "<td align=\"center\">$objp->reviews_rating</TD>\n";
		print "</TR>\n";
		$i++;
	}
	$dbosc->free();
}
else
{
	dol_print_error($dbosc);
}

print "</TABLE>";


$dbosc->close();

llxFooter();
?>
