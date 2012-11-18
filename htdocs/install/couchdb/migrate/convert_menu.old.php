<?php

/* Copyright (C) 2012      Patrick Mary           <laube@hotmail.fr>
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
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/menubase.class.php");
;
$langs->load("companies");
$langs->load("customers");
$langs->load("suppliers");
$langs->load("commercial");
/* Array of database columns which should be read and sent back to DataTables. Use a space where
 * you want to insert a non-database field (for example a counter or static image)
 */

$couchdb = new couchClient("http://" . "Administrator:admin@" . substr($conf->Couchdb->host, 7) . ':' . $conf->Couchdb->port . '/', $conf->Couchdb->name);


$flush = $_GET["flush"];
if ($flush) {
    // reset old value
    $result = $couchdb->limit(50000)->getView('MenuTop', 'target_id');
    $i = 0;

    if (count($result->rows) == 0) {
        print "Effacement terminé";
        exit;
    }

    foreach ($result->rows AS $aRow) {
        $obj[$i]->_id = $aRow->value->_id;
        $obj[$i]->_rev = $aRow->value->_rev;
        $i++;
    }

    try {
        $couchdb->deleteDocs($obj);
    } catch (Exception $e) {
        echo "Something weird happened: " . $e->getMessage() . " (errcode=" . $e->getCode() . ")\n";
        exit(1);
    }

    print "Effacement en cours";
    exit;
}


/* basic companies request query */
$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "menu WHERE menu_handler='auguria' ORDER BY rowid";


$result = $db->query($sql);

$i = 0;

$aRow = new Menubase($db);

while ($aRow = $db->fetch_object($result)) {

    //print_r($aRow);

    unset($aRow->menu_handler);
    unset($aRow->entity);
    $rowid = (int) $aRow->rowid;
    unset($aRow->rowid);
    $fk_menu = (int) $aRow->fk_menu;
    unset($aRow->fk_menu);
    $level = (int) $aRow->level;
    //unset($aRow->level);
    unset($aRow->fk_leftmenu);
    unset($aRow->fk_mainmenu);
    unset($aRow->target);
    $aRow->tms = dol_now();
    $aRow->title = $aRow->titre;
    unset($aRow->titre);
    $aRow->position = (int) $aRow->position;
    $aRow->usertype = (int) $aRow->usertype;

    $tabperefils[$rowid] = $fk_menu;

    $pos1 = strpos($aRow->url, "mainmenu");
    $pos2 = strpos($aRow->url, "&");

    //print $aRow->url."</br>";
    if ($pos1 > 0) {
        if ($pos2 > 0 && $pos2 > $pos1) { // retire mainmenu= in url
            $url = substr($aRow->url, 0, $pos1);
            $url.= substr($aRow->url, $pos2 + 5); // supprimer le &
            $aRow->url = $url;
        } else {
            if ($pos2 > 0)
                $aRow->url = substr($aRow->url, 0, $pos2 - 5);
            else
                $aRow->url = substr($aRow->url, 0, $pos1 - 1);
        }
    }

    $pos1 = strpos($aRow->url, "leftmenu");
    $pos2 = strpos($aRow->url, "&");

    if ($pos1 > 0) {
        if ($pos2 > 0 && $pos2 > $pos1) { // retire leftmenu= in url
            $url = substr($aRow->url, 0, $pos1);
            $url.= substr($aRow->url, $pos2 + 5); // supprimer le &
            $aRow->url = $url;
            //print $url."toto</br>";
        } else
        if ($pos2 > 0)
            $aRow->url = substr($aRow->url, 0, $pos1 - 5);
        else
            $aRow->url = substr($aRow->url, 0, $pos1 - 1);
    }

    //print $aRow->url."</br>";

    if ($aRow->type == "top") {
        $aRow->class = "menu";
        //unset($aRow->type);
        unset($aRow->leftmenu);
        $name = "menu:" . $aRow->mainmenu;
        unset($aRow->mainmenu);
        $obj[$name] = $aRow;
        $obj[$name]->_id = $name;
    } else {
        $aRow->class = "menu";
        $name = "menu:" . strtolower($aRow->module) . strtolower($aRow->title);
        $pos = strpos($name, "|");

        if ($pos != false) {
            $name = substr($name, 0, $pos);
        }

        if ($tabinsert[$name]) {
            $name = is_uniq($tabinsert, $name, 0); // Ajoute 1 en cas de doublons
        }

        unset($aRow->type);
        unset($aRow->leftmenu);
        unset($aRow->mainmenu);


        $obj[$name] = $aRow;

        // Add father
        $obj[$name]->fk_menu = $tabname[$fk_menu];
        $obj[$name]->_id = $name;

        //$obj[$tabname[$fk_menu]]->submenu[$name] = $aRow;
        //uasort($obj[$tabname[$fk_menu]]->submenu,array("Menubase","compare")); // suivant position
    }
    /* else if($level==1)
      {
      unset($aRow->type);
      unset($aRow->leftmenu);
      unset($aRow->mainmenu);
      unset($aRow->tms);
      $obj[$tabname[$tabperefils[$fk_menu]]]->submenu[$tabname[$fk_menu]]->submenu[$name] = $aRow;
      //uasort($obj[$tabname[$tabperefils[$fk_menu]]]->submenu[$tabname[$fk_menu]]->submenu,array("Menubase","compare"));
      }
      else
      {
      unset($aRow->type);
      unset($aRow->leftmenu);
      unset($aRow->mainmenu);
      unset($aRow->tms);
      $obj[$tabname[$tabperefils[$tabperefils[$fk_menu]]]]->submenu[$tabname[$tabperefils[$fk_menu]]]->submenu[$tabname[$fk_menu]]->submenu[$name] = $aRow;
      //uasort($obj[$tabname[$tabperefils[$tabperefils[$fk_menu]]]]->submenu[$tabname[$tabperefils[$fk_menu]]]->submenu[$tabname[$fk_menu]]->submenu,array("Menubase","compare"));
      } */

    $tabname[$rowid] = $name;
    $tabinsert[$name] = true;

    $i++;
}
$db->free($result);
unset($result);

$result = $couchdb->limit(50000)->getView('MenuTop', 'target_id');

foreach ($result->rows as $key => $aRow) {
    $obj[$aRow->value->_id]->_rev = $aRow->value->_rev;
}

//print_r($obj);
//exit;

try {
    $couchdb->clean($obj);
    print_r($couchdb->storeDocs($obj, false));
} catch (Exception $e) {
    $error = "Something weird happened: " . $e->getMessage() . " (errcode=" . $e->getCode() . ")\n";
    dol_print_error("", $error);
    exit(1);
}

function is_uniq(&$tabinsert, $name, $level) {
    if ($tabinsert[$name . $level]) {
        $level++;
        return is_uniq($tabinsert, $name, $level);
    }
    else
        return $name . strval($level);
}

?>