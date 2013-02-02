<?php

/* Copyright (C) 2004		Rodolphe Quiedeville            <rodolphe@quiedeville.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2004		Sebastien DiCintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2007-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Herve Prot                      <herve.prot@symeos.com>
 * Copyright (C) 2012           Marcos García
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

// Just to define version DOL_VERSION
if (!defined('DOL_INC_FOR_VERSION_ERROR'))
    define('DOL_INC_FOR_VERSION_ERROR', '1');
require_once '../filefunc.inc.php';


// Define DOL_DOCUMENT_ROOT and ADODB_PATH used for install/upgrade process
if (!defined('DOL_DOCUMENT_ROOT'))
    define('DOL_DOCUMENT_ROOT', '..');
if (!defined('ADODB_PATH')) {
    $foundpath = DOL_DOCUMENT_ROOT . '/includes/adodbtime/';
    if (!is_dir($foundpath))
        $foundpath = '/usr/share/php/adodb/';
    define('ADODB_PATH', $foundpath);
}

require_once DOL_DOCUMENT_ROOT . '/core/class/translatestandalone.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once ADODB_PATH . 'adodb-time.inc.php';

// For couchdb
if (!class_exists('couch'))
	require DOL_DOCUMENT_ROOT . '/core/db/couchdb/lib/couch.php';
if (!class_exists('couchClient'))
	require DOL_DOCUMENT_ROOT . '/core/db/couchdb/lib/couchClient.php';
if (!class_exists('nosqlDocument'))
	require DOL_DOCUMENT_ROOT . '/core/class/nosqlDocument.class.php';


// Avoid warnings with strict mode E_STRICT
$conf = new stdClass(); // instantiate $conf explicitely
$conf->global = new stdClass();
$conf->file = new stdClass();
$conf->db = new stdClass();
$conf->syslog = new stdClass();

// Force $_REQUEST["logtohtml"]
$_REQUEST["logtohtml"] = 1;

// Correction PHP_SELF (ex pour apache via caudium) car PHP_SELF doit valoir URL relative
// et non path absolu.
if (isset($_SERVER["DOCUMENT_URI"]) && $_SERVER["DOCUMENT_URI"]) {
    $_SERVER["PHP_SELF"] = $_SERVER["DOCUMENT_URI"];
}


$includeconferror = '';


// Define vars
$conffiletoshowshort = "conf.php";
// Define localization of conf file
$conffile = "../conf/conf.php";
$conffiletoshow = "htdocs/conf/conf.php";
// For debian/redhat like systems
//$conffile = "/etc/dolibarr/conf.php";
//$conffiletoshow = "/etc/dolibarr/conf.php";


if (!defined('DONOTLOADCONF') && file_exists($conffile)) {
    $result = include_once $conffile; // Load conf file
    if ($result) {
        if (empty($dolibarr_main_db_type))
            $dolibarr_main_db_type = 'mysql'; // For backward compatibility

		// Clean parameters
        $dolibarr_main_data_root = isset($dolibarr_main_data_root) ? trim($dolibarr_main_data_root) : '';
        $dolibarr_main_url_root = isset($dolibarr_main_url_root) ? trim($dolibarr_main_url_root) : '';
        $dolibarr_main_url_root_alt = isset($dolibarr_main_url_root_alt) ? trim($dolibarr_main_url_root_alt) : '';
        $dolibarr_main_document_root = isset($dolibarr_main_document_root) ? trim($dolibarr_main_document_root) : '';
        $dolibarr_main_document_root_alt = isset($dolibarr_main_document_root_alt) ? trim($dolibarr_main_document_root_alt) : '';

        // Remove last / or \ on directories or url value
        if (!empty($dolibarr_main_document_root) && !preg_match('/^[\\/]+$/', $dolibarr_main_document_root))
            $dolibarr_main_document_root = preg_replace('/[\\/]+$/', '', $dolibarr_main_document_root);
        if (!empty($dolibarr_main_url_root) && !preg_match('/^[\\/]+$/', $dolibarr_main_url_root))
            $dolibarr_main_url_root = preg_replace('/[\\/]+$/', '', $dolibarr_main_url_root);
        if (!empty($dolibarr_main_data_root) && !preg_match('/^[\\/]+$/', $dolibarr_main_data_root))
            $dolibarr_main_data_root = preg_replace('/[\\/]+$/', '', $dolibarr_main_data_root);
        if (!empty($dolibarr_main_document_root_alt) && !preg_match('/^[\\/]+$/', $dolibarr_main_document_root_alt))
            $dolibarr_main_document_root_alt = preg_replace('/[\\/]+$/', '', $dolibarr_main_document_root_alt);
        if (!empty($dolibarr_main_url_root_alt) && !preg_match('/^[\\/]+$/', $dolibarr_main_url_root_alt))
            $dolibarr_main_url_root_alt = preg_replace('/[\\/]+$/', '', $dolibarr_main_url_root_alt);

        // Create conf object
        if (!empty($dolibarr_main_document_root)) {
            $result = conf($dolibarr_main_document_root);
        }
        // Load database driver
        if ($result) {
            if (!empty($dolibarr_main_document_root) && !empty($dolibarr_main_db_type)) {
                $result = include_once $dolibarr_main_document_root . "/core/db/" . $dolibarr_main_db_type . '.class.php';
                if (!$result) {
                    $includeconferror = 'ErrorBadValueForDolibarrMainDBType';
                }
            }
        } else {
            $includeconferror = 'ErrorBadValueForDolibarrMainDocumentRoot';
        }
    } else {
        $includeconferror = 'ErrorBadFormatForConfFile';
    }
}
$conf->global->MAIN_LOGTOHTML = 1;

// Define prefix
if (!isset($dolibarr_main_db_prefix) || !$dolibarr_main_db_prefix)
    $dolibarr_main_db_prefix = 'llx_';
define('MAIN_DB_PREFIX', (isset($dolibarr_main_db_prefix) ? $dolibarr_main_db_prefix : ''));

define('DOL_CLASS_PATH', 'class/');                             // Filsystem path to class dir
define('DOL_DATA_ROOT', (isset($dolibarr_main_data_root) ? $dolibarr_main_data_root : ''));
if (!empty($dolibarr_main_document_root_alt)) {
    define('DOL_DOCUMENT_ROOT_ALT', $dolibarr_main_document_root_alt); // Filesystem paths to alternate core php (alternate htdocs)
}
define('DOL_MAIN_URL_ROOT', (isset($dolibarr_main_url_root) ? $dolibarr_main_url_root : ''));           // URL relative root
$uri = preg_replace('/^http(s?):\/\//i', '', constant('DOL_MAIN_URL_ROOT'));  // $uri contains url without http*
$suburi = strstr($uri, '/');       // $suburi contains url without domain
if ($suburi == '/')
    $suburi = '';   // If $suburi is /, it is now ''
define('DOL_URL_ROOT', $suburi);    // URL relative root ('', '/dolibarr', ...)


if (empty($conf->file->character_set_client))
    $conf->file->character_set_client = "UTF-8";
if (empty($conf->db->character_set))
    $conf->db->character_set = 'utf8';
if (empty($conf->db->dolibarr_main_db_collation))
    $conf->db->dolibarr_main_db_collation = 'utf8_general_ci';
if (empty($conf->db->dolibarr_main_db_encryption))
    $conf->db->dolibarr_main_db_encryption = 0;
if (empty($conf->db->dolibarr_main_db_cryptkey))
    $conf->db->dolibarr_main_db_cryptkey = '';
if (empty($conf->db->user))
    $conf->db->user = '';

// Define array of document root directories
$conf->file->dol_document_root = array(DOL_DOCUMENT_ROOT);
if (!empty($dolibarr_main_document_root_alt)) {
    // dolibarr_main_document_root_alt contains several directories
    $values = preg_split('/[;,]/', $dolibarr_main_document_root_alt);
    foreach ($values as $value) {
        $conf->file->dol_document_root[] = $value;
    }
}


// Security check
if (preg_match('/install.lock/i', $_SERVER["SCRIPT_FILENAME"])) {
    print 'Install pages have been disabled for security reason (directory renamed with .lock suffix).';
    if (!empty($dolibarr_main_url_root)) {
        print 'Click on following link. ';
        print '<a href="' . $dolibarr_main_url_root . '/admin/index.php?mainmenu=home&leftmenu=setup' . (isset($_POST["login"]) ? '&username=' . urlencode($_POST["login"]) : '') . '">';
        print 'Click here to go to Dolibarr';
        print '</a>';
    }
    exit;
}
$lockfile = DOL_DATA_ROOT . '/install.lock';
if (constant('DOL_DATA_ROOT') && file_exists($lockfile)) {
    print 'Install pages have been disabled for security reason (by lock file install.lock into dolibarr root directory).<br>';
    if (!empty($dolibarr_main_url_root)) {
        print 'Click on following link. ';
        print 'If you always reach this page, you must remove install.lock file manually.<br>';
        print '<a href="' . $dolibarr_main_url_root . '/admin/index.php?mainmenu=home&leftmenu=setup' . (isset($_POST["login"]) ? '&username=' . urlencode($_POST["login"]) : '') . '">';
        print 'Click here to go to Dolibarr';
        print '</a>';
    } else {
        print 'If you always reach this page, you must remove install.lock file manually.<br>';
    }
    exit;
}


// Force usage of log file for install and upgrades
$conf->syslog->enabled = 1;
$conf->global->SYSLOG_LEVEL = constant('LOG_DEBUG');
if (!defined('SYSLOG_FILE_ON'))
    define('SYSLOG_FILE_ON', 1);
if (!defined('SYSLOG_FILE')) { // To avoid warning on systems with constant already defined
    if (@is_writable('/tmp'))
        define('SYSLOG_FILE', '/tmp/dolibarr_install.log');
    else if (!empty($_ENV["TMP"]) && @is_writable($_ENV["TMP"]))
        define('SYSLOG_FILE', $_ENV["TMP"] . '/dolibarr_install.log');
    else if (!empty($_ENV["TEMP"]) && @is_writable($_ENV["TEMP"]))
        define('SYSLOG_FILE', $_ENV["TEMP"] . '/dolibarr_install.log');
    else if (@is_writable('../../../../') && @file_exists('../../../../startdoliwamp.bat'))
        define('SYSLOG_FILE', '../../../../dolibarr_install.log'); // For DoliWamp
    else if (@is_writable('../../'))
        define('SYSLOG_FILE', '../../dolibarr_install.log');    // For others

//print 'SYSLOG_FILE='.SYSLOG_FILE;exit;
}
if (!defined('SYSLOG_FILE_NO_ERROR'))
    define('SYSLOG_FILE_NO_ERROR', 1);

// Removed magic_quotes
if (function_exists('get_magic_quotes_gpc')) { // magic_quotes_* removed in PHP 5.4
    if (get_magic_quotes_gpc()) {

        // Forcing parameter setting magic_quotes_gpc and cleaning parameters
        // (Otherwise he would have for each position, condition
        // Reading stripslashes variable according to state get_magic_quotes_gpc).
        // Off mode (recommended, you just do $db->escape when an insert / update.
        function stripslashes_deep($value) {
            return (is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value));
        }

        $_GET = array_map('stripslashes_deep', $_GET);
        $_POST = array_map('stripslashes_deep', $_POST);
        $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
        $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
        @set_magic_quotes_runtime(0);
    }
}

// Defini objet langs
$langs = new TranslateStandalone(true);
if (GETPOST('lang'))
    $langs->setDefaultLang(GETPOST('lang'));
else
    $langs->setDefaultLang('auto');

$bc[false] = ' class="bg1"';
$bc[true] = ' class="bg2"';

/**
 * Load conf file (file must exists)
 *
 * @param	string		$dolibarr_main_document_root		Root directory of Dolibarr bin files
 * @return	int												<0 if KO, >0 if OK
 */
