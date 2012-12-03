<?php

/* Copyright (C) 2002-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2012 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2012 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2011-2012 Herve Prot            <herve.prot@symeos.com>
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
 * 	\file       htdocs/compta/facture.php
 * 	\ingroup    facture
 * 	\brief      Page to create/see an invoice
 */
/* Includes ***************************************************************** */

require '../main.inc.php';
require DOL_DOCUMENT_ROOT . '/facture/class/facture.class.php';
require DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require DOL_DOCUMENT_ROOT . '/facture/core/modules/facture/modules_facture.php';
require DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
require DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require DOL_DOCUMENT_ROOT . '/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';


/* Loading langs ************************************************************ */


$langs->load('bills');
$langs->load('companies');
$langs->load('products');
$langs->load('main');
if (!empty($conf->margin->enabled))
    $langs->load('margins');


/* Parameters *************************************************************** */


$sall = trim(GETPOST('sall'));
$projectid = (GETPOST('projectid') ? GETPOST('projectid', 'int') : 0);

$id = (GETPOST('id', 'alpha') ? GETPOST('id', 'alpha') : GETPOST('facid', 'alpha'));  // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$socid = GETPOST('socid', 'alpha');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$lineid = GETPOST('lineid', 'alpha');
$userid = GETPOST('userid', 'alpha');
$search_ref = GETPOST('sf_ref') ? GETPOST('sf_ref', 'alpha') : GETPOST('search_ref', 'alpha');
$search_societe = GETPOST('search_societe', 'alpha');
$search_montant_ht = GETPOST('search_montant_ht', 'alpha');
$search_montant_ttc = GETPOST('search_montant_ttc', 'alpha');
$origin = GETPOST('origin', 'alpha');
$originid = (GETPOST('originid', 'alpha') ? GETPOST('originid', 'alpha') : GETPOST('origin_id', 'alpha')); // For backward compatibility
//PDF
$hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0));
$hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

$object = new Facture($db);
$societe = new Societe($db);

if (!empty($socid)) {
    $societe->fetch($socid);
} else {
    $result = $societe->getView('list');
    if (!empty($result->rows))
        $socid = $result->rows[0]->value->_id;
}

$title = $langs->trans("Bill");

// Load object
if (!empty($id)) {
    $object->fetch($id);
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
$hookmanager = new HookManager($db);
$hookmanager->initHooks(array('invoicecard'));

/* Actions ****************************************************************** */


if ($action == 'create') {
    $title = $langs->trans('NewBill');
} 

else if ($action == 'add' && $user->rights->facture->creer) {

    // Replacement invoice
    if ($_POST['type'] == "INVOICE_REPLACEMENT") {

        $datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
        if (empty($datefacture)) {
            $error++;
            $mesgs[] = '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->trans("Date")) . '</div>';
        }

        if (empty($_POST['fac_replacement'])) {
            $error++;
            $mesgs[] = '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->trans("ReplaceInvoice")) . '</div>';
        }

        if (!$error) {
            // This is a replacement invoice
            $result = $object->fetch($_POST['fac_replacement']);
            $object->fetch_thirdparty();

            $object->date = $datefacture;
            $object->note_public = trim($_POST['note_public']);
            $object->note = trim($_POST['note']);
            $object->ref_client = $_POST['ref_client'];
            $object->ref_int = $_POST['ref_int'];
            $object->modelpdf = $_POST['model'];
            $object->fk_project = $_POST['projectid'];
            $object->cond_reglement_code = $_POST['cond_reglement_code'];
            $object->mode_reglement_code = $_POST['mode_reglement_code'];
            $object->remise_absolue = $_POST['remise_absolue'];
            $object->remise_percent = $_POST['remise_percent'];

            // Proprietes particulieres a facture de remplacement
            $object->fk_facture_source = $_POST['fac_replacement'];
            $object->type = "INVOICE_REPLACEMENT";
            
            echo '<pre>' . print_r($object, true) . '</pre>';die;

            $id = $object->createFromCurrent($user);
            if ($id <= 0)
                $mesgs[] = $object->error;
        }
    }
    
    // Standard or deposit or proforma invoice
    if (($_POST['type'] == "INVOICE_STANDARD" || $_POST['type'] == "INVOICE_DEPOSIT" || $_POST['type'] == 4) && $_POST['fac_rec'] <= 0) {

        $datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
        if (empty($datefacture)) {
            $error++;
            $mesgs[] = '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->trans("Date")) . '</div>';
        }

        if (!$error) {

            // Si facture standard
            $object->socid = $_POST['socid'];
            $object->type = $_POST['type'];
            $object->number = $_POST['facnumber'];
            $object->date = $datefacture;
            $object->note_public = trim($_POST['note_public']);
            $object->note = trim($_POST['note']);
            $object->ref_client = $_POST['ref_client'];
            $object->ref_int = $_POST['ref_int'];
            $object->modelpdf = $_POST['model'];
            $object->fk_project = $_POST['projectid'];
            $object->cond_reglement_code = $_POST["cond_reglement_code"]; //($_POST['type'] == 3?1:$_POST['cond_reglement_id']);
            $object->mode_reglement_code = $_POST['mode_reglement_code'];
            $object->amount = $_POST['amount'];
            $object->remise_absolue = $_POST['remise_absolue'];
            $object->remise_percent = $_POST['remise_percent'];

            $id = $object->createStandardInvoice($user);

            if (!empty($id)) {
                header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
                exit;
            }
        } else
            $action = 'create';
    }
}


else if ($action == 'update' && $user->rights->facture->creer) {

    $datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
    $datelimite = dol_mktime(12, 0, 0, $_POST['limonth'], $_POST['liday'], $_POST['liyear']);
    if (empty($datefacture)) {
        $error++;
        $mesgs[] = '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->trans("Date")) . '</div>';
    }

    $object->date = $datefacture;
    if (!empty($object->date_lim_reglement))
        $object->date_lim_reglement = $datelimite;
    $object->cond_reglement_code = GETPOST('cond_reglement_code');
    $object->mode_reglement_code = GETPOST('mode_reglement_code');

    $res = $object->update($user);
    if ($res > 0) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
        exit;
    }
}


