<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/fourn/product/categorie.php
 *	\ingroup    product
 *	\brief      Page of products categories
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->load("categories");

$mesg = '';

if (!$user->rights->produit->lire && !$user->rights->service->lire) accessforbidden();

/*
 * Creation de l'objet produit correspondant a l'id
 */
if ($_GET["id"])
{
  $product = new Product($db);
  $result = $product->fetch($_GET["id"]);
}

llxHeader("","",$langs->trans("CardProduct0"));

/*
 * Fiche produit
 */
if ($_GET["id"])
{
  //on veut supprimer une cat�gorie
  if ($_REQUEST["cat"])
    {
      $cat = new Categorie($db,$_REQUEST["cat"]);
      $cat->del_product($product);
    }

  //on veut ajouter une cat�gorie
  if (isset($_REQUEST["add_cat"]) && $_REQUEST["add_cat"]>=0)
    {
      $cat = new Categorie($db,$_REQUEST["add_cat"]);
      $cat->add_product($product);
    }

  if ( $result )
    {

      /*
       *  En mode visu
       */

      $h=0;

      $head[$h][0] = DOL_URL_ROOT."/fourn/product/fiche.php?id=".$product->id;
      $head[$h][1] = $langs->trans("Card");
      $h++;


      if (! empty($conf->stock->enabled))
	{
	  $head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$product->id;
	  $head[$h][1] = $langs->trans("Stock");
	  $h++;
	}

      if (! empty($conf->fournisseur->enabled))
	{
	  $head[$h][0] = DOL_URL_ROOT."/product/fournisseurs.php?id=".$product->id;
	  $head[$h][1] = $langs->trans("Suppliers");
	  $h++;
	}

      $head[$h][0] = DOL_URL_ROOT."/product/photos.php?id=".$product->id;
      $head[$h][1] = $langs->trans("Photos");
      $h++;

      $head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$product->id;
      $head[$h][1] = $langs->trans('Statistics');
      $h++;

      //affichage onglet cat�gorie
      if (! empty($conf->categorie->enabled)){
	$head[$h][0] = DOL_URL_ROOT."/fourn/product/categorie.php?id=".$product->id;
	$head[$h][1] = $langs->trans('Categories');
	$hselected = $h;
	$h++;
      }


      dol_fiche_head($head, $hselected, $langs->trans("CardProduct".$product->type).' : '.$product->ref);

      print($mesg);
      print '<table class="border" width="100%">';
      print "<tr>";
      print '<td>'.$langs->trans("Ref").'</td><td>'.$product->ref.'</td>';

      print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';
      print "</table><br>\n";

      $c = new Categorie($db);
      $cats = $c->containing($_REQUEST['id'],0);

      if (count($cats) > 0)
	{
	  print "Vous avez stock� le produit dans les cat�gorie suivantes:<br/><br/>";
	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("AllWays").'</td></tr>';


	  foreach ($cats as $cat)
	    {

	      $ways = $cat->print_all_ways();
	      foreach ($ways as $way)
		{
		  $i = !$i;
		  print "<tr ".$bc[$i]."><td>".$way."</td>";
		  print "<td><a href= '".DOL_URL_ROOT."/fourn/product/categorie.php?id=".$product->id."&amp;cat=".$cat->id."'>".$langs->trans("DeleteFromCat")."</a></td></tr>\n";

		}

	    }
	  print "</table><br/><br/>\n";
	}
      else if($cats < 0)
	{
	  print $langs->trans("ErrorUnknown");
	}

      else
	{
	  print $langs->trans("NoCat")."<br/><br/>";
	}

    }

  print $langs->trans("AddProductToCat")."<br/><br/>";
  print '<table class="border" width="100%">';
  print '<form method="POST" action="'.DOL_URL_ROOT.'/fourn/product/categorie.php?id='.$product->id.'">';
  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
  print "<tr><td><select name='add_cat'><option value='-1'>".$langs->trans("Choose")."</option>";
  $cat = new Categorie($db);
  foreach ($cat->get_all_categories() as $categorie)
    {
      print "<option value='".$categorie->id."'>".$categorie->label."</option>\n";
    }
  print "</select></td><td><input type='submit' value='".$langs->trans("Select")."'></td></tr>";
  print "</form></table><br/>";

}
$db->close();


llxFooter();
?>

