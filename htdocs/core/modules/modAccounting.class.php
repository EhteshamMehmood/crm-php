<?php

/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2011-2012 Herve Prot           <herve.prot@symeos.com>
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
 * 	\defgroup   accounting 			Module accounting
 * 	\brief      Module to include accounting features
 * 	\file       htdocs/core/modules/modAccounting.class.php
 * 	\ingroup    accounting
 * 	\brief      Fichier de description et activation du module Comptabilite Expert
 */
include_once(DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php");

/**
 * 	\class      modAccounting
 * 	\brief      Classe de description et activation du module Comptabilite Expert
 */
class modAccounting extends DolibarrModules {

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function modAccounting($db) {
		global $conf;

		parent::__construct($db);
		$this->values->numero = 50400;

		$this->values->family = "financial";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->values->name = preg_replace('/^mod/i', '', get_class($this));
		$this->values->description = "Gestion complete de comptabilite (doubles parties)";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		//$this->values->version = 'dolibarr';
		$this->values->version = "development";

		$this->values->const_name = 'MAIN_MODULE_' . strtoupper($this->values->name);
		$this->values->special = 0;

		// Config pages
		$this->values->config_page_url = array("accounting.php");

		// Dependancies
		$this->values->depends = array("modFacture", "modBanque", "modTax");
		$this->values->requiredby = array();
		$this->values->conflictwith = array("modComptabilite");
		$this->values->langfiles = array("compta");

		// Constants
		$this->values->const = array(0 => array('MAIN_COMPANY_CODE_ALWAYS_REQUIRED', 'chaine', '1', 'With this constants on, third party code is always required whatever is numbering module behaviour', 0, 'current', 1),
			1 => array('MAIN_BANK_ACCOUNTANCY_CODE_ALWAYS_REQUIRED', 'chaine', '1', 'With this constants on, bank account number is always required', 0, 'current', 1),
		);   // List of particular constants to add when module is enabled
		// Data directories to create when module is enabled
		$this->values->dirs = array("/accounting/temp");

		// Boxes
		$this->values->boxes = array();

		// Permissions
		$this->values->rights = array();
		$this->values->rights_class = 'accounting';

		$this->values->rights[1][0] = 50401;
		$this->values->rights[1][1] = 'Lire le plan de compte';
		$this->values->rights[1][2] = 'r';
		$this->values->rights[1][3] = 1;
		$this->values->rights[1][4] = 'plancompte';
		$this->values->rights[1][5] = 'lire';

		$this->values->rights[2][0] = 50402;
		$this->values->rights[2][1] = 'Creer/modifier un plan de compte';
		$this->values->rights[2][2] = 'w';
		$this->values->rights[2][3] = 0;
		$this->values->rights[2][4] = 'plancompte';
		$this->values->rights[2][5] = 'creer';

		$this->values->rights[3][0] = 50403;
		$this->values->rights[3][1] = 'Cloturer plan de compte';
		$this->values->rights[3][2] = 'w';
		$this->values->rights[3][3] = 0;
		$this->values->rights[3][4] = 'plancompte';
		$this->values->rights[3][5] = 'cloturer';

		$this->values->rights[4][0] = 50411;
		$this->values->rights[4][1] = 'Lire les mouvements comptables';
		$this->values->rights[4][2] = 'r';
		$this->values->rights[4][3] = 1;
		$this->values->rights[4][4] = 'mouvements';
		$this->values->rights[4][5] = 'lire';

		$this->values->rights[5][0] = 50412;
		$this->values->rights[5][1] = 'Creer/modifier/annuler les mouvements comptables';
		$this->values->rights[5][2] = 'w';
		$this->values->rights[5][3] = 0;
		$this->values->rights[5][4] = 'mouvements';
		$this->values->rights[5][5] = 'creer';

		$this->values->rights[6][0] = 50415;
		$this->values->rights[6][1] = 'Lire CA, bilans, resultats, journaux, grands livres';
		$this->values->rights[6][2] = 'r';
		$this->values->rights[6][3] = 0;
		$this->values->rights[6][4] = 'comptarapport';
		$this->values->rights[6][5] = 'lire';
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
		//$this->values->remove($options);

		$sql = array();

		return $this->values->_init($sql, $options);
	}

	/**
	 * 		Function called when module is enabled.
	 * 		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 * 		It also creates data directories
	 *
	 *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function remove($options = '') {
		global $conf;

		$sql = array("DELETE FROM " . MAIN_DB_PREFIX . "const where name='MAIN_COMPANY_CODE_ALWAYS_REQUIRED' and entity IN ('0','" . $conf->entity . "')");

		return $this->values->_remove($sql, $options);
	}

}

?>