// Add a new line
else if (($action == 'addline' || $action == 'addline_predef') && $user->rights->facture->creer) {

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

        $ret = $object->fetch_thirdparty();

        // Clean parameters
        $predef = ((!empty($idprod) && $conf->global->MAIN_FEATURES_LEVEL < 2) ? '_predef' : '');
        $date_start = dol_mktime(GETPOST('date_start' . $predef . 'hour'), GETPOST('date_start' . $predef . 'min'), GETPOST('date_start' . $predef . 'sec'), GETPOST('date_start' . $predef . 'month'), GETPOST('date_start' . $predef . 'day'), GETPOST('date_start' . $predef . 'year'));
        $date_end = dol_mktime(GETPOST('date_end' . $predef . 'hour'), GETPOST('date_end' . $predef . 'min'), GETPOST('date_end' . $predef . 'sec'), GETPOST('date_end' . $predef . 'month'), GETPOST('date_end' . $predef . 'day'), GETPOST('date_end' . $predef . 'year'));
        $price_base_type = (GETPOST('price_base_type', 'alpha') ? GETPOST('price_base_type', 'alpha') : 'HT');

        // Define special_code for special lines
        $special_code = 0;
        //if (empty($_POST['qty'])) $special_code=3;	// Options should not exists on invoices
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

                // We define price for product
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

            if (!empty($prod->customcode) || !empty($prod->country_code)) {
                $tmptxt = '(';
                if (!empty($prod->customcode))
                    $tmptxt.=$langs->transnoentitiesnoconv("CustomCode") . ': ' . $prod->customcode;
                if (!empty($prod->customcode) && !empty($prod->country_code))
                    $tmptxt.=' - ';
                if (!empty($prod->country_code))
                    $tmptxt.=$langs->transnoentitiesnoconv("CountryOrigin") . ': ' . getCountry($prod->country_code, 0, $db, $langs, 0);
                $tmptxt.=')';
                $desc.= (dol_textishtml($desc) ? "<br>\n" : "\n") . $tmptxt;
            }

            $type = $prod->type;
        }
        else {
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

        $info_bits = 0;
        if ($tva_npr)
            $info_bits |= 0x01;

        if (!empty($price_min) && (price2num($pu_ht) * (1 - price2num(GETPOST('remise_percent')) / 100) < price2num($price_min))) {
            $mesg = $langs->trans("CantBeLessThanMinPrice", price2num($price_min, 'MU') . getCurrencySymbol($conf->currency));
            setEventMessage($mesg, 'errors');
        } else {
            // Insert line
            $result = $object->addline(
                    $id, $desc, $pu_ht, GETPOST('qty'), $tva_tx, $localtax1_tx, $localtax2_tx, $idprod, GETPOST('remise_percent'), $date_start, $date_end, 0, $info_bits, '', $price_base_type, $pu_ttc, $type, -1, $special_code, '', 0, GETPOST('fk_parent_line'), $fournprice, $buyingprice, $label
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

                    $ret = $object->fetch($id);    // Reload to get new records
                    facture_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $hookmanager);
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

            $action = '';
        }
    }
} else if ($action == 'updateligne' && $user->rights->facture->creer && $_POST['save'] == $langs->trans('Save')) {
    if (!$object->fetch($id) > 0)
        dol_print_error($db);
    $object->fetch_thirdparty();

    // Clean parameters
    $date_start = '';
    $date_end = '';
    $date_start = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
    $date_end = dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
    $description = dol_htmlcleanlastbr(GETPOST('product_desc'));
    $pu_ht = GETPOST('price_ht');

    // Define info_bits
    $info_bits = 0;
    if (preg_match('/\*/', GETPOST('tva_tx')))
        $info_bits |= 0x01;

    // Define vat_rate
    $vat_rate = $_POST['tva_tx'];
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

    // Update line
    if (!$error) {
        $result = $object->updateline(
                GETPOST('lineid'), $description, $pu_ht, GETPOST('qty'), GETPOST('remise_percent'), $date_start, $date_end, $vat_rate, $localtax1_rate, $localtax2_rate, 'HT', $info_bits, $type, GETPOST('fk_parent_line'), 0, $fournprice, $buyingprice, $label
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

                $ret = $object->fetch($id);    // Reload to get new records
                facture_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $hookmanager);
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
} else if ($action == 'confirm_delete' && $user->rights->facture->supprimer) {

    $res = $object->delete();
    if ($res > 0) {
        header('Location: liste.php');
        exit;
    }
}


// Delete line
else if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->facture->creer) {
//    $object->fetch($id);
//    $object->fetch_thirdparty();

    $result = $object->deleteline($_GET['lineid'], $user);
    if ($result > 0) {
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
        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
            $ret = $object->fetch($id);    // Reload to get new records
            $result = facture_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $hookmanager);
        }
        if ($result >= 0) {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
            exit;
        }
    } else {
        $mesgs[] = '<div clas="error">' . $object->error . '</div>';
        $action = '';
    }
}


// Classify to validated
else if ($action == 'confirm_valid' && $confirm == 'yes' && $user->rights->facture->valider) {
    $idwarehouse = GETPOST('idwarehouse');

    $object->fetch($id);
    $object->fetch_thirdparty();

    // Check parameters
    if ($object->type != 3 && !empty($conf->global->STOCK_CALCULATE_ON_BILL) && $object->hasProductsOrServices(1)) {
        if (!$idwarehouse || $idwarehouse == -1) {
            $error++;
            $mesgs[] = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse"));
            $action = '';
        }
    }

    if (!$error) {
        $result = $object->validate($user, '', $idwarehouse);
        if ($result >= 0) {
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
            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                $ret = $object->fetch($id);    // Reload to get new records
                facture_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $hookmanager);
            }
        } else {
            $mesgs[] = '<div class="error">' . $object->error . '</div>';
        }
    }
}


/*
 * Generate document
 */ else if ($action == 'builddoc') { // En get ou en post
    $object->fetch_thirdparty();

    if (GETPOST('model')) {
        $object->setDocModel($user, GETPOST('model'));
    }

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
    $result = facture_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $hookmanager);
    if ($result <= 0) {
        dol_print_error($db, $result);
        exit;
    } else {
        header('Location: ' . $_SERVER["PHP_SELF"] . '?facid=' . $object->id . (empty($conf->global->MAIN_JUMP_TAG) ? '' : '#builddoc'));
        exit;
    }
}


else if ($action == 'confirm_modif' && ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $user->rights->facture->valider) || $user->rights->facture->invoice_advance->unvalidate)) {
    $idwarehouse = GETPOST('idwarehouse');

    $object->fetch($id);
    $object->fetch_thirdparty();

    // Check parameters
    if ($object->type != 3 && !empty($conf->global->STOCK_CALCULATE_ON_BILL) && $object->hasProductsOrServices(1)) {
        if (!$idwarehouse || $idwarehouse == -1) {
        $error++;
            $mesgs[] = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse"));
            $action = '';
        }
    }
    
    
    if (!$error) {
            
        // On verifie si aucun paiement n'a ete effectue
        if ($object->getSommePaiement() == 0 && $ventilExportCompta == 0) {
            
            $res = $object->set_draft($user, $idwarehouse);

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
            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                $ret = $object->fetch($id);    // Reload to get new records
                facture_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $hookmanager);
            }
        }
    }
}


