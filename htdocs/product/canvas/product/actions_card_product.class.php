<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
 *	\file       htdocs/product/canvas/product/actions_card_product.class.php
 *	\ingroup    produit
 *	\brief      Fichier de la classe des produits par defaut
 */
include_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');

/**
 *	\class      ActionsCardProduct
 *	\brief      Class with controller methods for product canvas
 */
class ActionsCardProduct
{
	var $db;
    var $targetmodule;
    var $canvas;
    var $card;

    //! Template container
	var $tpl = array();

	/**
	 *    \brief      Constructeur de la classe
	 *    \param      DB          Handler acces base de donnees
	 *    \param      id          Id produit (0 par defaut)
	 */
	function ActionsCardProduct($DB=0, $id=0, $user=0)
	{
		$this->db 				= $DB;
		$this->id 				= $id ;
		$this->user 			= $user;
		$this->module 			= "produit";
		$this->canvas 			= "default";
		$this->name 			= "default";
		$this->definition 		= "Product canvas (défaut)";
		$this->fieldListName    = "product_default";

		$this->next_prev_filter = "canvas='default'";
	}

	function getTitle()
	{
		global $langs;

		return $langs->trans("Products");
	}

	/**
	 *    \brief      Lecture des donnees dans la base
	 *    \param      id          Product id
	 */
	function fetch($id='', $ref='', $action='')
	{
		$result = parent::fetch($id);

		return $result;
	}

	/**
	 *    Assign custom values for canvas (for example into this->tpl to be used by templates)
	 *
	 *    @param      action	Type of action
	 */
	function assign_values($action='')
	{
		global $conf,$langs,$user;
		global $html, $formproduct;

		$this->tpl['finished'] = $this->object->finished;
		$this->tpl['ref'] = $this->object->ref;
		$this->tpl['label'] = $this->object->label;
		$this->tpl['id'] = $this->object->id;
		$this->tpl['type'] = $this->object->type;
		$this->tpl['note'] = $this->object->note;
		$this->tpl['seuil_stock_alerte'] = $this->object->seuil_stock_alerte;

		if ($action == 'create')
		{
			// Title
			$this->tpl['title'] = $langs->trans("NewProduct");
		}

		if ($action == 'edit')
		{
			$this->tpl['title'] = $langs->trans('Modify').' '.$langs->trans('Product').' : '.$this->object->ref;
		}

		if ($action == 'create' || $action == 'edit')
		{
    		// Status
    		$statutarray=array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
    		$this->tpl['status'] = $html->selectarray('statut',$statutarray,$_POST["statut"]);

    		$statutarray=array('1' => $langs->trans("ProductStatusOnBuy"), '0' => $langs->trans("ProductStatusNotOnBuy"));
    		$this->tpl['status_buy'] = $html->selectarray('statut_buy',$statutarray,$_POST["statut_buy"]);

		    // Finished
			$statutarray=array('1' => $langs->trans("Finished"), '0' => $langs->trans("RowMaterial"));
			$this->tpl['finished'] = $html->selectarray('finished',$statutarray,$this->object->finished);

			// Weight
			$this->tpl['weight'] = $this->object->weight;
			$this->tpl['weight_units'] = $formproduct->load_measuring_units("weight_units","weight",$this->object->weight_units);

			// Length
			$this->tpl['length'] = $this->object->length;
			$this->tpl['length_units'] = $formproduct->load_measuring_units("length_units","size",$this->object->length_units);

			// Surface
			$this->tpl['surface'] = $this->object->surface;
			$this->tpl['surface_units'] = $formproduct->load_measuring_units("surface_units","surface",$this->object->surface_units);

			// Volume
			$this->tpl['volume'] = $this->object->volume;
			$this->tpl['volume_units'] = $formproduct->load_measuring_units("volume_units","volume",$this->object->volume_units);
		}

		if ($action == 'view')
		{
    		$head=product_prepare_head($this->object, $user);
    		$titre=$langs->trans("CardProduct".$this->object->type);
    		$picto=($this->object->type==1?'service':'product');
    		$this->tpl['fiche_head']=dol_get_fiche_head($head, 'card', $titre, 0, $picto);

    		// Status
    		$this->tpl['status'] = $this->object->getLibStatut(2,0);
    		$this->tpl['status_buy'] = $this->object->getLibStatut(2,1);

    		// Photo
			$this->tpl['nblignes'] = 4;
			if ($this->object->is_photo_available($conf->product->dir_output))
			{
				$this->tpl['photos'] = $this->object->show_photos($conf->product->dir_output,1,1,0,0,0,80);
			}

			// Nature
			$this->tpl['finished'] = $this->object->getLibFinished();

			// Weight
			if ($this->object->weight != '')
			{
				$this->tpl['weight'] = $this->object->weight." ".measuring_units_string($this->object->weight_units,"weight");
			}

			// Length
			if ($this->object->length != '')
			{
				$this->tpl['length'] = $this->object->length." ".measuring_units_string($this->object->length_units,"size");
			}

			// Surface
			if ($this->object->surface != '')
			{
				$this->tpl['surface'] = $this->object->surface." ".measuring_units_string($this->object->surface_units,"surface");
			}

			// Volume
			if ($this->object->volume != '')
			{
				$this->tpl['volume'] = $this->object->volume." ".measuring_units_string($this->object->volume_units,"volume");
			}

    		$this->tpl['fiche_end']=dol_get_fiche_end();
		}
	}

