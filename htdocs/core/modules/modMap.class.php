<?php

/* Copyright (C) 2011-2012 Herve Prot           <herve.prot@symeos.com>
 * 
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

include_once(DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php");

/**
 *       \class      modAdherent
 *       \brief      Classe de description et activation du module Adherent
 */
class modMap extends DolibarrModules {

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      Database handler
	 */
	function modMap($DB) {
		parent::__construct($DB);
		$this->values->numero = 450;

		$this->values->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->values->name = preg_replace('/^mod/i', '', get_class($this));
		$this->values->description = "Carthographie";
		$this->values->version = 'dolibarr';						// 'experimental' or 'dolibarr' or version
		$this->values->const_name = 'MAIN_MODULE_' . strtoupper($this->values->name);
		$this->values->special = 0;
		$this->values->picto = 'globe';
		$this->values->moddir = "map"; //directory for module in htdocs : test index.php presence
		// Data directories to create when module is enabled
		$this->values->dirs = array();

		// Config pages
		//-------------
		$this->values->config_page_url = array("map.php@map");

		// Dependances
		//------------
		$this->values->depends = array();
		$this->values->requiredby = array();
		$this->values->langfiles = array("map@map", "companies");

		// Constantes
		//-----------
		$this->values->const = array();
		$this->values->const[0] = array("MAP_SYSTEM", "texte", "openlayers");

		// Boites
		//-------
		$this->values->boxes = array();


		// Menu
		//------------
		// None
		// Permissions
		//------------
		$this->values->rights = array();
		$this->values->rights_class = 'map';
		$r = 0;

		// $this->values->rights[$r][0]     Id permission (unique tous modules confondus)
		// $this->values->rights[$r][1]     Libelle par defaut si traduction de cle "PermissionXXX" non trouvee (XXX = Id permission)
		// $this->values->rights[$r][2]     Non utilise
		// $this->values->rights[$r][3]     1=Permis par defaut, 0=Non permis par defaut
		// $this->values->rights[$r][4]     Niveau 1 pour nommer permission dans code
		// $this->values->rights[$r][5]     Niveau 2 pour nommer permission dans code

		$this->values->rights[$r][0] = 451;
		$this->values->rights[$r][1] = 'See map';
		$this->values->rights[$r][2] = 'r';
		$this->values->rights[$r][3] = 1;
		$this->values->rights[$r][4] = 'read';
	}

	/**
	 *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *               Definit egalement les repertoires de donnees a creer pour ce module.
	 */
	function init() {

		$sql = array();

		//$result=$this->values->load_tables();
		return $this->values->_init($sql);
	}

	/**
	 *    \brief      Fonction appelee lors de la desactivation d'un module.
	 *                Supprime de la base les constantes, boites et permissions du module.
	 */
	function remove() {
		$sql = array();

		return $this->values->_remove($sql);
	}

	/**
	 * 		\brief		Create tables and keys required by module
	 * 					Files mymodule.sql and mymodule.key.sql with create table and create keys
	 * 					commands must be stored in directory /mymodule/sql/
	 * 					This function is called by this->init.
	 * 		\return		int		<=0 if KO, >0 if OK
	 */
	function load_tables() {
		return $this->values->_load_tables('/map/sql/');
	}

}

?>
