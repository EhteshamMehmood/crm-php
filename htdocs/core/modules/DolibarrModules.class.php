<?php

/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 Herve Prot           <herve.prot@symeos.com>
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
 *  \file           htdocs/core/modules/DolibarrModules.class.php
 *  \brief          Fichier de description et activation des modules Dolibarr
 */
require_once(DOL_DOCUMENT_ROOT . "/core/class/extrafields.class.php");

/**
 *  \class      DolibarrModules
 *  \brief      Classe mere des classes de description et activation des modules Dolibarr
 */
class DolibarrModules extends nosqlDocument {

    //! Database handler
    var $db;
    //! Relative path to module style sheet
    var $style_sheet = ''; // deprecated
    //! Path to create when module activated
    var $dirs = array();
    //! Tableau des boites
    var $boxes;
    //! Tableau des constantes
    var $const;
    //! Tableau des droits
    var $rights;
    //! Tableau des menus
    var $menu = array();
    //! Module parts array
    var $module_parts = array();
    //! Tableau des documents ???
    var $docs;
    var $global; // Load global from database
    var $dbversion = "-";

    function __construct($db) {
        global $couch;

        parent::__construct($db);

        try {
            $this->global = $couch->getDoc("const", true);
        } catch (Exception $e) {
            dol_print_error('', "Error : no const document in database" . $e->getMessage());
        }

        $fk_extrafields = new ExtraFields($db);
        $this->fk_extrafields = $fk_extrafields->load("extrafields:DolibarrModules", true); // load and cache
    }

    /**
     *      Fonction d'activation. Insere en base les constantes et boites du module
     *
     *      @param      array	$array_sql  Array of SQL requests to execute when enabling module
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
     *      @return     int              	1 if OK, 0 if KO
     */
    function _init($array_sql, $options = '') {
        global $langs;
        $err = 0;

        $this->db->begin();

        // Insert line in module table
        if (!$err)
            $err+=$this->_dbactive();

        // Insert activation module constant
        if (!$err)
            $err+=$this->_active();

        // Insert new pages for tabs into llx_const
        if (!$err)
            $err+=$this->insert_tabs();

        // Insert activation of module's parts
        if (!$err)
            $err+=$this->insert_module_parts();

        // Insert constant defined by modules, into llx_const
        if (!$err)
            $err+=$this->insert_const();

        // Insere les boites dans llx_boxes_def
        if (!$err && $options != 'noboxes')
            $err+=$this->insert_boxes();

        // Insert permission definitions of module into llx_rights_def. If user is admin, grant this permission to user.
        if (!$err)
            $err+=$this->insert_permissions(1);

        // Insert specific menus entries into database
        if (!$err)
            $err+=$this->insert_menus();

        // Create module's directories
        if (!$err)
            $err+=$this->create_dirs();

        // Execute addons requests
        $num = count($array_sql);
        for ($i = 0; $i < $num; $i++) {
            if (!$err) {
                $val = $array_sql[$i];
                $sql = '';
                $ignoreerror = 0;
                if (is_array($val)) {
                    $sql = $val['sql'];
                    $ignoreerror = $val['ignoreerror'];
                } else {
                    $sql = $val;
                }

                dol_syslog(get_class($this) . "::_init ignoreerror=" . $ignoreerror . " sql=" . $sql, LOG_DEBUG);
                $result = $this->db->query($sql);
                if (!$result) {
                    if (!$ignoreerror) {
                        $this->error = $this->db->lasterror();
                        dol_syslog(get_class($this) . "::_init Error " . $this->error, LOG_ERR);
                        $err++;
                    } else {
                        dol_syslog(get_class($this) . "::_init Warning " . $this->db->lasterror(), LOG_WARNING);
                    }
                }
            }
        }

        // Return code
        if (!$err) {
            $this->db->commit();
            //print_r($this->global);exit;
            $this->couchdb->storeDoc($this->global);
            $this->flush(); // clear cache
            return 1;
        } else {
            $this->db->rollback();
            return 0;
        }
    }

    /**
     *  Fonction de desactivation. Supprime de la base les constantes et boites du module
     *
     *  @param      array	$array_sql      Array of SQL requests to execute when disable module
     *  @param      string	$options		Options when disabling module ('', 'noboxes')
     *  @return     int      		       	1 if OK, 0 if KO
     */
    function _remove($array_sql, $options = '') {
        global $langs;

        $err = 0;

        $this->db->begin();

        // Remove line in activation module (entry in table llx_dolibarr_modules)
        if (!$err)
            $err+=$this->_dbunactive();

        // Remove activation module line (constant MAIN_MODULE_MYMODULE in llx_const)
        if (!$err)
            $err+=$this->_unactive();

        // Remove activation of module's new tabs (MAIN_MODULE_MYMODULE_TABS_XXX in llx_const)
        if (!$err)
            $err+=$this->delete_tabs();

        // Remove activation of module's parts (MAIN_MODULE_MYMODULE_XXX in llx_const)
        if (!$err)
            $err+=$this->delete_module_parts();

        // Remove constants defined by modules
        if (!$err)
            $err+=$this->delete_const();

        // Remove list of module's available boxes (entry in llx_boxes)
        if (!$err && $options != 'noboxes')
            $err+=$this->delete_boxes();

        // Remove module's permissions from list of available permissions (entries in llx_rights_def)
        if (!$err)
            $err+=$this->delete_permissions();

        // Remove module's menus (entries in llx_menu)
        if (!$err)
            $err+=$this->delete_menus();

        // Remove module's directories
        if (!$err)
            $err+=$this->delete_dirs();

        // Run complementary sql requests
        $num = count($array_sql);
        for ($i = 0; $i < $num; $i++) {
            if (!$err) {
                dol_syslog(get_class($this) . "::_remove sql=" . $array_sql[$i], LOG_DEBUG);
                $result = $this->db->query($array_sql[$i]);
                if (!$result) {
                    $this->error = $this->db->error();
                    dol_syslog(get_class($this) . "::_remove Error " . $this->error, LOG_ERR);
                    $err++;
                }
            }
        }

        // Return code
        if (!$err) {
            $this->db->commit();
            //print_r($this->global);
            $this->couchdb->storeDoc($this->global);
            $this->flush(); // clear cache
            return 1;
        } else {
            $this->db->rollback();
            return 0;
        }
    }