// Classify "abandoned"
else if ($action == 'confirm_canceled' && $confirm == 'yes') {
    $object->fetch($id);
    $close_code = $_POST["close_code"];
    $close_note = $_POST["close_note"];
    if ($close_code) {
        $result = $object->set_canceled($user, $close_code, $close_note);
    } else {
        $mesgs[] = '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->trans("Reason")) . '</div>';
    }
}


// Change status of invoice
else if ($action == 'reopen' && $user->rights->facture->creer) {
    $result = $object->fetch($id);
    if ($object->Status == "PAID" || $object->Status == "PAID_PARTIALLY" || ($object->Status == "CANCELED" && $object->close_code != 'replaced')) {
        $result = $object->set_unpaid($user);
        if ($result > 0) {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
            exit;
        } else {
            $mesgs[] = '<div class="error">' . $object->error . '</div>';
        }
    }
}


// Classify "paid"
else if ($action == 'confirm_paid' && $confirm == 'yes' && $user->rights->facture->paiement) {
    $object->fetch($id);
    $result = $object->set_paid($user);
}
// Classif  "paid partialy"
else if ($action == 'confirm_paid_partially' && $confirm == 'yes' && $user->rights->facture->paiement) {
    $object->fetch($id);
    $close_code = $_POST["close_code"];
    $close_note = $_POST["close_note"];
    if ($close_code) {
        $result = $object->set_paid($user, $close_code, $close_note);
    } else {
        $mesgs[] = '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->trans("Reason")) . '</div>';
    }
}


// Convertir en reduc
else if ($action == 'confirm_converttoreduc' && $confirm == 'yes' && $user->rights->facture->creer) {

    $object->fetch($id);
    $object->fetch_thirdparty();
    $object->getLinesArray();

    if ($object->Status != "PAID") { // protection against multiple submit
        // Boucle sur chaque taux de tva
        $i = 0;
        foreach ($object->lines as $line) {
            $amount_ht[$line->tva_tx]+=$line->total_ht;
            $amount_tva[$line->tva_tx]+=$line->total_tva;
            $amount_ttc[$line->tva_tx]+=$line->total_ttc;
            $i++;
        }

        // Insert one discount by VAT rate category
        $discount = new DiscountAbsolute($db);
        if ($object->type == 2)
            $discount->description = '(CREDIT_NOTE)';
        elseif ($object->type == "INVOICE_DEPOSIT")
            $discount->description = '(DEPOSIT)';
        else {
            $this->error = "CantConvertToReducAnInvoiceOfThisType";
            return -1;
        }
        $discount->tva_tx = abs($object->total_ttc);
        $discount->fk_soc = $object->socid;
        $discount->fk_facture_source = $object->id;

        $error = 0;
        foreach ($amount_ht as $tva_tx => $xxx) {
            $discount->amount_ht = abs($amount_ht[$tva_tx]);
            $discount->amount_tva = abs($amount_tva[$tva_tx]);
            $discount->amount_ttc = abs($amount_ttc[$tva_tx]);
            $discount->tva_tx = abs($tva_tx);

            $result = $discount->create($user);
            if ($result < 0) {
                $error++;
                break;
            }
        }

        if (!$error) {
            // Classe facture
            $result = $object->set_paid($user);
            if ($result > 0) {
                //$mesgs[]='OK'.$discount->id;
                $db->commit();
            } else {
                $mesgs[] = '<div class="error">' . $object->error . '</div>';
                $db->rollback();
            }
        } else {
            $mesgs[] = '<div class="error">' . $discount->error . '</div>';
            $db->rollback();
        }
    }
}


else if ($action == "setabsolutediscount" && $user->rights->facture->creer) {
    // POST[remise_id] ou POST[remise_id_for_payment]
    if (!empty($_POST["remise_id"])) {
        $ret = $object->fetch($id);
        if (!empty($ret)) {
            $result = $object->insert_discount($_POST["remise_id"]);
            if ($result < 0) {
                $mesgs[] = '<div class="error">' . $object->error . '</div>';
            }
        } else {
            dol_print_error($db, $object->error);
        }
    }
    if (!empty($_POST["remise_id_for_payment"])) {
        require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
        $discount = new DiscountAbsolute($db);
        $discount->fetch($_POST["remise_id_for_payment"]);

        $result = $discount->link_to_invoice(0, $id);
        if ($result < 0) {
            $mesgs[] = '<div class="error">' . $discount->error . '</div>';
        }
    }
}


/* View ********************************************************************* */

$form = new Form($db);
$htmlother = new FormOther($db);
$formfile = new FormFile($db);
$bankaccountstatic = new Account($db);
$now = dol_now();

llxHeader('', $langs->trans('Bill'), 'EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes');
print_fiche_titre($title);

$formconfirm = '';

// Confirmation to delete invoice
if ($action == 'delete') {
    $text = $langs->trans('ConfirmDeleteBill');
    $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('DeleteBill'), $text, 'confirm_delete', '', 0, 1);
}


// Confirmation de la suppression d'une ligne produit
if ($action == 'ask_deleteline') {
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?facid=' . $object->id . '&lineid=' . $lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 'no', 1);
}


