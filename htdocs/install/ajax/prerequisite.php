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
 *       \file       htdocs/install/ajax/prerequisite.php
 *       \brief      File to get all prerequisites for install process
 */
require '../inc.php';

$default_lang = GETPOST('lang', 'alpha');
$langs->setDefaultLang($default_lang);

$langs->load("install");
$langs->load("errors");

$action = GETPOST('action', 'alpha');
$out = array();
$continue = true;
$memcached = false;

/*
 * View
 */

if ($action == 'check_prerequisite') {

	// Check PHP version
	if (versioncompare(versionphparray(), array(4, 3, 10)) < 0) {  // Minimum to use (error if lower)
		$out['php_version'] = '<span class="icon-warning icon-red">' . $langs->trans("ErrorPHPVersionTooLow", '4.3.10') . '</span>';
		$continue = false;
	} else if (versioncompare(versionphparray(), array(5, 2, 0)) < 0) {   // Minimum supported (warning if lower)
		$out['php_version'] = '<span class="icon-warning icon-red">' . $langs->trans("WarningPHPVersionTooLow", '5.2.0') . '</span>';
		$continue = false;
	} else {
		$out['php_version'] = '<span class="icon-tick icon-green">' . $langs->trans("PHPVersion") . " " . versiontostring(versionphparray()) . '</span>';
	}
	if (empty($force_install_nophpinfo))
		$out['php_version'] .= ' (<a href="phpinfo.php" target="_blank">' . $langs->trans("MoreInformation") . '</a>)';

	// Check memory
	$memrequiredorig = '64M';
	$memrequired = 64 * 1024 * 1024;
	$memmaxorig = @ini_get("memory_limit");
	$memmax = @ini_get("memory_limit");
	if ($memmaxorig != '') {
		preg_match('/([0-9]+)([a-zA-Z]*)/i', $memmax, $reg);
		if ($reg[2]) {
			if (strtoupper($reg[2]) == 'M')
				$memmax = $reg[1] * 1024 * 1024;
			if (strtoupper($reg[2]) == 'K')
				$memmax = $reg[1] * 1024;
		}
		if ($memmax >= $memrequired)
			$out['php_memory'] = '<span class="icon-tick icon-green">' . $langs->trans("PHPMemoryOK", $memmaxorig) . '</span>';
		else {
			$out['php_memory'] = '<span class="icon-warning icon-red">' . $langs->trans("PHPMemoryTooLow", $memmaxorig, $memrequiredorig) . '</span>';
			$continue = false;
		}
	}

	// Check if GD supported
	if (!function_exists("imagecreate")) {
		$out['php_gd'] = '<span class="icon-cross icon-red">' . $langs->trans("ErrorPHPDoesNotSupportGD") . '</span>';
		$continue = false;
	}
	else
		$out['php_gd'] = '<span class="icon-tick icon-green">' . $langs->trans("PHPSupportGD") . '</span>';

	// Check if curl supported
	if (!function_exists("curl_version")) {
		$out['php_curl'] = '<span class="icon-cross icon-red">' . $langs->trans("ErrorPHPDoesNotSupportCurl") . '</span>';
		$continue = false;
	}
	else
		$out['php_curl'] = '<span class="icon-tick icon-green">' . $langs->trans("PHPSupportCurl") . '</span>';

	// Check if memcache or memcached supported
	if (!class_exists('Memcache') && !class_exists('Memcached')) {
		$out['php_memcached'] = $langs->trans("ErrorPHPDoesNotSupportMemcached");
		$out['php_memcached'].= ' <span class="info-spot on-top"><span class="icon-info-round"></span><span class="info-bubble blue-bg">' . $langs->trans("MemcachedDescription") . '</span></span>';
	} else {
		$out['php_memcached'] = '<span class="icon-tick icon-green">' . $langs->trans("PHPSupportMemcached") . '</span>';
		$memcached = true;
	}

	// Check config file
	if (!file_exists($conffile)) {
		$out['conf_file'] = '<span class="icon-warning icon-red">' . $langs->trans("ConfFileDoesNotExistsAndCouldNotBeCreated", $conffiletoshow);
		$out['conf_file'].= '<br>' . $langs->trans("YouMustCreateWithPermission", $conffiletoshow) . '</span>';
		$continue = false;
	} else {
		// File exists
		$out['conf_file'] = '<span class="icon-tick icon-green">' . $langs->trans("ConfFileExists", $conffiletoshow) . '</span>';

		// File is not editable
		if (!is_writable($conffile)) {
			$out['conf_file'].= '<br><span class="icon-warning icon-red">' . $langs->trans("ConfFileIsNotWritable", $conffiletoshow) . '</span>';
			$continue = false;
		} else {
			// File is editable
			$out['conf_file'].= '<br><span class="icon-tick icon-green">' . $langs->trans("ConfFileIsWritable", $conffiletoshow) . '</span>';
		}
	}

	// Check for couchdb /db proxy_pass and check couchdb version
	$couch = new couchClient($scheme . "://" . $serverport . '/db/', '_users');

	try {
		$result = $couch->getVersion();
		$server_found = true;
	} catch (Exception $e) {
		$server_found = false;
		error_log($e->getMessage());
	}

	if (!$server_found) {
		$out['couchdb_rewrite'] = $langs->trans("ErrorCouchDBNotUseProxyPass");
		$out['couchdb_rewrite'].= ' <span class="info-spot on-top"><span class="icon-info-round"></span><span class="info-bubble blue-bg">' . $langs->trans("CouchDBProxyPassDescription") . '</span></span>';
		$continue = false;
	} else {
		if ($result->version >= '1.2.1') {
			$out['couchdb_rewrite'] = '<span class="icon-tick icon-green">' . $langs->trans("CouchdbVersion", $result->version) . '</span>';
		} else {
			$out['couchdb_rewrite'] = '<span class="icon-warning icon-red">' . $langs->trans("ErrorCouchdbVersion", $result->version) . '</span>';
			$continue = false;
		}
	}

	// Check if all results are ok
	//$memcached = false; // for debug
	$out['memcached'] = $memcached;
	//$continue = false; // for debug
	$out['continue'] = $continue;

	echo json_encode($out);
}
?>