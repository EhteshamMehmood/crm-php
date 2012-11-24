<?php

/* Copyright (C) 2003-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2012 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2011      Philippe Grand        <philippe.grand@atoo-net.com>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2012      Marcos García         <marcosgdf@gmail.com>
 * Copyright (C) 2012      David Moothen         <dmoothen@gmail.com>
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
 *	\file       htdocs/commande/fiche.php
 *	\ingroup    commande
 *	\brief      Page to show customer order
 */


/* Includes ******************************************************************/


require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formorder.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/core/modules/commande/modules_commande.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
if (! empty($conf->propal->enabled))
	require DOL_DOCUMENT_ROOT.'/propal/class/propal.class.php';
if (! empty($conf->projet->enabled)) {
	require DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
}


/* Loading langs *************************************************************/


$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('propal');
$langs->load('deliveries');
$langs->load('products');
if (! empty($conf->margin->enabled))
	$langs->load('margins');


/* Post params ***************************************************************/


$id = GETPOST('id', 'alpha');
$action = (GETPOST('action', 'alpha') ? GETPOST('action', 'alpha') : 'view');
$confirm = GETPOST('confirm');
$lineid = GETPOST('lineid', 'alpha');
$origin=GETPOST('origin','alpha');
$originid=(GETPOST('originid','alpha')?GETPOST('originid','alpha'):GETPOST('origin_id','alpha')); // For backward compatibility


$title = $langs->trans('Order');

$object = new Commande($db);
$soc = new Societe($db);
if (!empty($id)) {
    $object->fetch($id);
    $soc->fetch($object->socid);
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('ordercard'));


/* Actions *******************************************************************/


if ($action == 'add' && $user->rights->commande->creer) {
    
    $datecommande  = dol_mktime(12, 0, 0, GETPOST('remonth'),  GETPOST('reday'),  GETPOST('reyear'));
    $datelivraison = dol_mktime(12, 0, 0, GETPOST('liv_month'),GETPOST('liv_day'),GETPOST('liv_year'));

    $object->socid = GETPOST('socid');
    $object->date_commande = $datecommande;
    $object->note                 = GETPOST('note');
    $object->note_public          = GETPOST('note_public');
    $object->source               = GETPOST('source_id');
    $object->fk_project           = GETPOST('projectid');
    $object->ref_client           = GETPOST('ref_client');
    $object->modelpdf             = GETPOST('model');
    $object->cond_reglement_code    = GETPOST('cond_reglement_code');
    $object->mode_reglement_code    = GETPOST('mode_reglement_code');
    $object->availability_code      = GETPOST('availability_code');
    $object->demand_reason_code     = GETPOST('demand_reason_code');
    $object->date_livraison       = $datelivraison;
    $object->fk_delivery_address  = GETPOST('fk_address');
    $object->contactid            = GETPOST('contactidp');
    
    $id = $object->create($user);
    
    if (!empty($id)) {
        header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
        exit;
    }
}


else if ($action == 'update' && $user->rights->commande->creer) {
    
    $datecommande  = dol_mktime(12, 0, 0, GETPOST('remonth'),  GETPOST('reday'),  GETPOST('reyear'));
    $datelivraison = dol_mktime(12, 0, 0, GETPOST('liv_month'),GETPOST('liv_day'),GETPOST('liv_year'));

    $object->socid = GETPOST('socid');
    $object->date_commande = $datecommande;
    $object->note                 = GETPOST('note');
    $object->note_public          = GETPOST('note_public');
    $object->source               = GETPOST('source_id');
    $object->fk_project           = GETPOST('projectid');
    $object->ref_client           = GETPOST('ref_client');
    $object->modelpdf             = GETPOST('model');
    $object->cond_reglement_code    = GETPOST('cond_reglement_code');
    $object->mode_reglement_code    = GETPOST('mode_reglement_code');
    $object->availability_code      = GETPOST('availability_code');
    $object->demand_reason_code     = GETPOST('demand_reason_code');
    $object->date_livraison       = $datelivraison;
    $object->fk_delivery_address  = GETPOST('fk_address');
    $object->contactid            = GETPOST('contactidp');
    
    $id = $object->update();
    if (!empty($id)) {
        header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
        exit;
    }
    
}