	/**
	 * 	\brief	Fetch datas list
	 */
	function LoadListDatas($limit, $offset, $sortfield, $sortorder)
	{
		global $conf, $langs;

		$this->list_datas = array();

		//$_GET["sall"] = 'LL';
		// Clean parameters
		$sall=trim(isset($_GET["sall"])?$_GET["sall"]:$_POST["sall"]);

		foreach($this->field_list as $field)
		{
			if ($field['enabled'])
			{
				$fieldname = "s".$field['alias'];
				$$fieldname = trim(isset($_GET[$fieldname])?$_GET[$fieldname]:$_POST[$fieldname]);
			}
		}

		$sql = 'SELECT DISTINCT ';

		// Fields requiered
		$sql.= 'p.rowid, p.price_base_type, p.fk_product_type, p.seuil_stock_alerte';

		// Fields not requiered
		foreach($this->field_list as $field)
		{
			if ($field['enabled'])
			{
				$sql.= ", ".$field['name']." as ".$field['alias'];
			}
		}

		$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
		$sql.= " WHERE p.entity = ".$conf->entity;
		if (!$user->rights->produit->hidden) $sql.=' AND p.hidden = 0';

		if ($sall)
		{
			$clause = '';
			$sql.= " AND (";
			foreach($this->field_list as $field)
			{
				if ($field['enabled'])
				{
					$sql.= $clause." ".$field['name']." LIKE '%".$this->db->escape($sall)."%'";
					if ($clause=='') $clause = ' OR';
				}
			}
			$sql.= ")";
		}

		// Search fields
		foreach($this->field_list as $field)
		{
			if ($field['enabled'])
			{
				$fieldname = "s".$field['alias'];
				if (${$fieldname}) $sql.= " AND ".$field['name']." LIKE '%".$this->db->escape(${$fieldname})."%'";
			}
		}

		if (isset($_GET["tosell"]) && dol_strlen($_GET["tosell"]) > 0)
		{
			$sql.= " AND p.tosell = ".$this->db->escape($_GET["tosell"]);
		}
		if (isset($_GET["canvas"]) && dol_strlen($_GET["canvas"]) > 0)
		{
			$sql.= " AND p.canvas = '".$this->db->escape($_GET["canvas"])."'";
		}
		$sql.= $this->db->order($sortfield,$sortorder);
		$sql.= $this->db->plimit($limit + 1 ,$offset);
		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);

			$i = 0;
			while ($i < min($num,$limit))
			{
				$datas = array();

				$obj = $this->db->fetch_object($resql);

				$datas["id"]        = $obj->rowid;

				foreach($this->field_list as $field)
				{
					if ($field['enabled'])
					{
						$alias = $field['alias'];

						if ($alias == 'ref')
						{
							$this->id 		= $obj->rowid;
							$this->ref 		= $obj->$alias;
							$this->type 	= $obj->fk_product_type;
							$datas[$alias] 	= $this->getNomUrl(1,'',24);
						}
						else if ($alias == 'stock')
						{
							$this->load_stock();
							if ($this->stock_reel < $obj->seuil_stock_alerte) $datas[$alias] = $this->stock_reel.' '.img_warning($langs->trans("StockTooLow"));
							else $datas[$alias] = $this->stock_reel;
						}
						else if ($alias == 'label')	$datas[$alias] = dol_trunc($obj->$alias,40);
						else if (preg_match('/price/i',$alias))	$datas[$alias] = price($obj->$alias);
						else if ($alias == 'datem') $datas[$alias] = dol_print_date($this->db->jdate($obj->$alias),'day');
						else if ($alias == 'status') $datas[$alias] = $this->LibStatut($obj->$alias,5);
						else $datas[$alias] = $obj->$alias;
					}
				}

				array_push($this->list_datas,$datas);

				$i++;
			}
			$this->db->free($resql);
		}
		else
		{
			print $sql;
		}
	}

}

?>