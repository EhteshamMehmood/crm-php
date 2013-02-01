<?php

/* Copyright (C) 2009-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *  \file		htdocs/core/lib/memory.lib.php
 *  \brief		Set of function for memory/cache management
 */
global $shmkeys, $shmoffset;

$shmkeys = array('main' => 1, 'admin' => 2, 'dict' => 3, 'companies' => 4, 'suppliers' => 5, 'products' => 6,
    'commercial' => 7, 'compta' => 8, 'projects' => 9, 'cashdesk' => 10, 'agenda' => 11, 'bills' => 12,
    'propal' => 13, 'boxes' => 14, 'banks' => 15, 'other' => 16, 'errors' => 17, 'members' => 18, 'ecm' => 19,
    'orders' => 20, 'users' => 21, 'help' => 22, 'stocks' => 23, 'interventions' => 24,
    'donations' => 25, 'contracts' => 26);
$shmoffset = 100;

/**
 * 	Save data into a memory area shared by all users, all sessions on server
 *
 *  @param	string      $memoryid		Memory id of shared area
 * 	@param	string		$data			Data to save
 * 	@return	int							<0 if KO, Nb of bytes written if OK
 */
function dol_setcache($memoryid, $data) {
    global $conf, $memcache;
    $result = -1;

    $memoryid = $conf->Couchdb->name . '.' . $memoryid; // For multi-entity

    // Using a memcached server
    if ($conf->memcached->enabled && get_class($memcache) == 'Memcached') {
        $memoryid = session_name() . '_' . $memoryid;
        $memcache->set($memoryid, $data, 3600); // This fails if key already exists
        $rescode = $memcache->getResultCode();
        if ($rescode == 0) {
            return count($data);
        } else {
            return -$rescode;
        }
    } else if ($conf->memcached->enabled && get_class($memcache) == 'Memcache') {
        $memoryid = session_name() . '_' . $memoryid;
        $result = $memcache->set($memoryid, $data); // This fails if key already exists
        if ($result) {
            return count($data);
        } else {
            return -1;
        }
    }
    // Using shmop
    else if (isset($conf->global->MAIN_OPTIMIZE_SPEED) && ($conf->global->MAIN_OPTIMIZE_SPEED & 0x02)) {
        $result = dol_setshmop($memoryid, $data);
    }
    // Using SESSION
    else {
        $_SESSION[$memoryid] = $data;
        $result = 1;
    }


    return $result;
}

/**
 * 	Read a memory area shared by all users, all sessions on server
 *
 *  @param	string	$memoryid		Memory id of shared area
 * 	@return	int						<0 if KO, data if OK
 */
function dol_getcache($memoryid) {
    global $conf, $memcache;

    $memoryid = $conf->Couchdb->name . '.' . $memoryid; // For multi-entity
    // Using a memcached server
    if ($conf->memcached->enabled && get_class($memcache) == 'Memcached') {
        $memoryid = session_name() . '_' . $memoryid;
        $data = $memcache->get($memoryid);
        $rescode = $memcache->getResultCode();
        if ($rescode == 0) {
            return $data;
        } else {
            return -$rescode;
        }
    } else if ($conf->memcached->enabled && get_class($memcache) == 'Memcache') {
        $memoryid = session_name() . '_' . $memoryid;
        $data = $memcache->get($memoryid);
        if ($data) {
            return $data;
        } else {
            return -1;
        }
    }
    // Using shmop
    else if (isset($conf->global->MAIN_OPTIMIZE_SPEED) && ($conf->global->MAIN_OPTIMIZE_SPEED & 0x02)) {
        $data = dol_getshmop($memoryid);
        return $data;
    }
    // Using SESSION
    else {
        return $_SESSION[$memoryid];
    }

    return 0;
}

/**
 * 	delete a data into a memory area shared by all users, all sessions on server
 *
 *  @param	string      $memoryid		Memory id of shared area
 * 	@return	int							<0 if KO, Nb of bytes written if OK
 */
function dol_delcache($memoryid) {
    global $conf, $memcache;
    $result = -1;

    $memoryid = $conf->Couchdb->name . '.' . $memoryid; // For multi-entity

    // Using a memcached server
    if ($conf->memcached->enabled && get_class($memcache) == 'Memcached') {
        $memoryid = session_name() . '_' . $memoryid;
        $memcache->delete($memoryid); // This fails if key already exists
        $rescode = $memcache->getResultCode();
        if ($rescode == 0) {
            return 1;
        } else {
            return -$rescode;
        }
    } else if ($conf->memcached->enabled && get_class($memcache) == 'Memcache') {
        $memoryid = session_name() . '_' . $memoryid;
        $result = $memcache->delete($memoryid); // This fails if key already exists
        if ($result) {
            return 1;
        } else {
            return -1;
        }
    }
    /* // Using shmop
      else if (isset($conf->global->MAIN_OPTIMIZE_SPEED) && ($conf->global->MAIN_OPTIMIZE_SPEED & 0x02))
      {
      $result=dol_setshmop($memoryid,$data);
      } */
    // Using SESSION
    else {
        unset($_SESSION[$memoryid]);
        return 1;
    }

    return $result;
}

