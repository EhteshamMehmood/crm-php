<?php

/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2009-2010 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * or see http://www.gnu.org/
 */

/**
 * 	\file       htdocs/core/lib/product.lib.php
 * 	\brief      Ensemble de fonctions de base pour le module produit et service
 * 	\ingroup	product
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @param	User	$user		Object user
 * @return  array				Array of tabs to shoc
 */
function product_prepare_head($object, $user) {
    global $langs, $conf;
    $langs->load("products");

    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT . "/product/fiche.php?id=" . $object->id;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'card';
    $h++;

    $head[$h][0] = DOL_URL_ROOT . "/product/price.php?id=" . $object->id;
    $head[$h][1] = $langs->trans("CustomerPrices");
    $head[$h][2] = 'price';
    $h++;

    if (!empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire) {
        $head[$h][0] = DOL_URL_ROOT . "/product/fournisseurs.php?id=" . $object->id;
        $head[$h][1] = $langs->trans("SuppliersPrices");
        $head[$h][2] = 'suppliers';
        $h++;
    }

    $head[$h][0] = DOL_URL_ROOT . "/product/photos.php?id=" . $object->id;
    $head[$h][1] = $langs->trans("Photos");
    $head[$h][2] = 'photos';
    $h++;

    // Show category tab
    if (!empty($conf->categorie->enabled) && $user->rights->categorie->lire) {
        $head[$h][0] = DOL_URL_ROOT . "/categories/categorie.php?id=" . $object->id . '&type=0';
        $head[$h][1] = $langs->trans('Categories');
        $head[$h][2] = 'category';
        $h++;
    }

    // Multilangs
    if (!empty($conf->global->MAIN_MULTILANGS)) {
        $head[$h][0] = DOL_URL_ROOT . "/product/traduction.php?id=" . $object->id;
        $head[$h][1] = $langs->trans("Translation");
        $head[$h][2] = 'translation';
        $h++;
    }

    // Sub products
    if (!empty($conf->global->PRODUIT_SOUSPRODUITS)) {
        $head[$h][0] = DOL_URL_ROOT . "/product/composition/fiche.php?id=" . $object->id;
        $head[$h][1] = $langs->trans('AssociatedProducts');
        $head[$h][2] = 'subproduct';
        $h++;
    }

    $head[$h][0] = DOL_URL_ROOT . "/product/stats/fiche.php?id=" . $object->id;
    $head[$h][1] = $langs->trans('Statistics');
    $head[$h][2] = 'stats';
    $h++;

    $head[$h][0] = DOL_URL_ROOT . "/product/stats/facture.php?id=" . $object->id;
    $head[$h][1] = $langs->trans('Referers');
    $head[$h][2] = 'referers';
    $h++;

    if ($object->isproduct()) {    // Si produit stockable
        if (!empty($conf->stock->enabled) && $user->rights->stock->lire) {
            $head[$h][0] = DOL_URL_ROOT . "/product/stock/product.php?id=" . $object->id;
            $head[$h][1] = $langs->trans("Stock");
            $head[$h][2] = 'stock';
            $h++;
        }
    }

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'product');

    $head[$h][0] = DOL_URL_ROOT . '/product/document.php?id=' . $object->id;
    $head[$h][1] = $langs->trans('Documents');
    $head[$h][2] = 'documents';
    $h++;


    // More tabs from canvas
    // TODO Is this still used ?
    if (isset($object->onglets) && is_array($object->onglets)) {
        foreach ($object->onglets as $onglet) {
            $head[$h] = $onglet;
            $h++;
        }
    }

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'product', 'remove');

    return $head;
}

/**
 *  Return array head with list of tabs to view object informations.
 *
 *  @param	Object	$object		Product
 *  @return	array   	        head array with tabs
 */
function product_admin_prepare_head($object = null) {
    global $langs, $conf, $user;

    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT . "/product/admin/product.php";
    $head[$h][1] = $langs->trans('Parameters');
    $head[$h][2] = 'general';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'product_admin');

    $head[$h][0] = DOL_URL_ROOT . '/product/admin/product_extrafields.php';
    $head[$h][1] = $langs->trans("ExtraFields");
    $head[$h][2] = 'attributes';
    $h++;

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'product_admin', 'remove');

    return $head;
}

/**
 * Show stats for company
 *
 * @param	Product		$product	Product object
 * @param 	int			$socid		Thirdparty id
 * @return	void
 */