function conf($dolibarr_main_document_root) {
    global $conf, $couch;
    global $dolibarr_main_db_type;
    global $dolibarr_main_db_host;
    global $dolibarr_main_db_port;
    global $dolibarr_main_db_name;
    global $dolibarr_main_db_user;
    global $dolibarr_main_db_pass;
    global $character_set_client;

    global $dolibarr_main_couchdb_host;
    global $dolibarr_main_couchdb_port;
    global $dolibarr_main_couchdb_name;

    $return = include_once $dolibarr_main_document_root . '/core/class/conf.class.php';
    if (!$return)
        return -1;

    $conf = new Conf();
    $conf->db->type = trim($dolibarr_main_db_type);
    $conf->db->host = trim($dolibarr_main_db_host);
    $conf->db->port = trim($dolibarr_main_db_port);
    $conf->db->name = trim($dolibarr_main_db_name);
    $conf->db->user = trim($dolibarr_main_db_user);
    $conf->db->pass = trim($dolibarr_main_db_pass);

    // CouchDB
    $conf->Couchdb->host = trim($dolibarr_main_couchdb_host);
    $conf->Couchdb->port = trim($dolibarr_main_couchdb_port);
    $conf->Couchdb->name = trim($dolibarr_main_couchdb_name);

    $couch = new couchClient($conf->Couchdb->host . ':' . $conf->Couchdb->port . '/', $conf->Couchdb->name);
    $couch->setSessionCookie("AuthSession=" . $_COOKIE['AuthSession']);

    $conf->useDatabase();

    if (empty($character_set_client))
        $character_set_client = "UTF-8";
    $conf->file->character_set_client = strtoupper($character_set_client);
    if (empty($dolibarr_main_db_character_set))
        $dolibarr_main_db_character_set = ($conf->db->type == 'mysql' ? 'latin1' : '');  // Old installation
    $conf->db->character_set = $dolibarr_main_db_character_set;
    if (empty($dolibarr_main_db_collation))
        $dolibarr_main_db_collation = ($conf->db->type == 'mysql' ? 'latin1_swedish_ci' : '');  // Old installation
    $conf->db->dolibarr_main_db_collation = $dolibarr_main_db_collation;
    if (empty($dolibarr_main_db_encryption))
        $dolibarr_main_db_encryption = 0;
    $conf->db->dolibarr_main_db_encryption = $dolibarr_main_db_encryption;
    if (empty($dolibarr_main_db_cryptkey))
        $dolibarr_main_db_cryptkey = '';
    $conf->db->dolibarr_main_db_cryptkey = $dolibarr_main_db_cryptkey;

    // Force usage of log file for install and upgrades
    $conf->syslog->enabled = 1;
    $conf->global->SYSLOG_LEVEL = constant('LOG_DEBUG');
    if (!defined('SYSLOG_FILE_ON'))
        define('SYSLOG_FILE_ON', 1);
    if (!defined('SYSLOG_FILE')) { // To avoid warning on systems with constant already defined
        if (@is_writable('/tmp'))
            define('SYSLOG_FILE', '/tmp/dolibarr_install.log');
        else if (!empty($_ENV["TMP"]) && @is_writable($_ENV["TMP"]))
            define('SYSLOG_FILE', $_ENV["TMP"] . '/dolibarr_install.log');
        else if (!empty($_ENV["TEMP"]) && @is_writable($_ENV["TEMP"]))
            define('SYSLOG_FILE', $_ENV["TEMP"] . '/dolibarr_install.log');
        else if (@is_writable('../../../../') && @file_exists('../../../../startdoliwamp.bat'))
            define('SYSLOG_FILE', '../../../../dolibarr_install.log'); // For DoliWamp
        else if (@is_writable('../../'))
            define('SYSLOG_FILE', '../../dolibarr_install.log');    // For others






//print 'SYSLOG_FILE='.SYSLOG_FILE;exit;
    }
    if (!defined('SYSLOG_FILE_NO_ERROR'))
        define('SYSLOG_FILE_NO_ERROR', 1);

    return 1;
}