    /**
     *  Retourne le nom traduit du module si la traduction existe dans admin.lang,
     *  sinon le nom defini par defaut dans le module.
     *
     *  @return     string      Nom du module traduit
     */
    function getName() {
        global $langs;
        $langs->load("admin");

        if (empty($this->numero))
            $this->numero = 0;

        if ($langs->trans("Module" . $this->numero . "Name") != ("Module" . $this->numero . "Name")) {
            // Si traduction du nom du module existe
            return $langs->trans("Module" . $this->numero . "Name");
        } else {
            // If translation of module with its numero does not exists, we take its name
            return $this->name;
        }
    }

    /**
     *  Retourne le nom traduit de la permssion si la traduction existe dans admin.lang,
     *  sinon le nom defini par defaut dans le module.
     *
     *  @return     string      Nom de la permission traduite
     */
    function getPermDesc() {
        global $langs;
        $langs->load("admin");

        if ($langs->trans("Permission" . $this->id) != ("Permission" . $this->id)) {
            // Si traduction du nom du module existe
            return $langs->trans("Permission" . $this->id);
        } else {
            // If translation of module with its numero does not exists, we take its name
            $out = $this->desc;
            return $out;
        }
    }

    /**
     *  Retourne la description traduite du module si la traduction existe dans admin.lang,
     *  sinon la description definie par defaut dans le module
     *
     *  @return     string      Nom du module traduit
     */
    function getDesc() {
        global $langs;
        $langs->load("admin");

        if ($langs->trans("Module" . $this->numero . "Desc") != ("Module" . $this->numero . "Desc")) {
            // Si traduction de la description du module existe
            return $langs->trans("Module" . $this->numero . "Desc");
        } else {
            // Si traduction de la description du module n'existe pas, on prend definition en dur dans module
            return $this->description;
        }
    }

