<?php

/* Copyright (C) 2013 Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2013 Herve Prot		<herve.prot@symeos.com>
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

/**
 *       \file       htdocs/install/ajax/install.php
 *       \brief      File to get all status for install process
 */
require '../inc.php';

$default_lang = GETPOST('lang', 'alpha');
$langs->setDefaultLang($default_lang);

$langs->load("install");
$langs->load("errors");

$action = GETPOST('action', 'alpha');
$out = array();


/*
 * View
 */

header('Content-type: application/json');

// This variable are loaded by inc.php
// $main_couchdb_host
// $main_couchdb_port
// Create config file
if ($action == 'create_config') {
	$couchdb_host = GETPOST('couchdb_host', 'alpha');
	$couchdb_port = GETPOST('couchdb_port', 'int');
	$memcached_host = GETPOST('memcached_host', 'alpha');
	$memcached_port = GETPOST('memcached_port', 'int');
	// Save old conf file on disk
	if (file_exists("$conffile")) {
		// We must ignore errors as an existing old file may already exists and not be replacable or
		// the installer (like for ubuntu) may not have permission to create another file than conf.php.
		// Also no other process must be able to read file or we expose the new file, so content with password.
		@dol_copy($conffile, $conffile . '.old', '0600');
	}
	$ret = write_conf_file();
	if ($ret > 0)
		echo json_encode(array('status' => 'ok', 'value' => $langs->trans('ConfFileCreated')));
	else
		echo json_encode(array('status' => 'error', 'value' => $langs->trans('ConfFileIsNotWritable', $conffile)));

// Create sync user
} else if ($action == 'create_syncuser') {
	$couchdb_user_sync = GETPOST('couchdb_user_sync', 'alpha');
	$couchdb_pass_sync = GETPOST('couchdb_pass_sync', 'alpha');
	// $main_couchdb_host
	// $main_couchdb_port

	sleep(1); // for test
	echo json_encode(array('status' => 'ok'));

// Create database
} else if ($action == 'create_database') {
	$couchdb_name = GETPOST('couchdb_name', 'alpha');
	$couch = new couchClient($main_couchdb_host . ':' . $main_couchdb_port . '/', $couchdb_name);

	if (!$couch->databaseExists()) {
		try {
			$couch->createDatabase();
			echo json_encode(array('status' => 'ok', 'value' => $langs->trans('DatabaseCreated')));
		} catch (Exception $e) {
			echo json_encode(array('status' => 'error', 'value' => $e->getMessage()));
			error_log($e->getMessage());
		}
	} else {
		echo json_encode(array('status' => 'ok', 'value' => $langs->trans('DatabaseAlreadyExists'))); // database already exists
	}

// Populate database
} else if ($action == 'populate_database') {
	$filename = GETPOST('filename', 'alpha');
	$filepath = GETPOST('filepath');

	$fp = fopen($filepath, "r");
	if ($fp) {
		$json = fread($fp, filesize($filepath));
		$obj = json_decode($json);
		unset($obj->_rev);
		if ($obj->_id == "const")
			unset($obj->MAIN_VERSION);

		$couchdb_name = GETPOST('couchdb_name', 'alpha');
		$couch = new couchClient($main_couchdb_host . ':' . $main_couchdb_port . '/', $couchdb_name);

		$couch->storeDoc($obj);

		echo json_encode(array('status' => 'ok'));
	} else {
		echo json_encode(array('status' => 'error'));
		error_log("file not found : " . $filepath);
	}

	// Create superadmin
} else if ($action == 'create_admin') {

	$couchdb_name = GETPOST('couchdb_name', 'alpha');
	$couchdb_user_root = GETPOST('couchdb_user_root', 'alpha');
	$couchdb_pass_root = GETPOST('couchdb_pass_root', 'alpha');

	$couch = new couchClient($main_couchdb_host . ':' . $main_couchdb_port . '/', $couchdb_name);
	$admin = new couchAdmin($couch);

	try {
		// create a temporary admin user
		$admin->createAdmin("admin_install", "admin_install");
	} catch (Exception $e) {
		// already exist or protected couchdb server
	}

	$host = substr($main_couchdb_host, 7);

	try {
		$couch = new couchClient('http://admin_install:admin_install@' . $host . ':' . $main_couchdb_port . '/', $couchdb_name, array("cookie_auth" => TRUE));
	} catch (Exception $e) {
		error_log($e->getMessage());
		echo json_encode(array('status' => 'error', 'value' => $e->getMessage()));
	}

	// create admin in login database
	try {
		$useradmin = new UserAdmin($db);

		$useradmin->Lastname = "Admin";
		$useradmin->Firstname = "Admin";
		$useradmin->name = trim($couchdb_user_root);
		$useradmin->pass = trim($couchdb_pass_root);
		$useradmin->entity = $couchdb_name;
		$useradmin->admin = true;
		$useradmin->Status = 'ENABLE';

		$id = $useradmin->update('', 'add');
		if ($id < 0)
			error_log($id);
	} catch (Exception $e) {
		echo json_encode(array('status' => 'error', 'value' => $e->getMessage()));
	}

	// create admib in couchdb_name database
	$edituser = new User($db);

	$found = false;
	try {
		$edituser->load("user:admin");
		$found = true;
	} catch (Exception $e) {
		// user not exit
	}

	if (!$found)
		try {
			$edituser->Lastname = "Admin";
			$edituser->Firstname = "Admin";
			$edituser->name = "admin";
			$edituser->admin = true;
			$edituser->email = trim($couchdb_user_root);
			$edituser->Status = 'ENABLE';

			$id = $edituser->update("", 0, "add");
		} catch (Exception $e) {
			echo json_encode(array('status' => 'error', 'value' => $e->getMessage()));
		}

	echo json_encode(array('status' => 'ok'));

	// Create first user
} else if ($action == 'create_user') {

	$couchdb_name = GETPOST('couchdb_name', 'alpha');
	$couchdb_user_firstname = GETPOST('couchdb_user_firstname', 'alpha');
	$couchdb_user_lastname = GETPOST('couchdb_user_lastname', 'alpha');
	$couchdb_user_pseudo = GETPOST('couchdb_user_pseudo', 'alpha');
	$couchdb_user_email = GETPOST('couchdb_user_email', 'alpha');
	$couchdb_user_pass = GETPOST('couchdb_user_pass', 'alpha');

	$host = substr($main_couchdb_host, 7);

	try {
		$couch = new couchClient('http://admin_install:admin_install@' . $host . ':' . $main_couchdb_port . '/', $couchdb_name, array("cookie_auth" => TRUE));
	} catch (Exception $e) {
		error_log($e->getMessage());
		echo json_encode(array('status' => 'error', 'value' => $e->getMessage()));
	}

	// create user in login database
	try {
		$useradmin = new UserAdmin($db);

		$useradmin->Lastname = trim($couchdb_user_lastname);
		$useradmin->Firstname = trim($couchdb_user_firstname);
		$useradmin->name = trim($couchdb_user_email);
		$useradmin->pass = trim($couchdb_user_pass);
		$useradmin->entity = $couchdb_name;
		$useradmin->admin = false;
		$useradmin->Status = 'DISABLE';

		$id = $useradmin->update('', 'add');
	} catch (Exception $e) {
		echo json_encode(array('status' => 'error', 'value' => $e->getMessage()));
	}

	// create user in couchdb_name database
	$edituser = new User($db);
	$found = false;
	try {
		$edituser->load("user:" . trim($couchdb_user_pseudo));
		$found = true;
	} catch (Exception $e) {
		// user not exit
	}

	if (!$found)
		try {
			$edituser->Lastname = trim($couchdb_user_lastname);
			$edituser->Firstname = trim($couchdb_user_firstname);
			$edituser->name = trim($couchdb_user_pseudo);
			$edituser->admin = false;
			$edituser->email = trim($couchdb_user_email);

			$id = $edituser->update("", 0, "add");
		} catch (Exception $e) {
			echo json_encode(array('status' => 'error', 'value' => $e->getMessage()));
		}


	// Add fisrt user to the database for security database
	$admin = new couchAdmin($couch);
	$admin->addDatabaseReaderUser(trim($couchdb_user_email));

	//remove admin_install
	try {
		// delete temporary admin user
		$admin->deleteAdmin("admin_install");
	} catch (Exception $e) {
		echo json_encode(array('status' => 'error', 'value' => $e->getMessage()));
	}

	echo json_encode(array('status' => 'ok'));

	// Install is finished, we create the lock file
} else if ($action == 'lock_install') {
	//$ret = write_lock_file();
	$ret = 1; // TODO for debug
	if ($ret > 0)
		echo json_encode(array('status' => 'ok', 'value' => $langs->trans('LockFileCreated')));
	else
		echo json_encode(array('status' => 'error', 'value' => $langs->trans('LockFileCouldNotBeCreated')));
}
?>