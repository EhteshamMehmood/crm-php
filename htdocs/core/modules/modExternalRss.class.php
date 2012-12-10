<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2012 Herve Prot           <herve.prot@symeos.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 *  \defgroup   externalrss     Module externalrss
 *	\brief      Module pour inclure des informations externes RSS
 *	\file       htdocs/core/modules/modExternalRss.class.php
 *	\ingroup    externalrss
 *	\brief      Fichier de description et activation du module externalrss
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Classe de description et activation du module externalrss
 */
class modExternalRss extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf;

		parent::__construct($db);
		$this->numero = 320;

		$this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Ajout de files d'informations RSS dans les ecrans Dolibarr";
		$this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 1;
		$this->picto='rss';

		// Data directories to create when module is enabled
		$this->dirs = array("/externalrss/temp");

		// Config pages
		$this->config_page_url = array("external_rss.php");

		// Dependances
		$this->depends = array();
		$this->requiredby = array();
		$this->phpmin = array(4,2,0);
		$this->phpmax = array();

		// Constantes
		$this->const = array();

		// Boxes
		$this->boxes = array();
		// Les boites sont ajoutees lors de la configuration des flux

		// Permissions
		$this->rights = array();
		$this->rights_class = 'externalrss';
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $conf;

		$sql = array();

		// Recherche configuration de boites
		$this->boxes=array();
		$sql="select name, value from ".MAIN_DB_PREFIX."const";
		$sql.= " WHERE name like 'EXTERNAL_RSS_TITLE_%'";
		$sql.= " AND entity = ".$conf->entity;
		$result=$this->db->query($sql);
		if ($result)
		{
			while ($obj = $this->db->fetch_object($result))
			{
				if (preg_match('/EXTERNAL_RSS_TITLE_([0-9]+)/i',$obj->name,$reg))
				{
					// Definie la boite si on a trouvee une ancienne configuration
					$this->boxes[$reg[1]][0] = "(ExternalRSSInformations)";
					$this->boxes[$reg[1]][1] = "box_external_rss.php";
					$this->boxes[$reg[1]][2] = $reg[1]." (".$obj->value.")";
				}
			}
			$this->db->free($result);
		}

		$sql = array();

		return $this->_init($sql,$options);
	}

    /**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
     */
    function remove($options='')
    {
		$sql = array();

		// Delete old declarations of RSS box
		$this->boxes[0][1] = "box_external_rss.php";

		return $this->_remove($sql,$options);
    }

}
?>