    /**
     *  Retourne la version du module.
     *  Pour les modules a l'etat 'experimental', retourne la traduction de 'experimental'
     *  Pour les modules 'dolibarr', retourne la version de Dolibarr
     *  Pour les autres modules, retourne la version du module
     *
     *  @return     string      Version du module
     */
    function getVersion() {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'experimental')
            return $langs->trans("VersionExperimental");
        elseif ($this->version == 'development')
            return $langs->trans("VersionDevelopment");
        elseif ($this->version == 'speedealing')
            return DOL_VERSION;
        elseif ($this->version == 'dolibarr')
            return '<span class="tag grey-gradient glossy">' . $langs->trans("In next version") . '</span>';
        elseif ($this->version)
            return $this->version;
        else
            return $langs->trans("VersionUnknown");
    }

    /**
     *  Return list of lang files related to module
     *
     *  @return     array       Array of lang files
     */
    function getLangFilesArray() {
        return $this->langfiles;
    }

    /**
     *  Return translated label of a export dataset
     *
     * 	@param	int		$r		Index of dataset
     *  @return string      	Label of databaset
     */
    function getExportDatasetLabel($r) {
        global $langs;

        $langstring = "ExportDataset_" . $this->export_code[$r];
        if ($langs->trans($langstring) == $langstring) {
            // Traduction non trouvee
            return $langs->trans($this->export_label[$r]);
        } else {
            // Traduction trouvee
            return $langs->trans($langstring);
        }
    }

    /**
     *  Insert line in dolibarr_modules table.
     *  Storage is made for information only, table is not required for Dolibarr usage
     *
     *  @return     int     Nb of errors (0 if OK)
     */
    function _dbactive() {
        global $conf;

        $err = 0;

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "dolibarr_modules";
        $sql.= " WHERE numero = " . $this->numero;
        $sql.= " AND entity = " . $conf->entity;

        dol_syslog(get_class($this) . "::_dbactive sql=" . $sql, LOG_DEBUG);
        $this->db->query($sql);

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "dolibarr_modules (";
        $sql.= "numero";
        $sql.= ", entity";
        $sql.= ", active";
        $sql.= ", active_date";
        $sql.= ", active_version";
        $sql.= ")";
        $sql.= " VALUES (";
        $sql.= $this->numero;
        $sql.= ", " . $conf->entity;
        $sql.= ", 1";
        $sql.= ", '" . $this->db->idate(dol_now()) . "'";
        $sql.= ", '" . $this->version . "'";
        $sql.= ")";

        dol_syslog(get_class($this) . "::_dbactive sql=" . $sql, LOG_DEBUG);
        $this->db->query($sql);

        return $err;
    }

    /**
     *  Remove line in dolibarr_modules table
     *  Storage is made for information only, table is not required for Dolibarr usage
     *
     *  @return     int     Nb of errors (0 if OK)
     */
    function _dbunactive() {
        global $conf;

        $err = 0;

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "dolibarr_modules";
        $sql.= " WHERE numero = " . $this->numero;
        $sql.= " AND entity IN (0, " . $conf->entity . ")";

        dol_syslog(get_class($this) . "::_dbunactive sql=" . $sql, LOG_DEBUG);
        $this->db->query($sql);

        return $err;
    }

    /**
     *      Insert constant to activate module
     *
     *      @return     int     Nb of errors (0 if OK)
     */
    function _active() {
        global $conf;

        $err = 0;

        // Common module
        $entity = ((!empty($this->always_enabled) || !empty($this->core_enabled)) ? 0 : $conf->entity);

        $name = $this->const_name;
        $this->global->values->$name = 1;

        return $err;
    }

    /**
     *      Remove activation line
     *
     *      @return     int     Nb of errors (0 if OK)
     * */
    function _unactive() {
        global $conf;

        $name = $this->const_name;

        if (isset($this->global->values->$name))
            unset($this->global->values->$name);

        $err = 0;

        return $err;
    }

    /**
     *  Create tables and keys required by module.
     *  Files module.sql and module.key.sql with create table and create keys
     *  commands must be stored in directory reldir='/module/sql/'
     *  This function is called by this->init
     *
     *  @param	string	$reldir		Relative directory where to scan files
     *  @return	int     			<=0 if KO, >0 if OK
     */
    function _load_tables($reldir) {
        global $db, $conf;

        $error = 0;

        include_once(DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php");

        $ok = 1;
        foreach ($conf->file->dol_document_root as $dirroot) {
            if ($ok) {
                $dir = $dirroot . $reldir;
                $ok = 0;

                // Run llx_mytable.sql files
                $handle = @opendir($dir);   // Dir may not exists
                if (is_resource($handle)) {
                    while (($file = readdir($handle)) !== false) {
                        if (preg_match('/\.sql$/i', $file) && !preg_match('/\.key\.sql$/i', $file) && substr($file, 0, 4) == 'llx_' && substr($file, 0, 4) != 'data') {
                            $result = run_sql($dir . $file, 1, '', 1);
                            if ($result <= 0)
                                $error++;
                        }
                    }
                    closedir($handle);
                }

                // Run llx_mytable.key.sql files (Must be done after llx_mytable.sql)
                $handle = @opendir($dir);   // Dir may not exist
                if (is_resource($handle)) {
                    while (($file = readdir($handle)) !== false) {
                        if (preg_match('/\.key\.sql$/i', $file) && substr($file, 0, 4) == 'llx_' && substr($file, 0, 4) != 'data') {
                            $result = run_sql($dir . $file, 1, '', 1);
                            if ($result <= 0)
                                $error++;
                        }
                    }
                    closedir($handle);
                }

                // Run data_xxx.sql files (Must be done after llx_mytable.key.sql)
                $handle = @opendir($dir);   // Dir may not exist
                if (is_resource($handle)) {
                    while (($file = readdir($handle)) !== false) {
                        if (preg_match('/\.sql$/i', $file) && !preg_match('/\.key\.sql$/i', $file) && substr($file, 0, 4) == 'data') {
                            $result = run_sql($dir . $file, 1, '', 1);
                            if ($result <= 0)
                                $error++;
                        }
                    }
                    closedir($handle);
                }

                // Run update_xxx.sql files
                $handle = @opendir($dir);   // Dir may not exist
                if (is_resource($handle)) {
                    while (($file = readdir($handle)) !== false) {
                        if (preg_match('/\.sql$/i', $file) && !preg_match('/\.key\.sql$/i', $file) && substr($file, 0, 6) == 'update') {
                            $result = run_sql($dir . $file, 1, '', 1);
                            if ($result <= 0)
                                $error++;
                        }
                    }
                    closedir($handle);
                }

                if ($error == 0) {
                    $ok = 1;
                }
            }
        }

        return $ok;
    }

    /**
     *  Create views and documents required by module.
     *  Files module.view.json and module.json with create view and create documents
     *  commands must be stored in directory reldir='/module/json/'
     *  This function is called by this->init
     *
     *  @param	string	$reldir		Relative directory where to scan files
     *  @return	int     			<=0 if KO, >0 if OK
     */
    function _load_documents() {
        global $db, $conf;

        $error = 0;

        $ok = 1;
        foreach ($conf->file->dol_document_root as $dirroot) {
            if ($ok) {
                $dir = $dirroot . "/" . strtolower($this->name) . "/json/";
                $ok = 0;

                // Create or upgrade views and documents
                $handle = @opendir($dir);   // Dir may not exists
                if (is_resource($handle)) {
                    while (($file = readdir($handle)) !== false) {
                        if (preg_match('/\.json$/i', $file)) {
                            $fp = fopen($dir . $file, "r");
                            if ($fp) {
                                $json = fread($fp, filesize($dir . $file));
                                $obj = json_decode($json);
                                unset($obj->_rev);

                                // Test if exist document in database : upgrade
                                try {
                                    $result = $this->couchdb->getDoc($obj->_id);
                                    $obj->_rev = $result->_rev;
                                } catch (Exception $e) {
                                    
                                }

                                if (!empty($result)) {
                                    if ($result->class == "extrafields") {
                                        if (isset($obj->shortList))
                                            $obj->shortList = $result->shortList;
                                        if (isset($obj->longList))
                                            $obj->longList = $result->longList;

                                        foreach ($result->fields as $key => $aRow) {
                                            if ($aRow->optional) //specific extrafields
                                                $obj->fields->$key = $aRow;

                                            if ($aRow->enable) // Test if fields was enable or disable
                                                $obj->fields->$key->enable = true;
                                            else
                                                $obj->fields->$key->enable = false;
                                        }
                                    }
                                }

                                try {
                                    $this->couchdb->storeDoc($obj);
                                } catch (Exception $e) {
                                    dol_print_error("", $e->getMessage());
                                    $error++;
                                }

                                fclose($fp);
                            }
                        }
                    }
                    closedir($handle);
                }


                if ($error == 0) {
                    $ok = 1;
                }
            }
        }

        return $ok;
    }

    /**
     *  Insert boxes into llx_boxes_def
     *
     *  @return     int     Nb of errors (0 if OK)
     */
    function insert_boxes() {
        global $conf;

        $err = 0;

        if (is_array($this->boxes)) {
            foreach ($this->boxes as $key => $value) {
                //$titre = $this->boxes[$key][0];
                $file = isset($this->boxes[$key][1]) ? $this->boxes[$key][1] : '';
                $note = isset($this->boxes[$key][2]) ? $this->boxes[$key][2] : '';

                $sql = "SELECT count(*) as nb FROM " . MAIN_DB_PREFIX . "boxes_def";
                $sql.= " WHERE file = '" . $this->db->escape($file) . "'";
                $sql.= " AND entity = " . $conf->entity;
                if ($note)
                    $sql.=" AND note ='" . $this->db->escape($note) . "'";

                $result = $this->db->query($sql);
                if ($result) {
                    $obj = $this->db->fetch_object($result);
                    if ($obj->nb == 0) {
                        $this->db->begin();

                        if (!$err) {
                            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "boxes_def (file,entity,note)";
                            $sql.= " VALUES ('" . $this->db->escape($file) . "',";
                            $sql.= $conf->entity . ",";
                            $sql.= $note ? "'" . $this->db->escape($note) . "'" : "null";
                            $sql.= ")";

                            dol_syslog(get_class($this) . "::insert_boxes sql=" . $sql);
                            $resql = $this->db->query($sql);
                            if (!$resql)
                                $err++;
                        }
                        if (!$err) {
                            $lastid = $this->db->last_insert_id(MAIN_DB_PREFIX . "boxes_def", "rowid");

                            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "boxes (box_id,position,box_order,fk_user)";
                            $sql.= " VALUES (" . $lastid . ", 0, '0', 0)";

                            dol_syslog(get_class($this) . "::insert_boxes sql=" . $sql);
                            $resql = $this->db->query($sql);
                            if (!$resql)
                                $err++;
                        }

                        if (!$err) {
                            $this->db->commit();
                        } else {
                            $this->error = $this->db->lasterror();
                            dol_syslog(get_class($this) . "::insert_boxes " . $this->error, LOG_ERR);
                            $this->db->rollback();
                        }
                    }
                } else {
                    $this->error = $this->db->lasterror();
                    dol_syslog(get_class($this) . "::insert_boxes " . $this->error, LOG_ERR);
                    $err++;
                }
            }
        }

        return $err;
    }

    /**
     *  Delete boxes
     *
     *  @return     int     Nb of errors (0 if OK)
     */
    function delete_boxes() {
        global $conf;

        $err = 0;

        if (is_array($this->boxes)) {
            foreach ($this->boxes as $key => $value) {
                //$titre = $this->boxes[$key][0];
                $file = $this->boxes[$key][1];
                //$note  = $this->boxes[$key][2];

                $sql = "DELETE FROM " . MAIN_DB_PREFIX . "boxes";
                $sql.= " USING " . MAIN_DB_PREFIX . "boxes, " . MAIN_DB_PREFIX . "boxes_def";
                $sql.= " WHERE " . MAIN_DB_PREFIX . "boxes.box_id = " . MAIN_DB_PREFIX . "boxes_def.rowid";
                $sql.= " AND " . MAIN_DB_PREFIX . "boxes_def.file = '" . $this->db->escape($file) . "'";
                $sql.= " AND " . MAIN_DB_PREFIX . "boxes_def.entity = " . $conf->entity;

                dol_syslog(get_class($this) . "::delete_boxes sql=" . $sql);
                $resql = $this->db->query($sql);
                if (!$resql) {
                    $this->error = $this->db->lasterror();
                    dol_syslog(get_class($this) . "::delete_boxes " . $this->error, LOG_ERR);
                    $err++;
                }

                $sql = "DELETE FROM " . MAIN_DB_PREFIX . "boxes_def";
                $sql.= " WHERE file = '" . $this->db->escape($file) . "'";
                $sql.= " AND entity = " . $conf->entity;

                dol_syslog(get_class($this) . "::delete_boxes sql=" . $sql);
                $resql = $this->db->query($sql);
                if (!$resql) {
                    $this->error = $this->db->lasterror();
                    dol_syslog(get_class($this) . "::delete_boxes " . $this->error, LOG_ERR);
                    $err++;
                }
            }
        }

        return $err;
    }

    /**
     *  Remove links to new module page present in llx_const
     *
     *  @return     int     Nb of errors (0 if OK)
     */
    function delete_tabs() {
        global $conf;

        $name = $this->const_name . "_TABS";

        if (count($this->global->values)) {
            foreach ($this->global->values as $key => $aRow) {
                if (strpos($key, $name) != false)
                    if (isset($this->global->values->$key))
                        unset($this->global->values->$key);
            }
        }

        $err = 0;

        return $err;
    }

    /**
     *  Add links of new pages from modules in llx_const
     *
     *  @return     int     Number of errors (0 if ok)
     */
    function insert_tabs() {
        global $conf;

        $err = 0;

        if (!empty($this->tabs)) {
            $i = 0;
            foreach ($this->tabs as $key => $value) {
                if ($value) {
                    $name = $this->const_name . "_TABS_" . $i;
                    $this->global->values->$name = $value;
                    $i++;
                }
            }
        }
        return $err;
    }

    /**
     *  Insert constants defined into $this->const array into table llx_const
     *
     *  @return     int     Number of errors (0 if OK)
     */
    function insert_const() {
        global $conf;

        $err = 0;

        foreach ($this->const as $key => $value) {
            $name = $this->const[$key][0];
            $type = $this->const[$key][1];
            $val = $this->const[$key][2];
            $note = isset($this->const[$key][3]) ? $this->const[$key][3] : '';
            $visible = isset($this->const[$key][4]) ? $this->const[$key][4] : 0;
            $entity = (!empty($this->const[$key][5]) && $this->const[$key][5] != 'current') ? 0 : $conf->entity;

            // Clean
            if (empty($visible))
                $visible = '0';
            if (empty($val))
                $val = '';

            $sql = "SELECT count(*)";
            $sql.= " FROM " . MAIN_DB_PREFIX . "const";
            $sql.= " WHERE " . $this->db->decrypt('name') . " = '" . $name . "'";
            $sql.= " AND entity = " . $entity;

            $result = $this->db->query($sql);
            if ($result) {
                $row = $this->db->fetch_row($result);

                if ($row[0] == 0) {   // If not found
                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "const (name,type,value,note,visible,entity)";
                    $sql.= " VALUES (";
                    $sql.= $this->db->encrypt($name, 1);
                    $sql.= ",'" . $type . "'";
                    $sql.= "," . ($val ? $this->db->encrypt($val, 1) : "''");
                    $sql.= "," . ($note ? "'" . $this->db->escape($note) . "'" : "null");
                    $sql.= ",'" . $visible . "'";
                    $sql.= "," . $entity;
                    $sql.= ")";


                    dol_syslog(get_class($this) . "::insert_const sql=" . $sql);
                    if (!$this->db->query($sql)) {
                        dol_syslog(get_class($this) . "::insert_const " . $this->db->lasterror(), LOG_ERR);
                        $err++;
                    }
                } else {
                    dol_syslog(get_class($this) . "::insert_const constant '" . $name . "' already exists", LOG_WARNING);
                }
            } else {
                $err++;
            }
        }

        return $err;
    }

    /**
     * Remove constants with tags deleteonunactive
     *
     * @return     int     <0 if KO, 0 if OK
     */
    function delete_const() {
        global $conf;

        $err = 0;

        foreach ($this->const as $key => $value) {
            $name = $this->const[$key][0];
            $deleteonunactive = (!empty($this->const[$key][6])) ? 1 : 0;

            if ($deleteonunactive) {
                if (isset($this->global->values->$name))
                    unset($this->global->values->$name);
            }
        }

        return $err;
    }

    /**
     *  Insert permissions definitions related to the module into llx_rights_def
     *
     *  @param	int		$reinitadminperms   If 1, we also grant them to all admin users
     *  @return int                 		Number of error (0 if OK)
     */
    function insert_permissions($reinitadminperms = 0) {
        global $conf, $user;

        $err = 0;

        //print $this->rights_class." ".count($this->rights)."<br>";
        // Test if module is activated
        $sql_del = "SELECT " . $this->db->decrypt('value') . " as value";
        $sql_del.= " FROM " . MAIN_DB_PREFIX . "const";
        $sql_del.= " WHERE " . $this->db->decrypt('name') . " = '" . $this->const_name . "'";
        $sql_del.= " AND entity IN (0," . $conf->entity . ")";

        dol_syslog(get_class($this) . "::insert_permissions sql=" . $sql_del);
        $resql = $this->db->query($sql_del);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj->value) {
                // Si module actif
                foreach ($this->rights as $key => $value) {
                    $r_id = $this->rights[$key][0];
                    $r_desc = $this->rights[$key][1];
                    $r_type = isset($this->rights[$key][2]) ? $this->rights[$key][2] : '';
                    $r_def = $this->rights[$key][3];
                    $r_perms = $this->rights[$key][4];
                    $r_subperms = isset($this->rights[$key][5]) ? $this->rights[$key][5] : '';
                    $r_modul = $this->rights_class;

                    if (empty($r_type))
                        $r_type = 'w';

                    if (dol_strlen($r_perms)) {
                        if (dol_strlen($r_subperms)) {
                            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "rights_def";
                            $sql.= " (id, entity, libelle, module, type, bydefault, perms, subperms)";
                            $sql.= " VALUES ";
                            $sql.= "(" . $r_id . "," . $conf->entity . ",'" . $this->db->escape($r_desc) . "','" . $r_modul . "','" . $r_type . "'," . $r_def . ",'" . $r_perms . "','" . $r_subperms . "')";
                        } else {
                            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "rights_def";
                            $sql.= " (id, entity, libelle, module, type, bydefault, perms)";
                            $sql.= " VALUES ";
                            $sql.= "(" . $r_id . "," . $conf->entity . ",'" . $this->db->escape($r_desc) . "','" . $r_modul . "','" . $r_type . "'," . $r_def . ",'" . $r_perms . "')";
                        }
                    } else {
                        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "rights_def ";
                        $sql .= " (id, entity, libelle, module, type, bydefault)";
                        $sql .= " VALUES ";
                        $sql .= "(" . $r_id . "," . $conf->entity . ",'" . $this->db->escape($r_desc) . "','" . $r_modul . "','" . $r_type . "'," . $r_def . ")";
                    }

                    dol_syslog(get_class($this) . "::insert_permissions sql=" . $sql, LOG_DEBUG);
                    $resqlinsert = $this->db->query($sql, 1);
                    if (!$resqlinsert) {
                        if ($this->db->errno() != "DB_ERROR_RECORD_ALREADY_EXISTS") {
                            $this->error = $this->db->lasterror();
                            dol_syslog(get_class($this) . "::insert_permissions error " . $this->error, LOG_ERR);
                            $err++;
                            break;
                        }
                        else
                            dol_syslog(get_class($this) . "::insert_permissions record already exists", LOG_INFO);
                    }
                    $this->db->free($resqlinsert);

                    // If we want to init permissions on admin users
                    if ($reinitadminperms) {
                        include_once(DOL_DOCUMENT_ROOT . '/user/class/user.class.php');
                        $sql = "SELECT rowid from " . MAIN_DB_PREFIX . "user where admin = 1";
                        dol_syslog(get_class($this) . "::insert_permissions Search all admin users sql=" . $sql);
                        $resqlseladmin = $this->db->query($sql, 1);
                        if ($resqlseladmin) {
                            $num = $this->db->num_rows($resqlseladmin);
                            $i = 0;
                            while ($i < $num) {
                                $obj2 = $this->db->fetch_object($resqlseladmin);
                                dol_syslog(get_class($this) . "::insert_permissions Add permission to user id=" . $obj2->rowid);
                                $tmpuser = new User($this->db);
                                $tmpuser->fetch($obj2->rowid);
                                $tmpuser->addrights($r_id);
                                $i++;
                            }
                            if (!empty($user->admin)) {  // Reload permission for current user if defined
                                // We reload permissions
                                $user->clearrights();
                                $user->getrights();
                            }
                        }
                        else
                            dol_print_error($this->db);
                    }
                }
            }
            $this->db->free($resql);
        }
        else {
            $this->error = $this->db->lasterror();
            dol_syslog(get_class($this) . "::insert_permissions " . $this->error, LOG_ERR);
            $err++;
        }

        return $err;
    }

    /**
     * Delete permissions
     *
     * @return     int     Nb of errors (0 if OK)
     */
    function delete_permissions() {
        global $conf;

        $err = 0;

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "rights_def";
        $sql.= " WHERE module = '" . $this->rights_class . "'";
        $sql.= " AND entity = " . $conf->entity;
        dol_syslog(get_class($this) . "::delete_permissions sql=" . $sql);
        if (!$this->db->query($sql)) {
            $this->error = $this->db->lasterror();
            dol_syslog(get_class($this) . "::delete_permissions " . $this->error, LOG_ERR);
            $err++;
        }

        return $err;
    }

    /**
     *  Insert menus entries found into $this->menu into llx_menu*
     *
     *  @return     int     Nb of errors (0 if OK)
     */
    function insert_menus() {
        global $user;

        require_once(DOL_DOCUMENT_ROOT . "/core/class/menubase.class.php");

        $err = 0;
        if (count($this->menu) == 0)
            return 0;

        $menus = array();

        //var_dump($this->menu); exit;
        foreach ($this->menu as $value) {
            $id = $value['_id'];

            $menu[$id]->module = $this->rights_class;

            if (empty($value['_id'])) {
                $error = "ErrorBadDefinitionOfMenuArrayInModuleDescriptor (bad value for _id)";
                dol_print_error("", $error);
                $err++;
            }

            if ($value['type'] != "top" && !empty($value['fk_menu'])) {
                if (empty($menu[$value['fk_menu']])) {
                    try {
                        $this->couchdb->getDoc($value['fk_menu']);
                    } catch (Exception $e) {
                        $error = "ErrorBadDefinitionOfMenuArrayInModuleDescriptor (bad value for key fk_menu)";
                        $error.="<br>Something weird happened: " . $e->getMessage() . " (errcode=" . $e->getCode() . ")\n";
                        dol_print_error("", $error);
                        $err++;
                    }
                }
            }

            $menu[$id]->class = "menu";
            $menu[$id]->type = $value['type'];
            $menu[$id]->title = $value['titre'];
            $menu[$id]->url = $value['url'];
            $menu[$id]->langs = $value['langs'];
            $menu[$id]->position = (int) $value['position'];
            $menu[$id]->perms = $value['perms'];
            $menu[$id]->target = $value['target'];
            $menu[$id]->user = $value['user'];
            $menu[$id]->enabled = isset($value['enabled']) ? $value['enabled'] : false;
            if ($value['fk_menu'])
                $menu[$id]->fk_menu = $value['fk_menu'];
            $menu[$id]->_id = $value['_id'];

            // for update
            try {
                $obj = $this->couchdb->getDoc($id);
                $menu[$id]->_rev = $obj->_rev;
            } catch (Exception $e) {
                
            }
        }

        //print_r($menu);exit;

        if (!$err) {
            try {
                $this->couchdb->clean($menu);
                $this->couchdb->storeDocs($menu, false);
            } catch (Exception $e) {
                $error = "Something weird happened: " . $e->getMessage() . " (errcode=" . $e->getCode() . ")\n";
                dol_print_error("", $error);
                exit(1);
            }
        } else {
            dol_syslog(get_class($this) . "::insert_menus " . $this->error, LOG_ERR);
        }

        return $err;
    }

    /**
     *  Remove menus entries
     *
     *  @return     int     Nb of errors (0 if OK)
     */
    function delete_menus() {
        $err = 0;

        foreach ($this->menu as $key => $value) {
            try {
                $menu = $this->couchdb->getDoc($value['_id']);
                $menu->enabled = false;
                $this->couchdb->storeDoc($menu);
            } catch (Exception $e) {
                
            }
        }

        return $err;
    }

    /**
     *  Create directories required by module
     *
     *  @return     int     Nb of errors (0 if OK)
     */
    function create_dirs() {
        global $langs, $conf;

        $err = 0;

        if (is_array($this->dirs)) {
            foreach ($this->dirs as $key => $value) {
                $addtodatabase = 0;

                if (!is_array($value))
                    $dir = $value; // Default simple mode
                else {
                    $constname = $this->const_name . "_DIR_";
                    $dir = $this->dirs[$key][1];
                    $addtodatabase = empty($this->dirs[$key][2]) ? '' : $this->dirs[$key][2]; // Create constante in llx_const
                    $subname = empty($this->dirs[$key][3]) ? '' : strtoupper($this->dirs[$key][3]); // Add submodule name (ex: $conf->module->submodule->dir_output)
                    $forcename = empty($this->dirs[$key][4]) ? '' : strtoupper($this->dirs[$key][4]); // Change the module name if different

                    if ($forcename)
                        $constname = 'MAIN_MODULE_' . $forcename . "_DIR_";
                    if ($subname)
                        $constname = $constname . $subname . "_";

                    $name = $constname . strtoupper($this->dirs[$key][0]);
                }

                // Define directory full path ($dir must start with "/")
                if (empty($conf->global->MAIN_MODULE_MULTICOMPANY) || $conf->entity == 1)
                    $fulldir = DOL_DATA_ROOT . $dir;
                else
                    $fulldir = DOL_DATA_ROOT . "/" . $conf->entity . $dir;
                // Create dir if it does not exists
                if ($fulldir && !file_exists($fulldir)) {
                    if (dol_mkdir($fulldir) < 0) {
                        $this->error = $langs->trans("ErrorCanNotCreateDir", $fulldir);
                        dol_syslog(get_class($this) . "::_init " . $this->error, LOG_ERR);
                        $err++;
                    }
                }

                // Define the constant in database if requested (not the default mode)
                if ($addtodatabase) {
                    $result = $this->insert_dirs($name, $dir);
                    if ($result)
                        $err++;
                }
            }
        }

        return $err;
    }

    /**
     *  Insert directories in llx_const
     *
     *  @param	string	$name		Name
     *  @param	string	$dir		Directory
     *  @return	int     			Nb of errors (0 if OK)
     */
    function insert_dirs($name, $dir) {
        global $conf;

        $err = 0;

        $sql = "SELECT count(*)";
        $sql.= " FROM " . MAIN_DB_PREFIX . "const";
        $sql.= " WHERE " . $this->db->decrypt('name') . " = '" . $name . "'";
        $sql.= " AND entity = " . $conf->entity;

        dol_syslog(get_class($this) . "::insert_dirs sql=" . $sql);
        $result = $this->db->query($sql);
        if ($result) {
            $row = $this->db->fetch_row($result);

            if ($row[0] == 0) {
                $sql = "INSERT INTO " . MAIN_DB_PREFIX . "const (name,type,value,note,visible,entity)";
                $sql.= " VALUES (" . $this->db->encrypt($name, 1) . ",'chaine'," . $this->db->encrypt($dir, 1) . ",'Directory for module " . $this->name . "','0'," . $conf->entity . ")";

                dol_syslog(get_class($this) . "::insert_dirs sql=" . $sql);
                $resql = $this->db->query($sql);
            }
        } else {
            $this->error = $this->db->lasterror();
            dol_syslog(get_class($this) . "::insert_dirs " . $this->error, LOG_ERR);
            $err++;
        }

        return $err;
    }

    /**
     *  Remove directory entries
     *
     *  @return     int     Nb of errors (0 if OK)
     */
    function delete_dirs() {
        global $conf;

        $err = 0;

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "const";
        $sql.= " WHERE " . $this->db->decrypt('name') . " like '" . $this->const_name . "_DIR_%'";
        $sql.= " AND entity = " . $conf->entity;

        dol_syslog(get_class($this) . "::delete_dirs sql=" . $sql);
        if (!$this->db->query($sql)) {
            $this->error = $this->db->lasterror();
            dol_syslog(get_class($this) . "::delete_dirs " . $this->error, LOG_ERR);
            $err++;
        }

        return $err;
    }

    /**
     * Insert activation of generic parts from modules in llx_const
     *
     * @return     int     Nb of errors (0 if OK)
     */
    function insert_module_parts() {
        global $conf;

        $error = 0;
        $entity = $conf->entity;

        if (is_array($this->module_parts) && !empty($this->module_parts)) {
            foreach ($this->module_parts as $key => $value) {
                $newvalue = $value;

                // Serialize array parameters
                if (is_array($value)) {
                    // Can defined other parameters
                    if (is_array($value['data']) && !empty($value['data'])) {
                        $newvalue = json_encode($value['data']);
                        if (isset($value['entity']))
                            $entity = $value['entity'];
                    }
                    else {
                        $newvalue = json_encode($value);
                    }
                }

                $name = $this->const_name . "_" . strtoupper($key);
                $this->global->values->$name = $newvalue;
            }
        }
        return $error;
    }

    /**
     * Remove activation of generic parts of modules from llx_const
     *
     * @return     int     Nb of errors (0 if OK)
     */
    function delete_module_parts() {
        global $conf;

        $err = 0;
        $entity = $conf->entity;

        if (is_array($this->module_parts) && !empty($this->module_parts)) {
            foreach ($this->module_parts as $key => $value) {
                // If entity is defined
                if (is_array($value) && isset($value['entity']))
                    $entity = $value['entity'];

                $name = $this->const_name . "_" . strtoupper($key);

                foreach ($this->global->values as $key => $aRow) {
                    if (strpos($key, $name) != false)
                        unset($this->global->values->$key);
                }
            }
        }

        return $err;
    }

    /**
     * Return modules configurations
     */
    function load_modules_files(&$filename, &$modules, &$orders, &$categ, &$dirmod, &$modNameLoaded) {
        global $conf;

        // Search modules dirs
        $modulesdir = array();
        foreach ($conf->file->dol_document_root as $type => $dirroot) {
            $modulesdir[$dirroot . '/core/modules/'] = $dirroot . '/core/modules/';

            $handle = @opendir($dirroot);
            if (is_resource($handle)) {
                while (($file = readdir($handle)) !== false) {
                    if (is_dir($dirroot . '/' . $file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS' && $file != 'includes') {
                        if (is_dir($dirroot . '/' . $file . '/core/modules/')) {
                            $modulesdir[$dirroot . '/' . $file . '/core/modules/'] = $dirroot . '/' . $file . '/core/modules/';
                        }
                    }
                }
                closedir($handle);
            }
        }

        foreach ($modulesdir as $dir) {

            // Load modules attributes in arrays (name, numero, orders) from dir directory
            //print $dir."\n<br>";
            $handle = @opendir($dir);
            if (is_resource($handle)) {
                while (($file = readdir($handle)) !== false) {
                    //print "$i ".$file."\n<br>";
                    if (is_readable($dir . $file) && substr($file, 0, 3) == 'mod' && substr($file, dol_strlen($file) - 10) == '.class.php') {
                        $modName = substr($file, 0, dol_strlen($file) - 10);

                        if ($modName) {
                            if (!empty($modNameLoaded[$modName])) {
                                $mesg = "Error: Module " . $modName . " was found twice: Into " . $modNameLoaded[$modName] . " and " . $dir . ". You probably have an old file on your disk.<br>";
                                dol_syslog($mesg, LOG_ERR);
                                continue;
                            }

                            try {
                                $res = include_once($dir . $file);
                                $objMod = new $modName($db);
                                $modNameLoaded[$modName] = $dir;

                                if ($objMod->numero >= 0) {
                                    $j = $objMod->numero;
                                } else {
                                    $j = 1000 + $i;
                                }

                                $modulequalified = 1;

                                // We discard modules according to features level (PS: if module is activated we always show it)
                                $const_name = 'MAIN_MODULE_' . strtoupper(preg_replace('/^mod/i', '', get_class($objMod)));
                                if ($objMod->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2 && !$conf->global->$const_name)
                                    $modulequalified = 0;
                                if ($objMod->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1 && !$conf->global->$const_name)
                                    $modulequalified = 0;

                                if ($modulequalified) {
                                    $modules[$j] = $objMod;
                                    $filename[$j] = $modName;
                                    $orders[$j] = $objMod->family . "_" . $j;   // Tri par famille puis numero module
                                    //print "x".$modName." ".$orders[$i]."\n<br>";
                                    if (isset($categ[$objMod->special]))
                                        $categ[$objMod->special]++;  // Array of all different modules categories
                                    else
                                        $categ[$objMod->special] = 1;
                                    $dirmod[$j] = $dir;
                                    $j++;
                                    $i++;
                                }
                                else
                                    dol_syslog("Module " . get_class($objMod) . " not qualified");
                            } catch (Exception $e) {
                                dol_syslog("Failed to load " . $dir . $file . " " . $e->getMessage(), LOG_ERR);
                            }
                        }
                    }
                }
                closedir($handle);
            } else {
                dol_syslog("htdocs/admin/modules.php: Failed to open directory " . $dir . ". See permission and open_basedir option.", LOG_WARNING);
            }
        }

        asort($orders);
//var_dump($orders);
//var_dump($categ);
//var_dump($modules);exit;
// Affichage debut page
        return $mesg;
    }

    /**
     * Upgrade specific file for database not a a module : For the core
     */
    function upgradeCore() {
        $files = array("DolibarrModules.view", "MenuAuguria.view", "Dict.view", "extrafields.DolibarrModules");

        $dir = DOL_DOCUMENT_ROOT . "/install/couchdb/json/";
        foreach ($files as $row) {
            $fp = fopen($dir . $row . ".json", "r");
            if ($fp) {
                $json = fread($fp, filesize($dir . $row . ".json"));
                $obj = json_decode($json);
            } else {
                print "file not found : " . $dir . $row . ".json";
                exit;
            }

            try {
                $result = $this->couchdb->getDoc($obj->_id);
                $obj->_rev = $result->_rev;
                $this->couchdb->storeDoc($obj);
            } catch (Exception $e) {
                print $row;
                print $e->getMessage();
                exit;
            }
        }
    }

}

?>
