<?php

/* Copyright (C) 2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2009-2011 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Herve Prot           <herve.prot@symeos.com>
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

include_once(DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php");

class modPlanning extends DolibarrModules {

    /**
     *   Constructor. Define names, constants, directories, boxes, permissions
     *
     *   @param      DoliDB		$db      Database handler
     */
    function __construct($db) {
        parent::__construct($db);

        $this->numero = 1000;

        $this->family = "projects";
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Gestion du planning de production";
        $this->version = 'speedealing';                        // 'experimental' or 'dolibarr' or version
        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->special = 0;
        $this->picto = 'action';

        // Data directories to create when module is enabled
        $this->dirs = array("/planning/temp");

        // Config pages
        //-------------
        $this->config_page_url = array("planning.php@planning");

        // Dependancies
        //-------------
        $this->depends = array();
        $this->requiredby = array();
        $this->langfiles = array("companies","agenda");

        // Constantes
        //-----------
        $this->const = array();

        // New pages on tabs
        // -----------------
        $this->tabs = array();

        // Boxes
        //------
        $this->boxes = array();

        // Permissions
        //------------
        $this->rights = array();
        $this->rights_class = 'planning';
        $r = 0;

        // $this->rights[$r][0]     Id permission (unique tous modules confondus)
        // $this->rights[$r][1]     Libelle par defaut si traduction de cle "PermissionXXX" non trouvee (XXX = Id permission)
        // $this->rights[$r][2]     Non utilise
        // $this->rights[$r][3]     1=Permis par defaut, 0=Non permis par defaut
        // $this->rights[$r][4]     Niveau 1 pour nommer permission dans code
        // $this->rights[$r][5]     Niveau 2 pour nommer permission dans code
        // $r++;

		$this->rights[$r] = new stdClass();
        $this->rights[$r]->id = 1001;
        $this->rights[$r]->desc = 'Read actions/tasks linked to his account';
        $this->rights[$r]->default = 1;
        $this->rights[$r]->perm = array('tasks', 'read');
        $r++;

		$this->rights[$r] = new stdClass();
        $this->rights[$r]->id = 1002;
        $this->rights[$r]->desc = 'Create/modify actions/tasks linked to his account';
        $this->rights[$r]->default = 0;
        $this->rights[$r]->perm = array('tasks', 'create');
        $r++;

		$this->rights[$r] = new stdClass();
        $this->rights[$r]->id = 1003;
        $this->rights[$r]->desc = 'Delete actions/tasks linked to his account';
        $this->rights[$r]->default = 0;
        $this->rights[$r]->perm = array('tasks', 'delete');
        $r++;
        
		$this->rights[$r] = new stdClass();
        $this->rights[$r]->id = 1004;
        $this->rights[$r]->desc = 'Export actions/tasks linked to his account';
        $this->rights[$r]->default = 0;
        $this->rights[$r]->perm = array('tasks', 'export');
        $r++;

        // Main menu entries
        $this->menu = array();   // List of menus to add
        $r = 0;

		$this->menus[$r] = new stdClass();
        $this->menus[$r]->_id = "menu:planning";
        $this->menus[$r]->position = 20;
        $this->menus[$r]->url = "/planning/list.php";
        $this->menus[$r]->langs = "agenda";
        $this->menus[$r]->perms = '$user->rights->planning->tasks->read';
        $this->menus[$r]->enabled = '$conf->planning->enabled';
        $this->menus[$r]->usertype = 2;
        $this->menus[$r]->title = "Planning";
		$this->menus[$r]->fk_menu = "menu:commandes";
        $r++;

        // Exports
        //--------
        $r = 0;
		$this->export[$r] = new stdClass();
        $this->export[$r]->code = $this->rights_class . '_' . $r;
        $this->export[$r]->label = 'ExportDataset_planning';
        $this->export[$r]->icon = 'action';
        $this->export[$r]->permission = '$user->rights->planning->export';
        $r++;
        
         // Imports
        //--------
        $r = 0;
        // Import list of third parties and attributes
		$this->import[$r] = new stdClass();
        $this->import[$r]->code = $this->rights_class . '_' . $r;
        $this->import[$r]->label = 'ImportDataset_planning';
        $this->import[$r]->icon = 'action';
        $r++;
        
    }

    /**
     * 		Function called when module is enabled.
     * 		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     * 		It also creates data directories
     *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
     *      @return     int             	1 if OK, 0 if KO
     */
    function init($options = '') {
        // Prevent pb of modules not correctly disabled
        //$this->remove($options);

        $sql = array();

        return $this->_init($sql, $options);
    }

    /**
     * 		Function called when module is disabled.
     *      Remove from database constants, boxes and permissions from Dolibarr database.
     * 		Data directories are not deleted
     *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
     *      @return     int             	1 if OK, 0 if KO
     */
    function remove($options = '') {
        $sql = array();

        return $this->_remove($sql, $options);
    }

}

?>
