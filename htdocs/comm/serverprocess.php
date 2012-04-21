<?php

/* Copyright (C) 2012      Patrick Mary           <laube@hotmail.fr>
 * Copyright (C) 2012      Herve Prot             <herve.prot@symeos.com>
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
 * 	\file       htdocs/comm/serverprocess.php
 * 	\ingroup    commercial societe
 * 	\brief      load data to display
 * 	\version    $Id: serverprocess.php,v 1.6 2012/01/27 16:15:05 synry63 Exp $
 */
require_once("../main.inc.php");

/* get Type */
$type = $_GET['type'];
$pstcomm = $_GET['pstcomm'];
$search_sale = $_GET['search_sale'];

// start storing data

$output = array(
    "sEcho" => intval($_GET['sEcho']),
    "iTotalRecords" => 0,
    "iTotalDisplayRecords" => 0,
    "aaData" => array()
);

$result = $couch->limit(1000)->getView('societe','list');


//print_r($result);
//exit;
$iTotal=  count($result->rows);
$output["iTotalRecords"]=$iTotal;
$output["iTotalDisplayRecords"]=$iTotal;


foreach($result->rows AS $aRow) {
    if(!isset($aRow->value->commerciaux))
        $aRow->value->commerciaux=null;
     if(!isset($aRow->value->category))
        $aRow->value->category=null;
    unset($aRow->value->class);
    unset($aRow->value->_rev);
    $output["aaData"][]=$aRow->value;
    unset($aRow);
}

header('Content-type: application/json');
echo json_encode($output);
?>