/**
 * Show HTML header of install pages
 *
 * @param	string		$subtitle			Title
 * @param 	string		$next				Next
 * @param 	string		$action    			Action code ('set' or 'upgrade')
 * @param 	string		$param				Param
 * @param	string		$forcejqueryurl		Set jquery relative URL (must end with / if defined)
 * @return	void
 */
function pHeader($subtitle, $next, $action = 'set', $param = '', $forcejqueryurl = '') {
    global $conf, $langs;
    $langs->load("main");
    $langs->load("admin");

    if ($forcejqueryurl) {
        $jQueryCustomPath = $forcejqueryurl;
        $jQueryUiCustomPath = $forcejqueryurl;
    } else {
        $jQueryCustomPath = (defined('JS_JQUERY') && constant('JS_JQUERY')) ? JS_JQUERY : false;
        $jQueryUiCustomPath = (defined('JS_JQUERY_UI') && constant('JS_JQUERY_UI')) ? JS_JQUERY_UI : false;
    }

    print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">' . "\n";
    print '<html>' . "\n";
    print '<head>' . "\n";
    print '<meta http-equiv="content-type" content="text/html; charset=' . $conf->file->character_set_client . '">' . "\n";
    print'<!-- Foundation framework -->';
    print '<link rel="stylesheet" href="../theme/pertho_sample/foundation/stylesheets/foundation.css">';
    print '<!-- jquery UI -->';
    print '<link rel="stylesheet" href="../theme/pertho_sample/lib/jQueryUI/css/Aristo/Aristo.css" media="all" />';
    print '<!-- fancybox -->';
    print '<link rel="stylesheet" href="../theme/pertho_sample/lib/fancybox/jquery.fancybox-1.3.4.css" media="all" />';
    print '<!-- tooltips -->';
    print '<link rel="stylesheet" href="../theme/pertho_sample/lib/qtip2/jquery.qtip.min.css" />';
    print '<!-- main styles -->';
    print '<link rel="stylesheet" href="../theme/eldy/style.css" />';
    print '<!-- datatables -->';
    print '<link rel="stylesheet" href="../theme/pertho_sample/lib/datatables/css/demo_table_jui.css" media="all" />';
    //print '<link rel="stylesheet" type="text/css" href="default.css">'."\n";
    //print '<link rel="stylesheet" type="text/css" href="../includes/jquery/css/smoothness/jquery-ui-latest.custom.css" type="text/css">'."\n";
    print '<script type="text/javascript" src="../includes/jquery/js/jquery-latest.min.js"></script>' . "\n";
    print '<script type="text/javascript" src="../includes/jquery/js/jquery-ui-latest.custom.min.js"></script>' . "\n";
    print '<title>' . $langs->trans("SpeedealingSetup") . '</title>' . "\n";
    print '<!-- Google fonts -->';
    //print '<link href="https://fonts.googleapis.com/css?family=Open+Sans+Condensed:300" rel="stylesheet" />';
    // FIXME use an alternative for offline mode
    print '<style type="text/css" media="screen, print">
		@font-face {
		font-family: "Open Sans Condensed";
		font-style: normal;
		font-weight: 300;
		src: url("../theme/pertho_sample/lib/google/OpenSans-CondensedLight.woff") format("woff"),
			url("http://themes.googleusercontent.com/static/fonts/opensanscondensed/v6/gk5FxslNkTTHtojXrkp-xF1YPouZEKgzpqZW9wN-3Ek.woff") format("woff");
		}
		</style>';
    // FIXME use an alternative for offline mode
    print '<style type="text/css" media="screen, print">
		@font-face {
		font-family: "Terminal Dosis";
		font-style: normal;
		font-weight: 400;
		src: url("../theme/pertho_sample/lib/google/Dosis-Regular.woff") format("woff"),
			url("http://themes.googleusercontent.com/static/fonts/terminaldosis/v5/_IcvjW_kWHsIHjjUsLbdpFGxLvFzKVP0OsRYGzgS_zk.woff") format("woff");
		}
		</style>';
    print '<script type="text/javascript" src="../includes/lib/stepy/js/jquery.stepy.min.js"></script>';
    print '<script type="text/javascript" src="../includes/lib/validate/jquery.validate.min.js"></script>';

    print '<script type="text/javascript" src="../includes/lib/validate/localization/messages_' . substr($langs->getDefaultLang(), 0, 2) . '.js"></script>'; //localization for validation plugin
    //print '<script type="text/javascript" src="../includes/js/pertho.js"></script>';

    print '</head>' . "\n";
    print '<header>';
    print '<div class="container head_s_a">';

    // HEADER
    print '<div class="row sepH_b">
        <div class="six columns">
        <div class="row">
        <div class="five phone-two columns">
        <div id="logo">';
    $urllogo = '../theme/speedealing_logo.png';
    $title = (!empty($conf->global->MAIN_INFO_SOCIETE_NOM)?$conf->global->MAIN_INFO_SOCIETE_NOM:'Speedealing');
    print '<center><img src="' . $urllogo . '" alt="' . $title . '" title="' . $title . '"/>';
    print DOL_VERSION . '</center>';
    print '</div></div>';
    print '</div>';
    print '</div></div>';
    print '</div>';
    print '</header>';
    print '<body class="ptrn_a grdnt_b mhover_c">' . "\n";
    print '<div class="container">';

    print '<div class="row">';

    if (!empty($soutitre))
        print start_box($langs->trans("SpeedealingSetup") . ' - ' . $soutitre, "twelve", "16-Settings.png");
    else
        print start_box($langs->trans("SpeedealingSetup"), "twelve", "16-Settings.png");

    print '<form name="forminstall" id="validate_wizard" class="stepy-wizzard nice" action="' . $next . '.php' . ($param ? '?' . $param : '') . '" method="POST">' . "\n";
    print '<input type="hidden" name="testpost" value="ok">' . "\n";
    print '<input type="hidden" name="action" value="' . $action . '">' . "\n";

    //print '<table class="main" width="100%"><tr><td>' . "\n";
    //print '<table class="main-inside" width="100%"><tr><td>' . "\n";
}