function show_stats_for_company($product, $socid) {
    global $conf, $langs, $user, $db;

    print '<tr>';
    print '<td align="left" width="25%" valign="top">' . $langs->trans("Referers") . '</td>';
    print '<td align="right" width="25%">' . $langs->trans("NbOfThirdParties") . '</td>';
    print '<td align="right" width="25%">' . $langs->trans("NbOfReferers") . '</td>';
    print '<td align="right" width="25%">' . $langs->trans("TotalQuantity") . '</td>';
    print '</tr>';

    // Propals
    if (!empty($conf->propal->enabled) && $user->rights->propale->lire) {
        $ret = $product->load_stats_propale($socid);
        if ($ret < 0)
            dol_print_error($db);
        $langs->load("propal");
        print '<tr><td>';
        print '<a href="propal.php?id=' . $product->id . '">' . img_object('', 'propal') . ' ' . $langs->trans("Proposals") . '</a>';
        print '</td><td align="right">';
        print $product->stats_propale['customers'];
        print '</td><td align="right">';
        print $product->stats_propale['nb'];
        print '</td><td align="right">';
        print $product->stats_propale['qty'];
        print '</td>';
        print '</tr>';
    }
    // Commandes clients
    if (!empty($conf->commande->enabled) && $user->rights->commande->lire) {
        $ret = $product->load_stats_commande($socid);
        if ($ret < 0)
            dol_print_error($db);
        $langs->load("orders");
        print '<tr><td>';
        print '<a href="commande.php?id=' . $product->id . '">' . img_object('', 'order') . ' ' . $langs->trans("CustomersOrders") . '</a>';
        print '</td><td align="right">';
        print $product->stats_commande['customers'];
        print '</td><td align="right">';
        print $product->stats_commande['nb'];
        print '</td><td align="right">';
        print $product->stats_commande['qty'];
        print '</td>';
        print '</tr>';
    }
    // Commandes fournisseurs
    if (!empty($conf->fournisseur->enabled) && $user->rights->fournisseur->commande->lire) {
        $ret = $product->load_stats_commande_fournisseur($socid);
        if ($ret < 0)
            dol_print_error($db);
        $langs->load("orders");
        print '<tr><td>';
        print '<a href="commande_fournisseur.php?id=' . $product->id . '">' . img_object('', 'order') . ' ' . $langs->trans("SuppliersOrders") . '</a>';
        print '</td><td align="right">';
        print $product->stats_commande_fournisseur['suppliers'];
        print '</td><td align="right">';
        print $product->stats_commande_fournisseur['nb'];
        print '</td><td align="right">';
        print $product->stats_commande_fournisseur['qty'];
        print '</td>';
        print '</tr>';
    }
    // Contrats
    if (!empty($conf->contrat->enabled) && $user->rights->contrat->lire) {
        $ret = $product->load_stats_contrat($socid);
        if ($ret < 0)
            dol_print_error($db);
        $langs->load("contracts");
        print '<tr><td>';
        print '<a href="contrat.php?id=' . $product->id . '">' . img_object('', 'contract') . ' ' . $langs->trans("Contracts") . '</a>';
        print '</td><td align="right">';
        print $product->stats_contrat['customers'];
        print '</td><td align="right">';
        print $product->stats_contrat['nb'];
        print '</td><td align="right">';
        print $product->stats_contrat['qty'];
        print '</td>';
        print '</tr>';
    }
    // Factures clients
    if (!empty($conf->facture->enabled) && $user->rights->facture->lire) {
        $ret = $product->load_stats_facture($socid);
        if ($ret < 0)
            dol_print_error($db);
        $langs->load("bills");
        print '<tr><td>';
        print '<a href="facture.php?id=' . $product->id . '">' . img_object('', 'bill') . ' ' . $langs->trans("CustomersInvoices") . '</a>';
        print '</td><td align="right">';
        print $product->stats_facture['customers'];
        print '</td><td align="right">';
        print $product->stats_facture['nb'];
        print '</td><td align="right">';
        print $product->stats_facture['qty'];
        print '</td>';
        print '</tr>';
    }
    // Factures fournisseurs
    if (!empty($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->lire) {
        $ret = $product->load_stats_facture_fournisseur($socid);
        if ($ret < 0)
            dol_print_error($db);
        $langs->load("bills");
        print '<tr><td>';
        print '<a href="facture_fournisseur.php?id=' . $product->id . '">' . img_object('', 'bill') . ' ' . $langs->trans("SuppliersInvoices") . '</a>';
        print '</td><td align="right">';
        print $product->stats_facture_fournisseur['suppliers'];
        print '</td><td align="right">';
        print $product->stats_facture_fournisseur['nb'];
        print '</td><td align="right">';
        print $product->stats_facture_fournisseur['qty'];
        print '</td>';
        print '</tr>';
    }

    return 0;
}

?>