/**
 * 	delete all data
 *
 * 	@return	int							<0 if KO, Nb of bytes written if OK
 */
function dol_flushcache() {
    global $conf, $memcache;
    $result = -1;

    // Using a memcached server
    if ($conf->memcached->enabled && get_class($memcache) == 'Memcached') {
        $memoryid = session_name() . '_' . $memoryid;
        $memcache->flush(); // This fails if key already exists
        $rescode = $memcache->getResultCode();
        if ($rescode == 0) {
            return 1;
        } else {
            return -$rescode;
        }
    } else if ($conf->memcached->enabled && get_class($memcache) == 'Memcache') {
        $memoryid = session_name() . '_' . $memoryid;
        $result = $memcache->flush(); // This fails if key already exists
        if ($result) {
            return 1;
        } else {
            return -1;
        }
    }
    /* // Using shmop
      else if (isset($conf->global->MAIN_OPTIMIZE_SPEED) && ($conf->global->MAIN_OPTIMIZE_SPEED & 0x02))
      {
      $result=dol_setshmop($memoryid,$data);
      } */
    // SESSION
    else {

        session_destroy();
    }

    return $result;
}

/**
 * 	Return shared memory address used to store dataset with key memoryid
 *
 *  @param	string	$memoryid		Memory id of shared area
 * 	@return	int						<0 if KO, Memoy address of shared memory for key
 */
function dol_getshmopaddress($memoryid) {
    global $shmkeys, $shmoffset;
    if (empty($shmkeys[$memoryid]))
        return 0;
    return $shmkeys[$memoryid] + $shmoffset;
}

/**
 * 	Return list of contents of all memory area shared
 *
 * 	@return	int						0=Nothing is done, <0 if KO, >0 if OK
 */
function dol_listshmop() {
    global $shmkeys, $shmoffset;

    $resarray = array();
    foreach ($shmkeys as $key => $val) {
        $result = dol_getshmop($key);
        if (!is_numeric($result) || $result > 0)
            $resarray[$key] = $result;
    }
    return $resarray;
}

/**
 * 	Save data into a memory area shared by all users, all sessions on server
 *
 *  @param	int		$memoryid		Memory id of shared area
 * 	@param	string	$data			Data to save
 * 	@return	int						<0 if KO, Nb of bytes written if OK
 */
function dol_setshmop($memoryid, $data) {
    global $shmkeys, $shmoffset;

    //print 'dol_setshmop memoryid='.$memoryid."<br>\n";
    if (empty($shmkeys[$memoryid]) || !function_exists("shmop_write"))
        return 0;
    $shmkey = dol_getshmopaddress($memoryid);
    $newdata = serialize($data);
    $size = strlen($newdata);
    //print 'dol_setshmop memoryid='.$memoryid." shmkey=".$shmkey." newdata=".$size."bytes<br>\n";
    $handle = shmop_open($shmkey, 'c', 0644, 6 + $size);
    if ($handle) {
        $shm_bytes_written1 = shmop_write($handle, str_pad($size, 6), 0);
        $shm_bytes_written2 = shmop_write($handle, $newdata, 6);
        if (($shm_bytes_written1 + $shm_bytes_written2) != (6 + dol_strlen($newdata))) {
            print "Couldn't write the entire length of data\n";
        }
        shmop_close($handle);
        return ($shm_bytes_written1 + $shm_bytes_written2);
    } else {
        print 'Error in shmop_open for memoryid=' . $memoryid . ' shmkey=' . $shmkey . ' 6+size=6+' . $size;
        return -1;
    }
}

/**
 * 	Read a memory area shared by all users, all sessions on server
 *
 *  @param	string	$memoryid		Memory id of shared area
 * 	@return	int						<0 if KO, data if OK
 */
function dol_getshmop($memoryid) {
    global $shmkeys, $shmoffset;

    if (empty($shmkeys[$memoryid]) || !function_exists("shmop_open"))
        return 0;
    $shmkey = dol_getshmopaddress($memoryid);
    ;
    //print 'dol_getshmop memoryid='.$memoryid." shmkey=".$shmkey."<br>\n";
    $handle = @shmop_open($shmkey, 'a', 0, 0);
    if ($handle) {
        $size = trim(shmop_read($handle, 0, 6));
        if ($size)
            $data = unserialize(shmop_read($handle, 6, $size));
        else
            return -1;
        shmop_close($handle);
    }
    else {
        return -2;
    }
    return $data;
}

?>