// Confirmation de la validation
if ($action == 'valid') {

    $text = $langs->trans('ConfirmValidateBill', $numref);
    if (!empty($conf->notification->enabled)) {
        require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
        $notify = new Notify($db);
        $text.='<br>';
        $text.=$notify->confirmMessage('NOTIFY_VAL_FAC', $object->socid);
    }
    $formquestion = array();

    if ($object->type != 3 && !empty($conf->global->STOCK_CALCULATE_ON_BILL) && $object->hasProductsOrServices(1)) {
        $langs->load("stocks");
        require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
        $formproduct = new FormProduct($db);
        $label = $object->type == 2 ? $langs->trans("SelectWarehouseForStockIncrease") : $langs->trans("SelectWarehouseForStockDecrease");
        $formquestion = array(
            //'text' => $langs->trans("ConfirmClone"),
            //array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1),
            //array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1),
            array('type' => 'other', 'name' => 'idwarehouse', 'label' => $label, 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse'), 'idwarehouse', '', 1)));
    }
    if ($object->type != 2 && $object->total_ttc < 0) {    // Can happen only if $conf->global->FACTURE_ENABLE_NEGATIVE is on
        $text.='<br>' . img_warning() . ' ' . $langs->trans("ErrorInvoiceOfThisTypeMustBePositive");
    }
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ValidateBill'), $text, 'confirm_valid', $formquestion, (($object->type != 2 && $object->total_ttc < 0) ? "no" : "yes"), ($conf->notification->enabled ? 0 : 2));
}

 // Confirm back to draft status
if ($action == 'modif') {
    $text = $langs->trans('ConfirmUnvalidateBill', $object->ref);
    $formquestion = array();
    if ($object->type != 3 && !empty($conf->global->STOCK_CALCULATE_ON_BILL) && $object->hasProductsOrServices(1)) {
        $langs->load("stocks");
        require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
        $formproduct = new FormProduct($db);
        $label = $object->type == 2 ? $langs->trans("SelectWarehouseForStockDecrease") : $langs->trans("SelectWarehouseForStockIncrease");
        $formquestion = array(
            //'text' => $langs->trans("ConfirmClone"),
            //array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1),
            //array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1),
            array('type' => 'other', 'name' => 'idwarehouse', 'label' => $label, 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse'), 'idwarehouse', '', 1)));
    }

    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('UnvalidateBill'), $text, 'confirm_modif', $formquestion, "yes", 1);
}


// Confirmation du classement abandonne
if ($action == 'canceled') {
    // S'il y a une facture de remplacement pas encore validee (etat brouillon),
    // on ne permet pas de classer abandonner la facture.
    if ($objectidnext) {
        $facturereplacement = new Facture($db);
        $facturereplacement->fetch($objectidnext);
        $statusreplacement = $facturereplacement->statut;
    }
    if ($objectidnext && $statusreplacement == 0) {
        print '<div class="error">' . $langs->trans("ErrorCantCancelIfReplacementInvoiceNotValidated") . '</div>';
    } else {
        // Code
        $close[1]['code'] = 'badcustomer';
        $close[2]['code'] = 'abandon';
        // Help
        $close[1]['label'] = $langs->trans("ConfirmClassifyPaidPartiallyReasonBadCustomerDesc");
        $close[2]['label'] = $langs->trans("ConfirmClassifyAbandonReasonOtherDesc");
        // Texte
        $close[1]['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonBadCustomer", $object->ref), $close[1]['label'], 1);
        $close[2]['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyAbandonReasonOther"), $close[2]['label'], 1);
        // arrayreasons
        $arrayreasons[$close[1]['code']] = $close[1]['reason'];
        $arrayreasons[$close[2]['code']] = $close[2]['reason'];

        // Cree un tableau formulaire
        $formquestion = array(
            'text' => $langs->trans("ConfirmCancelBillQuestion"),
            array('type' => 'radio', 'name' => 'close_code', 'label' => $langs->trans("Reason"), 'values' => $arrayreasons),
            array('type' => 'text', 'name' => 'close_note', 'label' => $langs->trans("Comment"), 'value' => '', 'size' => '100')
        );

        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $langs->trans('CancelBill'), $langs->trans('ConfirmCancelBill', $object->ref), 'confirm_canceled', $formquestion, "yes");
    }
}


// Confirmation du classement paye
$resteapayer = $object->total_ttc - $object->getSommePaiement();
if ($action == 'paid' && $resteapayer <= 0) {
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?facid=' . $object->id, $langs->trans('ClassifyPaid'), $langs->trans('ConfirmClassifyPaidBill', $object->ref), 'confirm_paid', '', "yes", 1);
}
if ($action == 'paid' && $resteapayer > 0) {
    // Code
    $i = 0;
    $close[$i]['code'] = 'discount_vat';
    $i++;
    $close[$i]['code'] = 'badcustomer';
    $i++;
    // Help
    $i = 0;
    $close[$i]['label'] = $langs->trans("HelpEscompte") . '<br><br>' . $langs->trans("ConfirmClassifyPaidPartiallyReasonDiscountVatDesc");
    $i++;
    $close[$i]['label'] = $langs->trans("ConfirmClassifyPaidPartiallyReasonBadCustomerDesc");
    $i++;
    // Texte
    $i = 0;
    $close[$i]['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonDiscountVat", $resteapayer, $langs->trans("Currency" . $conf->currency)), $close[$i]['label'], 1);
    $i++;
    $close[$i]['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonBadCustomer", $resteapayer, $langs->trans("Currency" . $conf->currency)), $close[$i]['label'], 1);
    $i++;
    // arrayreasons[code]=reason
    foreach ($close as $key => $val) {
        $arrayreasons[$close[$key]['code']] = $close[$key]['reason'];
    }

    // Cree un tableau formulaire
    $formquestion = array(
        'text' => $langs->trans("ConfirmClassifyPaidPartiallyQuestion"),
        array('type' => 'radio', 'name' => 'close_code', 'label' => $langs->trans("Reason"), 'values' => $arrayreasons),
        array('type' => 'text', 'name' => 'close_note', 'label' => $langs->trans("Comment"), 'value' => '', 'size' => '100')
    );
    // Paiement incomplet. On demande si motif = escompte ou autre
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?facid=' . $object->id, $langs->trans('ClassifyPaid'), $langs->trans('ConfirmClassifyPaidPartially', $object->ref), 'confirm_paid_partially', $formquestion, "yes");
}


// Confirmation de la conversion de l'avoir en reduc
if ($action == 'converttoreduc') {
    $text = $langs->trans('ConfirmConvertToReduc');
    $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConvertToReduc'), $text, 'confirm_converttoreduc', '', "yes", 2);
}

print $formconfirm;


print '<div class="with-padding" >';
print '<div class="columns" >';


// CREATE INVOICE VIEW


if ($action == 'create' && $user->rights->facture->creer) {
    
    print '<script type="text/javascript" >
            $(document).ready(function(){
                $("#socid").change(function(){
                    window.location = "' . $_SERVER['PHP_SELF'] . '?action=create&socid=" + $(this).val();
                });
            });
        </script>';

    print start_box($title, "twelve", $object->fk_extrafields->ico, false);

    print '<form id="form-add" name="add" action="' . $_SERVER["PHP_SELF"] . '?action=add" method="POST">';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="action" value="add">';
    print '<input name="facnumber" type="hidden" value="provisoire">';

    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td class="fieldrequired">' . $langs->trans('Ref') . '</td><td colspan="2">' . $langs->trans('Draft') . '</td></tr>';

    // Tiers
    print '<tr><td class="fieldrequired">' . $langs->trans('Customer') . '</td><td colspan="2">';
    print $form->select_company($socid, "socid");
    print '</td>';
    print '</tr>' . "\n";

    // Type de facture
    print '<tr><td valign="top" class="fieldrequired">' . $langs->trans('Type') . '</td><td colspan="2">';
    print '<table class="nobordernopadding">' . "\n";

    // Standard invoice
    print '<tr height="18"><td width="16px" valign="middle">';
    print '<input type="radio" name="type" value="INVOICE_STANDARD" checked="checked" >';
    print '</td><td valign="middle">';
    $desc = $form->textwithpicto($langs->trans("InvoiceStandardAsk"), $langs->transnoentities("InvoiceStandardDesc"), 1);
    print $desc;
    print '</td></tr>' . "\n";

    // Deposit
    print '<tr height="18"><td width="16px" valign="middle">';
    print '<input type="radio" name="type" value="INVOICE_DEPOSIT"' . (GETPOST('type') == "INVOICE_DEPOSIT" ? ' checked="checked"' : '') . '>';
    print '</td><td valign="middle">';
    $desc = $form->textwithpicto($langs->trans("InvoiceDeposit"), $langs->transnoentities("InvoiceDepositDesc"), 1);
    print $desc;
    print '</td></tr>' . "\n";

    // Proforma
    if (!empty($conf->global->FACTURE_USE_PROFORMAT)) {
        print '<tr height="18"><td width="16px" valign="middle">';
        print '<input type="radio" name="type" value="4"' . (GETPOST('type') == 4 ? ' checked="checked"' : '') . '>';
        print '</td><td valign="middle">';
        $desc = $form->textwithpicto($langs->trans("InvoiceProForma"), $langs->transnoentities("InvoiceProFormaDesc"), 1);
        print $desc;
        print '</td></tr>' . "\n";
    }

    // Replacement
    $options = $object->selectReplaceableInvoiceOptions($socid);
    print '<tr height="18"><td valign="middle">';
    print '<input type="radio" id="fac_replacement_radio" name="type" value="INVOICE_REPLACEMENT"' . (GETPOST('type') == "INVOICE_REPLACEMENT" ? ' checked="checked"' : '');
    if (!$options)
        print ' disabled="disabled"';
    print '>';
    print '</td><td valign="middle">';
    $text = $langs->trans("InvoiceReplacementAsk") . ' ';
    $text.='<select class="flat" name="fac_replacement" id="fac_replacement"';
    if (!$options)
        $text.=' disabled="disabled"';
    $text.='>';
    if ($options) {
        $text.='<option value="-1"></option>';
        $text.=$options;
    } else {
        $text.='<option value="-1">' . $langs->trans("NoReplacableInvoice") . '</option>';
    }
    $text.='</select>';
    $desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceReplacementDesc"), 1);
    print $desc;
    print '</td></tr>' . "\n";

    // Credit note
    print '<tr height="18"><td valign="middle">';
    print '<input type="radio" name="type" value="2"' . (GETPOST('type') == 2 ? ' checked=true' : '');
    if (!$optionsav)
        print ' disabled="disabled"';
    print '>';
    print '</td><td valign="middle">';
    $text = $langs->transnoentities("InvoiceAvoirAsk") . ' ';
    //	$text.='<input type="text" value="">';
    $text.='<select class="flat" name="fac_avoir" id="fac_avoir"';
    if (!$optionsav)
        $text.=' disabled="disabled"';
    $text.='>';
    if ($optionsav) {
        $text.='<option value="-1"></option>';
        $text.=$optionsav;
    } else {
        $text.='<option value="-1">' . $langs->trans("NoInvoiceToCorrect") . '</option>';
    }
    $text.='</select>';
    $desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceAvoirDesc"), 1);
    print $desc;
    print '</td></tr>' . "\n";

    print '</table>';
    print '</td></tr>';


    // Date invoice
    print '<tr><td class="fieldrequired">' . $langs->trans('Date') . '</td><td colspan="2">';
    $form->select_date($dateinvoice, '', '', '', '', "add", 1, 1);
    print '</td></tr>';

    // Payment term
    print '<tr><td nowrap>' . $langs->trans('PaymentConditionsShort') . '</td><td colspan="2">';
//    $form->select_conditions_paiements(isset($_POST['cond_reglement_id'])?$_POST['cond_reglement_id']:$cond_reglement_id,'cond_reglement_id');
    print $object->select_fk_extrafields('mode_reglement_code', 'mode_reglement_code', $object->mode_reglement_code);
    print '</td></tr>';

    // Payment mode
    print '<tr><td>' . $langs->trans('PaymentMode') . '</td><td colspan="2">';
//    $form->select_types_paiements(isset($_POST['mode_reglement_id'])?$_POST['mode_reglement_id']:$mode_reglement_id,'mode_reglement_id');
    print $object->select_fk_extrafields('cond_reglement_code', 'cond_reglement_code', $object->cond_reglement_code);
    print '</td></tr>';

    // Project
    if (!empty($conf->projet->enabled)) {
        $langs->load('projects');
        print '<tr><td>' . $langs->trans('Project') . '</td><td colspan="2">';
        select_projects($soc->id, $projectid, 'projectid');
        print '</td></tr>';
    }

    // Other attributes
    $parameters = array('objectsrc' => $objectsrc, 'colspan' => ' colspan="3"');
    $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
    if (empty($reshook) && !empty($extrafields->attribute_label)) {
        foreach ($extrafields->attribute_label as $key => $label) {
            $value = (isset($_POST["options_" . $key]) ? $_POST["options_" . $key] : $object->array_options["options_" . $key]);
            print '<tr><td>' . $label . '</td><td colspan="3">';
            print $extrafields->showInputField($key, $value);
            print '</td></tr>' . "\n";
        }
    }

    // Modele PDF
    print '<tr><td>' . $langs->trans('Model') . '</td>';
    print '<td>';
    include_once DOL_DOCUMENT_ROOT . '/facture/core/modules/facture/modules_facture.php';
    $liste = ModelePDFFactures::liste_modeles($db);
    print $form->selectarray('model', $liste, $conf->global->FACTURE_ADDON_PDF);
    print "</td></tr>";

    // Public note
    print '<tr>';
    print '<td class="border" valign="top">' . $langs->trans('NotePublic') . '</td>';
    print '<td valign="top" colspan="2">';
    print '<textarea name="note_public" wrap="soft" cols="70" rows="' . ROWS_3 . '">';
    if (is_object($objectsrc)) {    // Take value from source object
        print $objectsrc->note_public;
    }
    print '</textarea></td></tr>';

    // Private note
    if (empty($user->societe_id)) {
        print '<tr>';
        print '<td class="border" valign="top">' . $langs->trans('NotePrivate') . '</td>';
        print '<td valign="top" colspan="2">';
        print '<textarea name="note" wrap="soft" cols="70" rows="' . ROWS_3 . '">';
        if (!empty($origin) && !empty($originid) && is_object($objectsrc)) {    // Take value from source object
            print $objectsrc->note;
        }
        print '</textarea></td></tr>';
    }

    print '</table>';

    // Button "Create Draft"
    print '<br><center><input type="submit" id="submit-form-add" class="button" name="bouton" value="' . $langs->trans('CreateDraft') . '"></center>';

    print "</form>\n";

    print end_box();
    
}


// DETAILS INVOICE
else {

    /*
     * Boutons actions
     */
    if ($action != 'presend') {
        if ($user->societe_id == 0 && $action <> 'editline') {
            print '<div class="tabsAction">';

            $totalpaye = $object->getSommePaiement();
            $resteapayer = $object->total_ttc - $totalpaye;
            
            // Editer une facture deja validee, sans paiement effectue et pas exporte en compta
            if ($object->Status == "NOT_PAID") {
                // On verifie si les lignes de factures ont ete exportees en compta et/ou ventilees
                $ventilExportCompta = $object->getVentilExportCompta();

                if ($resteapayer == $object->total_ttc &&  $ventilExportCompta == 0) {
                    if (!$objectidnext) {
                        if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $user->rights->facture->valider) || $user->rights->facture->invoice_advance->unvalidate) {
                            print '<p class="button-height right">';
                            print '<a class="button icon-pencil" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=modif">' . $langs->trans('Modify') . '</a>';
                            print "</p>";
                        } else {
                            print '<span class="butActionRefused" title="' . $langs->trans("NotEnoughPermissions") . '">' . $langs->trans('Modify') . '</span>';
                        }
                    } else {
                        print '<span class="butActionRefused" title="' . $langs->trans("DisabledBecauseReplacedInvoice") . '">' . $langs->trans('Modify') . '</span>';
                    }
                }
            }
            
            // Reverse back money or convert to reduction
            if ($object->type == "INVOICE_DEPOSIT" || $object->type == 3) {
                // For credit note only
                if ($object->type == 2 && $object->statut == 1 && $object->paye == 0 && $user->rights->facture->paiement) {
                    print '<a class="butAction" href="paiement.php?facid=' . $object->id . '&amp;action=create">' . $langs->trans('DoPaymentBack') . '</a>';
                }
                // For credit note
                if ($object->type == 2 && $object->statut == 1 && $object->paye == 0 && $user->rights->facture->creer && $object->getSommePaiement() == 0) {
                    print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?facid=' . $object->id . '&amp;action=converttoreduc">' . $langs->trans('ConvertToReduc') . '</a>';
                }
                // For deposit invoice
                if ($object->type == "INVOICE_DEPOSIT" && $object->Status == "STARTED" && $resteapayer == 0 && $user->rights->facture->creer) {
                    print '<p class="button-height right">';
                    print '<a class="button" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=converttoreduc">' . $langs->trans('ConvertToReduc') . '</a>';
                    print '</p>';
                }
            }

            // Classify paid (if not deposit and not credit note. Such invoice are "converted")
            if ($object->Status == "STARTED" && $user->rights->facture->paiement &&
                    (($object->type != "INVOICE_DEPOSIT" && $object->type != 3 && $resteapayer <= 0) || ($object->type == 2 && $resteapayer >= 0))) {
                print '<p class="button-height right">';
                print '<a class="button" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=paid">' . $langs->trans('ClassifyPaid') . '</a>';
                print "</p>";
            }
            
            // Classify 'closed not completely paid' (possible si validee et pas encore classee payee)
            if ($object->Status == "STARTED"  && $resteapayer > 0
                    && $user->rights->facture->paiement) {
                if ($totalpaye > 0 || $totalcreditnotes > 0) {
                    // If one payment or one credit note was linked to this invoice
                    print '<p class="button-height right">';
                    print '<a class="button" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=paid">' . $langs->trans('ClassifyPaidPartially') . '</a>';
                    print "</p>";
                } else {
                    if ($objectidnext) {
                        print '<span class="butActionRefused" title="' . $langs->trans("DisabledBecauseReplacedInvoice") . '">' . $langs->trans('ClassifyCanceled') . '</span>';
                    } else {
                        print '<p class="button-height right">';
                        print '<a class="button" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=canceled">' . $langs->trans('ClassifyCanceled') . '</a>';
                        print "</p>";
                    }
                }
            }
            
            // Delete invoice
            if ($user->rights->facture->supprimer) {
                if ($numshipping == 0) {
                    print '<p class="button-height right">';
                    print '<a class="button icon-cross" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete">' . $langs->trans("Delete") . '</a>';
                    print "</p>";
                } else {
                    print '<a class="butActionRefused" href="#" title="' . $langs->trans("ShippingExist") . '">' . $langs->trans("Delete") . '</a>';
                }
            }

            // Clone
                
            if (($object->type == "INVOICE_STANDARD" || $object->type == "INVOICE_DEPOSIT" || $object->type == 4) && $user->rights->facture->creer) {
                print '<p class="button-height right">';
                print '<a class="button icon-pages" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=clone&amp;object=invoice">' . $langs->trans("ToClone") . '</a>';
                print "</p>";
            }

            // Clone as predefined
            if (($object->type == "INVOICE_STANDARD" || $object->type == "INVOICE_DEPOSIT" || $object->type == 4) && $object->Status != "PAID" && $object->Status != "NOT_PAID" && $object->Status != "STARTED" && $object->Status != "CANCELED" && $object->Status != "PAID_PARTIALLY" && $user->rights->facture->creer) {
                if (!$objectidnext) {
                    print '<p class="button-height right">';
                    print '<a class="button icon-page" href="facture/fiche-rec.php?id=' . $object->id . '&amp;action=create">' . $langs->trans("ChangeIntoRepeatableInvoice") . '</a>';
                    print "</p>";
                }
            }
            
            // Reopen a standard paid invoice
            if (($object->type == "INVOICE_STANDARD" || $object->type == 1) && ($object->Status == "CANCELED" || $object->Status == "PAID" || $object->Status == "PAID_PARTIALLY")) {    // A paid invoice (partially or completely)
                if (!$objectidnext && $object->close_code != 'replaced') { // Not replaced by another invoice
                    print '<p class="button-height right">';
                    print '<a class="button icon-reply" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=reopen">' . $langs->trans('ReOpen') . '</a>';
                    print '</p>';
                }
            }
            

            // Validate
            if ($object->Status == "DRAFT" && count($object->lines) > 0 &&
                    (
                    (($object->type == "INVOICE_STANDARD" || $object->type == "INVOICE_DEPOSIT" || $object->type == 3 || $object->type == 4) && (!empty($conf->global->FACTURE_ENABLE_NEGATIVE) || $object->total_ttc >= 0))
                    || ($object->type == 2 && $object->total_ttc <= 0))
            ) {
                if ($user->rights->facture->valider) {
                    print '<p class="button-height right">';
                    print '<a class="button icon-tick" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=valid">' . $langs->trans('Validate') . '</a>';
                    print "</p>";
                }
            }
            
            // Create payment
            if ($object->type != 2 && ($object->Status == "NOT_PAID" || $object->Status == "STARTED") &&  $user->rights->facture->paiement) {
                if ($resteapayer > 0) {
                    print '<p class="button-height right">';
                    print '<a class="button" href="compta/paiement.php?facid=' . $object->id . '&amp;action=create">' . $langs->trans('DoPayment') . '</a>';
                    print "</p>";
                }
            }

            print '</div>';
        }
    }

    print start_box($title, "twelve", $object->fk_extrafields->ico, false);

    if ($action == 'edit') {
        print '<form name="edit" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
        print '<input type="hidden" name="action" value="update">';
        print '<input type="hidden" name="id" value="' . $id . '">';
        print '<input name="facnumber" type="hidden" value="provisoire">';
    }

    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td width="20%">' . $langs->trans('Ref') . '</td>';
    print '<td>' . $object->ref . '</td></tr>';

    // Client
    print '<tr><td width="20%">' . $langs->trans('Company') . '</td>';
    print '<td>' . $object->client->name . '</td></tr>';

    // Type
    print '<tr><td width="20%">' . $langs->trans('Type') . '</td>';
    print '<td>' . $object->getExtraFieldLabel('type') . '</td></tr>';
    
            // Relative and absolute discounts
        $addrelativediscount = '<a href="' . DOL_URL_ROOT . '/comm/remise.php?id=' . $soc->id . '&backtopage=' . urlencode($_SERVER["PHP_SELF"]) . '?facid=' . $object->id . '">' . $langs->trans("EditRelativeDiscounts") . '</a>';
        $addabsolutediscount = '<a href="' . DOL_URL_ROOT . '/comm/remx.php?id=' . $soc->id . '&backtopage=' . urlencode($_SERVER["PHP_SELF"]) . '?facid=' . $object->id . '">' . $langs->trans("EditGlobalDiscounts") . '</a>';
        $addcreditnote = '<a href="' . DOL_URL_ROOT . '/compta/facture.php?action=create&socid=' . $soc->id . '&type=2&backtopage=' . urlencode($_SERVER["PHP_SELF"]) . '?facid=' . $object->id . '">' . $langs->trans("AddCreditNote") . '</a>';

        print '<tr><td>' . $langs->trans('Discounts');
        print '</td><td colspan="5">';
        if ($soc->remise_client)
            print $langs->trans("CompanyHasRelativeDiscount", $soc->remise_client);
        else
            print $langs->trans("CompanyHasNoRelativeDiscount");
        //print ' ('.$addrelativediscount.')';

        $object->fetch_thirdparty();
        $absolute_discount = $object->thirdparty->getAvailableDiscounts();
        if ($absolute_discount > 0) {
            print '. ';
            if ($object->Status != "DRAFT" || $object->type == "INVOICE_DEPOSIT" || $object->type == 3) {
                if ($object->Status == "DRAFT") {
                    print $langs->trans("CompanyHasAbsoluteDiscount", price($absolute_discount), $langs->transnoentities("Currency" . $conf->currency));
                    print '. ';
                } else {
                    if ($object->Status == "DRAFT" || $object->type == "INVOICE_DEPOSIT" || $object->type == 3) {
                        $text = $langs->trans("CompanyHasAbsoluteDiscount", price($absolute_discount), $langs->transnoentities("Currency" . $conf->currency));
                        print '<br>' . $text . '.<br>';
                    } else {
                        $text = $langs->trans("CompanyHasAbsoluteDiscount", price($absolute_discount), $langs->transnoentities("Currency" . $conf->currency));
                        $text2 = $langs->trans("AbsoluteDiscountUse");
                        print $form->textwithpicto($text, $text2);
                    }
                }
            } else {
                // Remise dispo de type remise fixe (not credit note)
                print '<br>';
                $form->form_remise_dispo($_SERVER["PHP_SELF"] . '?id=' . $object->id, GETPOST('discountid'), 'remise_id', $object->socid, $absolute_discount, $filterabsolutediscount, $resteapayer, ' (' . $addabsolutediscount . ')');
            }
        } else {
            if ($absolute_creditnote > 0) {    // If not, link will be added later
                if ($object->statut == 0 && $object->type != 2 && $object->type != 3)
                    print ' (' . $addabsolutediscount . ')<br>';
                else
                    print '. ';
            }
            else
                print '. ';
        }
        if ($absolute_creditnote > 0) {
            // If validated, we show link "add credit note to payment"
            if ($object->statut != 1 || $object->type == 2 || $object->type == 3) {
                if ($object->statut == 0 && $object->type != 3) {
                    $text = $langs->trans("CompanyHasCreditNote", price($absolute_creditnote), $langs->transnoentities("Currency" . $conf->currency));
                    print $form->textwithpicto($text, $langs->trans("CreditNoteDepositUse"));
                } else {
                    print $langs->trans("CompanyHasCreditNote", price($absolute_creditnote), $langs->transnoentities("Currency" . $conf->currency)) . '.';
                }
            } else {
                // Remise dispo de type avoir
                if (!$absolute_discount)
                    print '<br>';
                //$form->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$object->id, 0, 'remise_id_for_payment', $soc->id, $absolute_creditnote, $filtercreditnote, $resteapayer);
                $form->form_remise_dispo($_SERVER["PHP_SELF"] . '?facid=' . $object->id, 0, 'remise_id_for_payment', $soc->id, $absolute_creditnote, $filtercreditnote, 0);    // We must allow credit not even if amount is higher
            }
        }
        if (!$absolute_discount && !$absolute_creditnote) {
            print $langs->trans("CompanyHasNoAbsoluteDiscount");
            if ($object->statut == 0 && $object->type != 2 && $object->type != 3)
                print ' (' . $addabsolutediscount . ')<br>';
            else
                print '. ';
        }
        /* if ($object->statut == 0 && $object->type != 2 && $object->type != 3)
          {
          if (! $absolute_discount && ! $absolute_creditnote) print '<br>';
          //print ' &nbsp; - &nbsp; ';
          print $addabsolutediscount;
          //print ' &nbsp; - &nbsp; '.$addcreditnote;      // We disbale link to credit note
          } */
        print '</td></tr>';


    // Date invoice
    print '<tr><td width="20%">' . $langs->trans('Date') . '</td>';
    print '<td>';
    if ($action == 'edit')
        $form->select_date($object->date, 're', '', '', '', "edit_commande", 1, 1);
    else
        print dol_print_date($object->date, 'daytext');
    print '</td></tr>';

    // Date payment term
    print '<tr><td width="20%">' . $langs->trans('DateMaxPayment') . '</td>';
    print '<td>';
    if ($action == 'edit')
        $form->select_date($object->date_lim_reglement, 'li', '', '', '', "edit_commande", 1, 1);
    else {
        print dol_print_date($object->date_lim_reglement, 'daytext');
        if ($object->date_lim_reglement < ($now - $conf->facture->client->warning_delay) && !$object->paye && $object->statut == 1 && !isset($object->am))
            print img_warning($langs->trans('Late'));
    }
    print '</td></tr>';

    // Payment terms
    print '<tr><td width="20%">' . $langs->trans('PaymentConditions') . '</td>';
    print '<td>';
    if ($action == 'edit') {
        print $object->select_fk_extrafields('cond_reglement_code', 'cond_reglement_code', $object->cond_reglement_code);
    }
    else
        print $object->getExtraFieldLabel('cond_reglement_code');
    print '</td></tr>';

    // Payment mode
    print '<tr><td width="20%">' . $langs->trans('PaymentMode') . '</td>';
    print '<td>';
    if ($action == 'edit')
        print $object->select_fk_extrafields('mode_reglement_code', 'mode_reglement_code', $object->mode_reglement_code);
    else
        print $object->getExtraFieldLabel('mode_reglement_code') . '</td></tr>';

    // Amount
    print '<tr><td>' . $langs->trans('AmountHT') . '</td>';
    print '<td align="right" colspan="2" nowrap>' . price($object->total_ht) . '</td>';
    print '<td>' . $langs->trans('Currency' . $conf->currency) . '</td></tr>';
    print '<tr><td>' . $langs->trans('AmountVAT') . '</td><td align="right" colspan="2" nowrap>' . price($object->total_tva) . '</td>';
    print '<td>' . $langs->trans('Currency' . $conf->currency) . '</td>';
    print '</tr>';

    // Amount Local Taxes
    if ($mysoc->pays_code == 'ES') {
        if ($mysoc->localtax1_assuj == "1") { //Localtax1 RE
            print '<tr><td>' . $langs->transcountry("AmountLT1", $mysoc->pays_code) . '</td>';
            print '<td align="right" colspan="2" nowrap>' . price($object->total_localtax1) . '</td>';
            print '<td>' . $langs->trans("Currency" . $conf->currency) . '</td></tr>';
        }
        if ($mysoc->localtax2_assuj == "1") { //Localtax2 IRPF
            print '<tr><td>' . $langs->transcountry("AmountLT2", $mysoc->pays_code) . '</td>';
            print '<td align="right" colspan="2" nowrap>' . price($object->total_localtax2) . '</td>';
            print '<td>' . $langs->trans("Currency" . $conf->currency) . '</td></tr>';
        }
    }

    print '<tr><td>' . $langs->trans('AmountTTC') . '</td><td align="right" colspan="2" nowrap>' . price($object->total_ttc) . '</td>';
    print '<td>' . $langs->trans('Currency' . $conf->currency) . '</td></tr>';

    // Status
    print '<tr><td width="20%">' . $langs->trans('Status') . '</td>';
    print '<td>' . $object->getExtraFieldLabel('Status') . '</td></tr>';


    print '</table>';

    if ($action == 'edit') {
        // Button "Update"
        print '<br><center><input type="submit" class="button" name="bouton" value="' . $langs->trans('Update') . '"></center>';
        print '</form>';
    }

    print end_box();

    // Actions
    if ($object->Status == "DRAFT" && $user->rights->facture->creer) {
        print '<p class="button-height right">';
        print '<a class="button icon-pencil" href="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&action=edit">' . $langs->trans("Edit") . '</a>';
        print "</p>";
    }


    // Lines

    print start_box($langs->trans('BillLines'), "six", $object->fk_extrafields->ico, false);
    print '<table id="tablelines" class="noborder" width="100%">';

    $object->getLinesArray();
    $nbLines = count($object->lines);

    // Show object lines
    if (!empty($object->lines))
        $ret = $object->printObjectLines($action, $mysoc, $soc, $lineid, 1, $hookmanager);

    // Form to add new line

    if ($object->Status == "DRAFT" && $user->rights->facture->creer) {
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

    
    // Show list of paymenys
    $payments = $object->getPaymentsList();
    print start_box($langs->trans('PaymentsList'), "six", $object->fk_extrafields->ico, false);
    print '<table id="tablelines" class="noborder" width="100%">';
    print '<tr>';
    print '<th align="left">'. $langs->trans('Payments') . '</th>';
    print '<th align="right" colspan="2" nowrap>'. $langs->trans('Amount') . '</th>';
    print '</tr>';
    foreach ($payments as $p) {
        print '<tr>';
        print '<td>'. dol_print_date($p->datepaye, "day") . '</td>';
        print '<td align="right" colspan="2" nowrap>'. price($p->amount) . '</td>';
        print '<td>'. $langs->trans('Currency' . $conf->currency) . '</td>';
        print '</tr>';
    }
    print '</table>';
    print '<br />';
    
    $amountPaid = $object->getSommePaiement();
    print $langs->trans("AlreadyPaid") . ': ' . price($amountPaid) . $langs->trans('Currency' . $conf->currency) . '<br />';
    print $langs->trans("RemainderToPay") . ': ' . price($object->total_ttc - $amountPaid) . $langs->trans('Currency' . $conf->currency) . '<br />';
    
        print end_box();
    

    /*
     * Documents generes
     */
    print start_box($langs->trans('GeneratedDocuments'), "six", $object->fk_extrafields->ico, false);
    $filename = dol_sanitizeFileName($object->ref);
    $filedir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($object->ref);
    $urlsource = $_SERVER['PHP_SELF'] . '?id=' . $object->id;
    $genallowed = $user->rights->facture->creer;
    $delallowed = $user->rights->facture->supprimer;

    print '<br>';
    print $formfile->showdocuments('facture', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang, $hookmanager);
    $somethingshown = $formfile->numoffiles;
    print end_box();
}

print '</div>';
print '</div>';

dol_htmloutput_mesg('', $mesgs);

llxFooter();
?>