else if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->commande->supprimer)
{
	$result=$object->delete($user);
	if ($result > 0) {
		header('Location: index.php');
		exit;
	} else {
		$mesg='<div class="error">'.$object->error.'</div>';
	}
}


 else if ($action == 'addline' && $user->rights->commande->creer) {
     
    $langs->load('errors');
    $error = false;

    $idprod = GETPOST('idprod', 'int');
    $product_desc = (GETPOST('product_desc') ? GETPOST('product_desc') : (GETPOST('np_desc') ? GETPOST('np_desc') : (GETPOST('dp_desc') ? GETPOST('dp_desc') : '')));
    $price_ht = GETPOST('price_ht');
    $tva_tx = GETPOST('tva_tx');

    if ((empty($idprod) || GETPOST('usenewaddlineform')) && ($price_ht < 0) && (GETPOST('qty') < 0)) {
        setEventMessage($langs->trans('ErrorBothFieldCantBeNegative', $langs->transnoentitiesnoconv('UnitPriceHT'), $langs->transnoentitiesnoconv('Qty')), 'errors');
        $error = true;
    }
    if (empty($idprod) && GETPOST('type') < 0) {
        setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type')), 'errors');
        $error = true;
    }
    if ((empty($idprod) || GETPOST('usenewaddlineform')) && (!($price_ht >= 0) || $price_ht == '')) { // Unit price can be 0 but not ''
        setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("UnitPriceHT")), 'errors');
        $error++;
    }
    if (!GETPOST('qty') && GETPOST('qty') == '') {
        setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), 'errors');
        $error = true;
    }
    if (empty($idprod) && empty($product_desc)) {
        setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Description')), 'errors');
        $error = true;
    }

    if (!$error && (GETPOST('qty') >= 0) && (!empty($product_desc) || !empty($idprod))) {
        // Clean parameters
        $predef = ((!empty($idprod) && $conf->global->MAIN_FEATURES_LEVEL < 2) ? '_predef' : '');
        $date_start = dol_mktime(0, 0, 0, GETPOST('date_start' . $predef . 'month'), GETPOST('date_start' . $predef . 'day'), GETPOST('date_start' . $predef . 'year'));
        $date_end = dol_mktime(0, 0, 0, GETPOST('date_end' . $predef . 'month'), GETPOST('date_end' . $predef . 'day'), GETPOST('date_end' . $predef . 'year'));
        $price_base_type = (GETPOST('price_base_type', 'alpha') ? GETPOST('price_base_type', 'alpha') : 'HT');

        // Ecrase $pu par celui du produit
        // Ecrase $desc par celui du produit
        // Ecrase $txtva par celui du produit
        // Ecrase $base_price_type par celui du produit
        if (!empty($idprod)) {
            $prod = new Product($db);
            $prod->fetch($idprod);

            $label = ((GETPOST('product_label') && GETPOST('product_label') != $prod->label) ? GETPOST('product_label') : '');

            // Update if prices fields are defined
            if (GETPOST('usenewaddlineform')) {
                $pu_ht = price2num($price_ht, 'MU');
                $pu_ttc = price2num(GETPOST('price_ttc'), 'MU');
                $tva_npr = (preg_match('/\*/', $tva_tx) ? 1 : 0);
                $tva_tx = str_replace('*', '', $tva_tx);
                $desc = $product_desc;
            } else {
                $tva_tx = get_default_tva($mysoc, $object->client, $prod->id);
                $tva_npr = get_default_npr($mysoc, $object->client, $prod->id);

                // multiprix
                if (!empty($conf->global->PRODUIT_MULTIPRICES) && !empty($object->client->price_level)) {
                    $pu_ht = $prod->multiprices[$object->client->price_level];
                    $pu_ttc = $prod->multiprices_ttc[$object->client->price_level];
                    $price_min = $prod->multiprices_min[$object->client->price_level];
                    $price_base_type = $prod->multiprices_base_type[$object->client->price_level];
                } else {
                    $pu_ht = $prod->price;
                    $pu_ttc = $prod->price_ttc;
                    $price_min = $prod->price_min;
                    $price_base_type = $prod->price_base_type;
                }

                // On reevalue prix selon taux tva car taux tva transaction peut etre different
                // de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
                if ($tva_tx != $prod->tva_tx) {
                    if ($price_base_type != 'HT') {
                        $pu_ht = price2num($pu_ttc / (1 + ($tva_tx / 100)), 'MU');
                    } else {
                        $pu_ttc = price2num($pu_ht * (1 + ($tva_tx / 100)), 'MU');
                    }
                }

                $desc = '';

                // Define output language
                if (!empty($conf->global->MAIN_MULTILANGS) && !empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
                    $outputlangs = $langs;
                    $newlang = '';
                    if (empty($newlang) && GETPOST('lang_id'))
                        $newlang = GETPOST('lang_id');
                    if (empty($newlang))
                        $newlang = $object->client->default_lang;
                    if (!empty($newlang)) {
                        $outputlangs = new Translate("", $conf);
                        $outputlangs->setDefaultLang($newlang);
                    }

                    $desc = (!empty($prod->multilangs[$outputlangs->defaultlang]["description"])) ? $prod->multilangs[$outputlangs->defaultlang]["description"] : $prod->description;
                } else {
                    $desc = $prod->description;
                }

                $desc = dol_concatdesc($desc, $product_desc);
            }

            $type = $prod->type;
        } else {
            $pu_ht = price2num($price_ht, 'MU');
            $pu_ttc = price2num(GETPOST('price_ttc'), 'MU');
            $tva_npr = (preg_match('/\*/', $tva_tx) ? 1 : 0);
            $tva_tx = str_replace('*', '', $tva_tx);
            $label = (GETPOST('product_label') ? GETPOST('product_label') : '');
            $desc = $product_desc;
            $type = GETPOST('type');
        }

        // Margin
        $fournprice = (GETPOST('fournprice') ? GETPOST('fournprice') : '');
        $buyingprice = (GETPOST('buying_price') ? GETPOST('buying_price') : '');

        // Local Taxes
        $localtax1_tx = get_localtax($tva_tx, 1, $object->client);
        $localtax2_tx = get_localtax($tva_tx, 2, $object->client);

        $desc = dol_htmlcleanlastbr($desc);

        $info_bits = 0;
        if ($tva_npr)
            $info_bits |= 0x01;

        if (!empty($price_min) && (price2num($pu_ht) * (1 - price2num(GETPOST('remise_percent')) / 100) < price2num($price_min))) {
            $mesg = $langs->trans("CantBeLessThanMinPrice", price2num($price_min, 'MU') . getCurrencySymbol($conf->currency));
            setEventMessage($mesg, 'errors');
        } else {
            // Insert line
            $result = $object->addline(
                    $object->id, $desc, $pu_ht, GETPOST('qty'), $tva_tx, $localtax1_tx, $localtax2_tx, $idprod, GETPOST('remise_percent'), $info_bits, 0, $price_base_type, $pu_ttc, $date_start, $date_end, $type, -1, 0, GETPOST('fk_parent_line'), $fournprice, $buyingprice, $label
            );

            if ($result > 0) {
                if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                    // Define output language
                    $outputlangs = $langs;
                    $newlang = GETPOST('lang_id', 'alpha');
                    if (!empty($conf->global->MAIN_MULTILANGS) && empty($newlang))
                        $newlang = $object->client->default_lang;
                    if (!empty($newlang)) {
                        $outputlangs = new Translate("", $conf);
                        $outputlangs->setDefaultLang($newlang);
                    }

                    $ret = $object->fetch($object->id);    // Reload to get new records
                    commande_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $hookmanager);
                }

                unset($_POST['qty']);
                unset($_POST['type']);
                unset($_POST['idprod']);
                unset($_POST['remise_percent']);
                unset($_POST['price_ht']);
                unset($_POST['price_ttc']);
                unset($_POST['tva_tx']);
                unset($_POST['product_ref']);
                unset($_POST['product_label']);
                unset($_POST['product_desc']);
                unset($_POST['fournprice']);
                unset($_POST['buying_price']);

                // old method
                unset($_POST['np_desc']);
                unset($_POST['dp_desc']);
            } else {
                setEventMessage($object->error, 'errors');
            }
        }
    }
}


 else if ($action == 'updateligne' && $user->rights->commande->creer && GETPOST('save') == $langs->trans('Save')) {
    // Clean parameters
    $date_start = '';
    $date_end = '';
    $date_start = dol_mktime(0, 0, 0, GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
    $date_end = dol_mktime(0, 0, 0, GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
    $description = dol_htmlcleanlastbr(GETPOST('product_desc'));
    $pu_ht = GETPOST('price_ht');

    // Define info_bits
    $info_bits = 0;
    if (preg_match('/\*/', GETPOST('tva_tx')))
        $info_bits |= 0x01;

    // Define vat_rate
    $vat_rate = GETPOST('tva_tx');
    $vat_rate = str_replace('*', '', $vat_rate);
    $localtax1_rate = get_localtax($vat_rate, 1, $object->client);
    $localtax2_rate = get_localtax($vat_rate, 2, $object->client);

    // Add buying price
    $fournprice = (GETPOST('fournprice') ? GETPOST('fournprice') : '');
    $buyingprice = (GETPOST('buying_price') ? GETPOST('buying_price') : '');

    // Check minimum price
    $productid = GETPOST('productid', 'int');
    if (!empty($productid)) {
        $product = new Product($db);
        $product->fetch($productid);

        $type = $product->type;

        $price_min = $product->price_min;
        if (!empty($conf->global->PRODUIT_MULTIPRICES) && !empty($object->client->price_level))
            $price_min = $product->multiprices_min[$object->client->price_level];

        $label = ((GETPOST('update_label') && GETPOST('product_label')) ? GETPOST('product_label') : '');

        if ($price_min && (price2num($pu_ht) * (1 - price2num(GETPOST('remise_percent')) / 100) < price2num($price_min))) {
            setEventMessage($langs->trans("CantBeLessThanMinPrice", price2num($price_min, 'MU')) . getCurrencySymbol($conf->currency), 'errors');
            $error++;
        }
    } else {
        $type = GETPOST('type');
        $label = (GETPOST('product_label') ? GETPOST('product_label') : '');

        // Check parameters
        if (GETPOST('type') < 0) {
            setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), 'errors');
            $error++;
        }
    }

    if (!$error) {
        $result = $object->updateline(
                GETPOST('lineid'), $description, $pu_ht, GETPOST('qty'), GETPOST('remise_percent'), $vat_rate, $localtax1_rate, $localtax2_rate, 'HT', $info_bits, $date_start, $date_end, $type, GETPOST('fk_parent_line'), 0, $fournprice, $buyingprice, $label
        );

        if ($result >= 0) {
            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                // Define output language
                $outputlangs = $langs;
                $newlang = '';
                if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id'))
                    $newlang = GETPOST('lang_id');
                if ($conf->global->MAIN_MULTILANGS && empty($newlang))
                    $newlang = $object->client->default_lang;
                if (!empty($newlang)) {
                    $outputlangs = new Translate("", $conf);
                    $outputlangs->setDefaultLang($newlang);
                }

                $ret = $object->fetch($object->id);    // Reload to get new records
                commande_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $hookmanager);
            }

            unset($_POST['qty']);
            unset($_POST['type']);
            unset($_POST['productid']);
            unset($_POST['remise_percent']);
            unset($_POST['price_ht']);
            unset($_POST['price_ttc']);
            unset($_POST['tva_tx']);
            unset($_POST['product_ref']);
            unset($_POST['product_label']);
            unset($_POST['product_desc']);
            unset($_POST['fournprice']);
            unset($_POST['buying_price']);
        } else {
            setEventMessage($object->error, 'errors');
        }
    }
}


 else if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->commande->creer) {
    $result = $object->deleteline($lineid);
    if ($result > 0) {
        // Define output language
        $outputlangs = $langs;
        $newlang = '';
        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id'))
            $newlang = GETPOST('lang_id');
        if ($conf->global->MAIN_MULTILANGS && empty($newlang))
            $newlang = $object->client->default_lang;
        if (!empty($newlang)) {
            $outputlangs = new Translate("", $conf);
            $outputlangs->setDefaultLang($newlang);
        }
        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
            $ret = $object->fetch($object->id);    // Reload to get new records
            commande_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $hookmanager);
        }

        header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
        exit;
    } else {
        $mesg = '<div class="error">' . $object->error . '</div>';
    }
}


 else if ($action == 'builddoc') { // In get or post
    /*
     * Generate order document
     * define into /core/modules/commande/modules_commande.php
     */

    // Sauvegarde le dernier modele choisi pour generer un document
    if ($_REQUEST['model']) {
        $object->setDocModel($user, $_REQUEST['model']);
    }

    // Define output language
    $outputlangs = $langs;
    $newlang = '';
    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && !empty($_REQUEST['lang_id']))
        $newlang = $_REQUEST['lang_id'];
    if ($conf->global->MAIN_MULTILANGS && empty($newlang))
        $newlang = $object->client->default_lang;
    if (!empty($newlang)) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang($newlang);
    }
    $result = commande_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $hookmanager);

    if ($result <= 0) {
        dol_print_error($db, $result);
        exit;
    } else {
        header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . (empty($conf->global->MAIN_JUMP_TAG) ? '' : '#builddoc'));
        exit;
    }
}


