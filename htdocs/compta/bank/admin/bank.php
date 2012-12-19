<?php
/* Copyright (C) 2009 		Laurent Destailleur            <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012  Juanjo Menent			       <jmenent@2byte.es>
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
 *      \file       htdocs/compta/bank/admin/bank.php
 *		\ingroup    bank
 *		\brief      Page to setup the bank module
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$langs->load("admin");
$langs->load("companies");
$langs->load("bills");
$langs->load("other");
$langs->load("banks");

if (!$user->admin)
  accessforbidden();

$action = GETPOST('action','alpha');


/*
 * Actions
 */

if ($action == 'set_BANK_CHEQUERECEIPT_FREE_TEXT')
{
	$free = GETPOST('BANK_CHEQUERECEIPT_FREE_TEXT','alpha');
    $res = dolibarr_set_const($db, "BANK_CHEQUERECEIPT_FREE_TEXT",$free,'chaine',0,'',$conf->entity);

	if (! $res > 0) $error++;

 	if (! $error)
    {
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
        $mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
    }
}

//Order display of bank account
if ($action == 'setbankorder')
{
	if (dolibarr_set_const($db, "BANK_SHOW_ORDER_OPTION",GETPOST('value','alpha'),'chaine',0,'',$conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * view
 */

llxHeader("",$langs->trans("BankSetupModule"));

$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("BankSetupModule"),$linkback,'setup');
print '<br>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>';
print '<td align="center" width="60">&nbsp;</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";
$var=true;

$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_BANK_CHEQUERECEIPT_FREE_TEXT">';
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("FreeLegalTextOnChequeReceipts").' ('.$langs->trans("AddCRIfTooLong").')<br>';
print '<textarea name="BANK_CHEQUERECEIPT_FREE_TEXT" class="flat" cols="120">'.$conf->global->BANK_CHEQUERECEIPT_FREE_TEXT.'</textarea>';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</table>';
print "<br>";

/*
$var=!$var;
print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"set_BANK_CHEQUERECEIPT_DRAFT_WATERMARK\">";
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("WatermarkOnDraftChequeReceipt").'<br>';
print '<input size="50" class="flat" type="text" name="BANK_CHEQUERECEIPT_DRAFT_WATERMARK" value="'.$conf->global->BANK_CHEQUERECEIPT_DRAFT_WATERMARK.'">';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';
*/


//Show bank account order
print_titre($langs->trans("BankOrderShow"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="140">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center">'.$langs->trans("Status").'</td>';
print '<td align="center" width="60">&nbsp;</td>';
print "</tr>\n";

$bankorder[0][0]=$langs->trans("BankOrderGlobal");
$bankorder[0][1]=$langs->trans("BankOrderGlobalDesc");
$bankorder[0][2]='BankCode DeskCode AccountNumber BankAccountNumberKey';
$bankorder[1][0]=$langs->trans("BankOrderES");
$bankorder[1][1]=$langs->trans("BankOrderESDesc");
$bankorder[1][2]='BankCode DeskCode BankAccountNumberKey AccountNumber';

$var = true;
$i=0;

$nbofbank=count($bankorder);
while ($i < $nbofbank)
{
	$var = !$var;

	print '<tr '.$bc[$var].'>';
	print '<td>'.$bankorder[$i][0]."</td><td>\n";
	print $bankorder[$i][1];
	print '</td>';
	print '<td nowrap="nowrap">';
	$tmparray=explode(' ',$bankorder[$i][2]);
	foreach($tmparray as $key => $val)
	{
	    if ($key > 0) print ', ';
	    print $langs->trans($val);
	}
	print "</td>\n";

	if ($conf->global->BANK_SHOW_ORDER_OPTION == $i)
	{
		print '<td align="center">';
		print img_picto($langs->trans("Activated"),'on');
		print '</td>';
	}
	else
	{
		print '<td align="center"><a href="'.$_SERVER['PHP_SELF'].'?action=setbankorder&amp;value='.$i.'">';
		print img_picto($langs->trans("Disabled"),'off');
		print '</a></td>';
	}
	print '<td>&nbsp;</td>';
	print '</tr>'."\n";
	$i++;
}

print '</table>'."\n";

dol_htmloutput_mesg($mesg);

$db->close();

llxFooter();
?>