/**
 * Print HTML footer of install pages
 *
 * @param 	string	$nonext				No button "Next step"
 * @param	string	$setuplang			Language code
 * @param	string	$jscheckfunction	Add a javascript check function
 * @param	string	$withpleasewait		Add also please wait tags
 * @return	void
 */
function pFooter($nonext = 0, $setuplang = '', $jscheckfunction = '', $withpleasewait = 0) {
    global $conf, $langs;

    $langs->load("main");
    $langs->load("admin");

    //print '</td></tr></table>' . "\n";
    //print '</td></tr></table>' . "\n";

    if (!$nonext) {
        print '<div class="nextbutton" id="nextbutton"><input type="submit" value="' . $langs->trans("NextStep") . ' ->"';
        if ($jscheckfunction)
            print ' onClick="return ' . $jscheckfunction . '();"';
        print '></div>';
        print '<div style="visibility: hidden;" class="pleasewait" id="pleasewait">' . $langs->trans("NextStepMightLastALongTime") . '<br><br><div class="blinkwait">' . $langs->trans("PleaseBePatient") . '</div></div>';
    }
    if ($setuplang) {
        print '<input type="hidden" name="selectlang" value="' . $setuplang . '">';
    }

    print '</form>' . "\n";

    // If there is some logs in buffer to show
    if (isset($conf->logbuffer) && count($conf->logbuffer)) {
        print "\n";
        print "<!-- Start of log output\n";
        //print '<div class="hidden">'."\n";
        foreach ($conf->logbuffer as $logline) {
            print $logline . "\n";
        }
        //print '</div>'."\n";
        print "End of log output -->\n";
        print "\n";
    }

    print end_box();

    print '</div>';
    print '</div>';
    print '</body>' . "\n";
    print '</html>' . "\n";
}

/**
 * Log function for install pages
 *
 * @param	string	$message	Message
 * @param 	int		$level		Level of log
 * @return	void
 */
function dolibarr_install_syslog($message, $level = LOG_DEBUG) {
    if (!defined('LOG_DEBUG'))
        define('LOG_DEBUG', 6);
    dol_syslog($message, $level);
}

?>