/* View **********************************************************************/

$form = new Form($db);
$formfile = new FormFile($db);
$formorder = new FormOrder($db);

llxHeader('',$title,'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes');
print_fiche_titre($title);

$formconfirm = null;

if ($action == 'delete') {
    $formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteOrder'), $langs->trans('ConfirmDeleteOrder'), 'confirm_delete', '', 0, 1);
}

 else if ($action == 'ask_deleteline') {
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&lineid=' . $lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 0, 1);
}

print $formconfirm;

print '<div class="with-padding" >';
print '<div class="columns" >';


/* Create View */


if (($action == 'create' || $action == 'edit') && $user->rights->commande->creer) {
    
    print start_box($title, "twelve", $object->fk_extrafields->ico, false);
    print '<form name="crea_commande" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="'.($action == 'create' ? 'add' : 'update').'">';
    print '<input type="hidden" name="id" value="'.$id.'">';
    print '<input type="hidden" name="origin" value="'.$origin.'">';
    print '<input type="hidden" name="originid" value="'.$originid.'">';
    
    print '<table class="border" width="100%">';

    // Reference
    print '<tr><td class="fieldrequired">'.$langs->trans('Ref').'</td><td colspan="2">'.($action == 'edit' ? $object->ref : $langs->trans("Draft")).'</td></tr>';

    // Reference client
    print '<tr><td>'.$langs->trans('RefCustomer').'</td><td colspan="2">';
    print '<input type="text" name="ref_client" value="' . $object->ref_client . '"></td>';
    print '</tr>';
    
    // Client
    print '<tr><td class="fieldrequired">'.$langs->trans('Customer').'</td><td colspan="2">'.$form->select_company($object->socid, "socid").'</td></tr>';

   // Contact de la commande
    print "<tr><td>".$langs->trans("DefaultContact").'</td><td colspan="2">';
    $form->select_contacts($soc->id,$setcontact,'contactidp',1,$srccontactslist);
    print '</td></tr>';

    // Date
    print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td colspan="2">';
    $form->select_date($object->date,'re','','','',"crea_commande",1,1);
    print '</td></tr>';
    
    // Date de livraison
    print "<tr><td>".$langs->trans("DeliveryDate").'</td><td colspan="2">';
    if ($action == 'edit') {
        $datedelivery = $object->date_livraison;
    } else if (! empty($conf->global->DATE_LIVRAISON_WEEK_DELAY)) {
        $datedelivery = time() + ((7*$conf->global->DATE_LIVRAISON_WEEK_DELAY) * 24 * 60 * 60);
    } else {
        $datedelivery=empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;
    }
    $form->select_date($datedelivery,'liv_','','','',"crea_commande",1,1);
    print "</td></tr>";
    
    // Conditions de reglement
    print '<tr><td nowrap="nowrap">'.$langs->trans('PaymentConditionsShort').'</td><td colspan="2">';
    print $object->select_fk_extrafields('cond_reglement_code', 'cond_reglement_code', $object->cond_reglement_code);
    print '</td></tr>';
    
    // Delivery delay
    print '<tr><td>'.$langs->trans('AvailabilityPeriod').'</td><td colspan="2">';
    print $object->select_fk_extrafields('availability_code', 'availability_code', $object->availability_code);
    print '</td></tr>';
    
    // Mode de reglement
    print '<tr><td nowrap="nowrap">'.$langs->trans('PaymentMode').'</td><td colspan="2">';
    print $object->select_fk_extrafields('mode_reglement_code', 'mode_reglement_code', $object->mode_reglement_code);
    print '</td></tr>';

    // What trigger creation
    print '<tr><td>'.$langs->trans('Source').'</td><td colspan="2">';
    print $object->select_fk_extrafields('demand_reason_code', 'demand_reason_code', $object->demand_reason_code);
    print '</td></tr>';
                
    // Project
    if (! empty($conf->projet->enabled)) {
        $projectid = 0;
        if ($origin == 'project') $projectid = ($originid?$originid:0);
        print '<tr><td>'.$langs->trans('Project').'</td><td colspan="2">';
        $numprojet=select_projects($soc->id,$projectid);
        if ($numprojet==0) {
            print ' &nbsp; <a href="'.DOL_URL_ROOT.'/projet/fiche.php?socid='.$soc->id.'&action=create">'.$langs->trans("AddProject").'</a>';
        }
        print '</td></tr>';
    }
    
    // Other attributes
    $parameters=array('objectsrc' => $objectsrc, 'colspan' => ' colspan="3"');
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
    if (empty($reshook) && ! empty($extrafields->attribute_label)) {
        foreach($extrafields->attribute_label as $key=>$label) {
            $value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$object->array_options["options_".$key]);
            print "<tr><td>".$label.'</td><td colspan="3">';
            print $extrafields->showInputField($key,$value);
            print '</td></tr>'."\n";
        }
    }

    // Template to use by default
    print '<tr><td>'.$langs->trans('Model').'</td>';
    print '<td colspan="2">';
    include_once DOL_DOCUMENT_ROOT.'/commande/core/modules/commande/modules_commande.php';
    $liste=ModelePDFCommandes::liste_modeles($db);
    print $form->selectarray('model',$liste,$conf->global->COMMANDE_ADDON_PDF);
    print "</td></tr>";
    
    // Note publique
    print '<tr>';
    print '<td class="border" valign="top">'.$langs->trans('NotePublic').'</td>';
    print '<td valign="top" colspan="2">';
    $doleditor = new DolEditor('note_public', $object->note, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
    print $doleditor->Create(1);
    print '</td></tr>';
    
    // Note privee
    if (! $user->societe_id) {
        print '<tr>';
        print '<td class="border" valign="top">'.$langs->trans('NotePrivate').'</td>';
        print '<td valign="top" colspan="2">';
        $doleditor=new DolEditor('note', $object->note, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
        print $doleditor->Create(1);
        print '</td></tr>';
    }

    print '</table>';
    
    // Button "Create Draft"
    print '<br><center><input type="submit" class="button" name="bouton" value="'. ($action =='edit' ? $langs->trans('Modify') : $langs->trans('CreateDraft')) . '"></center>';

    print '</form>';
    print end_box();
}


/* Default View */


else {
    
    // Actions
    if ($user->rights->commande->supprimer) {
        print '<p class="button-height right">';
        print '<a class="button icon-cross" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete">' . $langs->trans("Delete") . '</a>';
        print "</p>";
    }
    if ($object->Status == "DRAFT" && $user->rights->commande->creer) {
        print '<p class="button-height right">';
        print '<a class="button icon-pencil" href="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&action=edit">' . $langs->trans("Modify") . '</a>';
        print "</p>";
    }
    
    print start_box($title, "twelve", $object->fk_extrafields->ico, false);
    print '<table class="border" width="100%">';
    
    // Ref
    print '<tr><td width="18%">'.$langs->trans('Ref').'</td>';
    print '<td colspan="3">';
    print $object->ref;
    print '</td>';
    print '</tr>';
       
    // Ref commande client
    print '<tr><td>'. $langs->trans('RefCustomer').'</td>';
    print '<td colspan="3">' . $object->ref_client . '</td>';
    print '</tr>';
    
    // Societe
    print '<tr><td>'.$langs->trans('Company').'</td>';
    print '<td colspan="3">'.$object->client->name.'</td>';
    print '</tr>';

    // Date
    print '<tr><td>'.$langs->trans('Date').'</td>';
    print '<td colspan="3">'. ($object->date ? dol_print_date($object->date,'daytext') : '&nbsp;') .'</td>';
    print '</tr>';
    
    // Delivery date planed
    print '<tr><td>'.$langs->trans('DateDeliveryPlanned').'</td>';
    print '<td colspan="3">'. ($object->date_livraison ? dol_print_date($object->date_livraison,'daytext') : '&nbsp;') .'</td>';
    print '</tr>';
    
    // Terms of payment
    print '<tr><td>'.$langs->trans('PaymentConditionsShort').'</td>';
    print '<td colspan="3">'. $object->getExtraFieldLabel('cond_reglement_code') .'</td>';
    print '</tr>';

    // Mode of payment
    print '<tr><td>'.$langs->trans('PaymentMode').'</td>';
    print '<td colspan="3">'. $object->getExtraFieldLabel('mode_reglement_code') .'</td>';
    print '</tr>';
    
    // Availability
    print '<tr><td>'.$langs->trans('AvailabilityPeriod').'</td>';
    print '<td colspan="3">'. $object->getExtraFieldLabel('availability_code') .'</td>';
    print '</tr>';
    
    // Source
    print '<tr><td>'.$langs->trans('Source').'</td>';
    print '<td colspan="3">'. $object->getExtraFieldLabel('demand_reason_code') .'</td>';
    print '</tr>';
    
    // Total HT
    print '<tr><td>'.$langs->trans('AmountHT').'</td>';
    print '<td align="right"><b>'.price($object->total_ht).'</b></td>';
    print '<td>'.$langs->trans('Currency'.$conf->currency).'</td>';

    // Margin Infos
    if (! empty($conf->margin->enabled)) {
        print '<td valign="top" width="50%" rowspan="4">';
        $object->displayMarginInfos();
        print '</td>';
    }
    print '</tr>';

    // Total TVA
    print '<tr><td>'.$langs->trans('AmountVAT').'</td><td align="right">'.price($object->total_tva).'</td>';
    print '<td>'.$langs->trans('Currency'.$conf->currency).'</td></tr>';

    // Amount Local Taxes
    if ($mysoc->country_code=='ES')
    {
        if ($mysoc->localtax1_assuj=="1") //Localtax1 RE
        {
            print '<tr><td>'.$langs->transcountry("AmountLT1",$mysoc->country_code).'</td>';
            print '<td align="right">'.price($object->total_localtax1).'</td>';
            print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
        }
        if ($mysoc->localtax2_assuj=="1") //Localtax2 IRPF
        {
            print '<tr><td>'.$langs->transcountry("AmountLT2",$mysoc->country_code).'</td>';
            print '<td align="right">'.price($object->total_localtax2).'</td>';
            print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
        }
    }

    // Total TTC
    print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right">'.price($object->total_ttc).'</td>';
    print '<td>'.$langs->trans('Currency'.$conf->currency).'</td></tr>';

    // Statut
    print '<tr><td>'.$langs->trans('Status').'</td>';
    print '<td colspan="2">'.$object->getExtraFieldLabel('Status').'</td>';
    print '</tr>';

    
    print '</table>';
    print end_box();
    
    if (!empty($conf->global->MAIN_DISABLE_CONTACTS_TAB)) {
        $blocname = 'contacts';
        $title = $langs->trans('ContactsAddresses');
        include DOL_DOCUMENT_ROOT . '/core/tpl/bloc_showhide.tpl.php';
    }

    if (!empty($conf->global->MAIN_DISABLE_NOTES_TAB)) {
        $blocname = 'notes';
        $title = $langs->trans('Notes');
        include DOL_DOCUMENT_ROOT . '/core/tpl/bloc_showhide.tpl.php';
    }
    
    // Lines
    
    print start_box($langs->trans('OrderLines'), "six", $object->fk_extrafields->ico, false);
    print '<table id="tablelines" class="noborder" width="100%">';
  
    $object->getLinesArray();
    $nbLines = count($object->lines);
    
    // Show object lines
    if (! empty($object->lines))
        $ret=$object->printObjectLines($action,$mysoc,$soc,$lineid,1,$hookmanager);

    // Form to add new line
    
    if ($object->Status == "DRAFT" && $user->rights->commande->creer) {
        if ($action != 'editline') {
            $var = true;

            if ($conf->global->MAIN_FEATURES_LEVEL > 1) {
                // Add free or predefined products/services
                $object->formAddObjectLine(1, $mysoc, $soc, $hookmanager);
            } else {
                // Add free products/services
                $object->formAddFreeProduct(1, $mysoc, $soc, $hookmanager);

                // Add predefined products/services
                if (!empty($conf->product->enabled) || !empty($conf->service->enabled)) {
                    $var = !$var;
                    $object->formAddPredefinedProduct(1, $mysoc, $soc, $hookmanager);
                }
            }

            $parameters = array();
            $reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
        }
    }
    print '</table>';
    print end_box();
    
    
    if ($action != 'presend') {
        print '<a name="builddoc"></a>'; // ancre

        /*
         * Documents generes
         *
         */
        $comref = dol_sanitizeFileName($object->ref);
        $file = $conf->commande->dir_output . '/' . $comref . '/' . $comref . '.pdf';
        $relativepath = $comref . '/' . $comref . '.pdf';
        $filedir = $conf->commande->dir_output . '/' . $comref;
        $urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
        $genallowed = $user->rights->commande->creer;
        $delallowed = $user->rights->commande->supprimer;

        $somethingshown = $formfile->show_documents('commande', $comref, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang, $hookmanager);

        /*
         * Linked object block
         */
        $somethingshown = $object->showLinkedObjectBlock();

        print '</td><td valign="top" width="50%">';

        // List of actions on element
//				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
//				$formactions=new FormActions($db);
//				$somethingshown=$formactions->showactions($object,'order',$socid);

        print '</td></tr>';
    }
    
}

print '</div>';
print '</div>';
llxFooter();

?>
