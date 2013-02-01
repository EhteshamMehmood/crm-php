<?PHP

/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2005 	   Simon Tosser         <simon@kornog-computing.com>
 * Copyright (C) 2006 	   Andre Cianfarani     <andre.cianfarani@acdeveloppement.net>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2011      Philippe Grand       <philippe.grand@atoo-net.com>
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

//require_once("filefunc.inc.php");	// May have been already require by main.inc.php. But may not by scripts.
/* error_reporting(E_ALL);
  ini_set('display_errors', true);
  ini_set('html_errors', false); */

/*
 * Create $conf object
 */
if (!class_exists('Conf'))
    require DOL_DOCUMENT_ROOT . '/core/class/conf.class.php';

$conf = new Conf();
// Identifiant propres au serveur couchdb
$conf->Couchdb->protocol = $dolibarr_main_couchdb_protocol;
$conf->Couchdb->host = $dolibarr_main_couchdb_host;
$conf->Couchdb->port = $dolibarr_main_couchdb_port;
$conf->Couchdb->name = null;
// Identifiant pour le serveur memcached
$conf->memcached->host = $dolibarr_main_memcached_host;
$conf->memcached->port = $dolibarr_main_memcached_port;
// Is urlrewrite enable for multicompany db
$conf->urlrewrite = $dolibarr_urlrewrite;

// Identifiant propres au serveur base de donnee
$conf->db->host = $dolibarr_main_db_host;
$conf->db->port = $dolibarr_main_db_port;
$conf->db->name = $dolibarr_main_db_name;
$conf->db->user = $dolibarr_main_db_user;
$conf->db->pass = $dolibarr_main_db_pass;
$conf->db->type = $dolibarr_main_db_type;
$conf->db->prefix = $dolibarr_main_db_prefix;
$conf->db->character_set = $dolibarr_main_db_character_set;
$conf->db->dolibarr_main_db_collation = $dolibarr_main_db_collation;
$conf->db->dolibarr_main_db_encryption = $dolibarr_main_db_encryption;
$conf->db->dolibarr_main_db_cryptkey = $dolibarr_main_db_cryptkey;
$conf->file->main_limit_users = $dolibarr_main_limit_users;
$conf->file->mailing_limit_sendbyweb = $dolibarr_mailing_limit_sendbyweb;
// Identification mode
$conf->file->main_authentication = empty($dolibarr_main_authentication) ? '' : $dolibarr_main_authentication;
// Force https
$conf->file->main_force_https = empty($dolibarr_main_force_https) ? '' : $dolibarr_main_force_https;
// Cookie cryptkey
$conf->file->cookie_cryptkey = empty($dolibarr_main_cookie_cryptkey) ? '' : $dolibarr_main_cookie_cryptkey;
// Define array of document root directories
$conf->file->dol_document_root = array('main' => DOL_DOCUMENT_ROOT);
if (!empty($dolibarr_main_document_root_alt)) {
    // dolibarr_main_document_root_alt contains several directories
    $values = preg_split('/[;,]/', $dolibarr_main_document_root_alt);
    foreach ($values as $value) {
        $conf->file->dol_document_root['alt'] = $value;
    }
}
// Force db type (for test purpose)
if (defined('TEST_DB_FORCE_TYPE'))
    $conf->db->type = constant('TEST_DB_FORCE_TYPE');

// Chargement des includes principaux de librairies communes
if (!defined('NOREQUIREUSER')) {
    if (!class_exists('User'))
        require DOL_DOCUMENT_ROOT . '/user/class/user.class.php';  // Need 500ko memory
}


// For couchdb
if (!class_exists('couch'))
    require DOL_DOCUMENT_ROOT . '/core/db/couchdb/lib/couch.php';
if (!class_exists('couchClient'))
    require DOL_DOCUMENT_ROOT . '/core/db/couchdb/lib/couchClient.php';
if (!class_exists('nosqlDocument'))
    require DOL_DOCUMENT_ROOT . '/core/class/nosqlDocument.class.php';

// Load Memcache configuration
if (!empty($conf->memcached->host) && class_exists('Memcached')) {
    $memcache = new Memcached();
    $result = $memcache->addServer($conf->memcached->host, $conf->memcached->port);
    if ($result)
        $conf->memcached->enabled = true;
} elseif (!empty($conf->memcached->host) && class_exists('Memcache')) {
    $memcache = new Memcache();
    $result = $memcache->addServer($conf->memcached->host, $conf->memcached->port);
    if ($result)
        $conf->memcached->enabled = true;
}

/*
 * Object $db
 */
if (!defined('NOREQUIREDB')) {
    $db = getDoliDBInstance($conf->db->type, $conf->db->host, $conf->db->user, $conf->db->pass, $conf->db->name, $conf->db->port);

    if ($db->error) {
        dol_print_error($db, "host=" . $conf->db->host . ", port=" . $conf->db->port . ", user=" . $conf->db->user . ", databasename=" . $conf->db->name . ", " . $db->error);
        exit;
    }

    // By default conf->entity is 1, but we change this if we ask another value
    if ($conf->urlrewrite && GETPOST("db")) // Value pass from url for the name of the database : need url rewrite
        $conf->Couchdb->name = strtolower(GETPOST("db", 'alpha'));
    else { //Query standard
        if (session_id()) {   // Entity inside an opened session
            $conf->Couchdb->name = dol_getcache("dol_entity");
            if (is_int($conf->Couchdb->name))
                $conf->Couchdb->name = null;
        }
        if (empty($conf->Couchdb->name) && !empty($_ENV["dol_entity"])) {    // Entity inside a CLI script
            $conf->Couchdb->name = strtolower($_ENV["dol_entity"]);
            dol_setcache("dol_entity", $conf->Couchdb->name);
        }
        if (GETPOST("entity", 'alpha')) { // Just after a login page
            $conf->Couchdb->name = strtolower(GETPOST("entity", 'alpha'));
            dol_setcache("dol_entity", $conf->Couchdb->name);
            //} else if (defined('DOLENTITY') && is_int(DOLENTITY)) { // For public page with MultiCompany module
            //    $conf->entity = DOLENTITY;
        }
    }

    if (empty($conf->Couchdb->name))
        $conf->Couchdb->name = "_users"; // login phase

    $couch = new couchClient($conf->Couchdb->host . ':' . $conf->Couchdb->port . '/', $conf->Couchdb->name);
    $couch->setSessionCookie("AuthSession=" . $_COOKIE['AuthSession']);
}

// Creation objet $langs (must be before all other code)
if (!defined('NOREQUIRETRAN')) {
	if (!class_exists('Translate'))
		require DOL_DOCUMENT_ROOT . '/core/class/translate.class.php';
	$langs = new Translate('', $conf); // A mettre apres lecture de la conf
}

// Create the global $hookmanager object
if (!defined('NOREQUIREHOOK')) {
	if (!class_exists('HookManager'))
		require DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
	$hookmanager = new HookManager($db); // TODO remove $db object
}

// Now database connexion is known, so we can forget password
unset($dolibarr_main_db_pass);  // We comment this because this constant is used in a lot of pages
unset($conf->db->pass);    // This is to avoid password to be shown in memory/swap dump

if (!defined('MAIN_LABEL_MENTION_NPR'))
    define('MAIN_LABEL_MENTION_NPR', 'NPR');

// We force feature to help debug
//$conf->global->MAIN_JS_ON_PAYMENT=0;    // We disable this. See bug #402 on doliforge
?>
