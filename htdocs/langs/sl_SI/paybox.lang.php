<?php
/* Copyright (C) 2012	Regis Houssin	<regis@dolibarr.fr>
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

$paybox = array(
		'CHARSET' => 'UTF-8',
		'PayBoxSetup' => 'Nastavitev modula za online plačila Paybox',
		'PayBoxDesc' => 'Ta modul omogoča kupcem plačevanje na <a href="http://www.paybox.com" target="_blank">Paybox</a>. Lahko se uporabi za prosto plačevanje ali za plačilo posameznih Dolibarr postavk (račun, naročilo, ...)',
		'FollowingUrlAreAvailableToMakePayments' => 'Naslednji URL naslovi so na voljo kupcem za izvedbo plačil Dolibarr postavk',
		'PaymentForm' => 'Obrazec za plačilo',
		'WelcomeOnPaymentPage' => 'Dobrodošli v naši storitvi online plačil',
		'ThisScreenAllowsYouToPay' => 'Ta zaslon omogoča online plačilo za %s.',
		'ThisIsInformationOnPayment' => 'To je informacija o potrebnem plačilu',
		'ToComplete' => 'Za dokončanje',
		'YourEMail' => 'E-pošta za potrditev plačila',
		'Creditor' => 'Upnik',
		'PaymentCode' => 'Koda plačila',
		'PayBoxDoPayment' => 'Plačila v postopku',
		'YouWillBeRedirectedOnPayBox' => 'Preusmerjeni boste na varno Paybox stran za vnos podatkov o vaši kreditni kartici',
		'PleaseBePatient' => 'Prosim, bodite potrpežljivi',
		'Continue' => 'Naslednji',
		'ToOfferALinkForOnlinePayment' => 'URL za %s plačila',
		'ToOfferALinkForOnlinePaymentOnOrder' => 'URL naslov s ponudbo %s vmesnika za online plačila naročil',
		'ToOfferALinkForOnlinePaymentOnInvoice' => 'URL naslov s ponudbo %s vmesnika za online plačila računov',
		'ToOfferALinkForOnlinePaymentOnContractLine' => 'URL naslov s ponudbo %s vmesnika za online plačila po pogodbi',
		'ToOfferALinkForOnlinePaymentOnFreeAmount' => 'URL naslov s ponudbo %s vmesnika za online plačila poljubnih zneskov',
		'ToOfferALinkForOnlinePaymentOnMemberSubscription' => 'URL naslov s ponudbo %s vmesnika za online plačila članarin',
		'YouCanAddTagOnUrl' => 'Vsakemu od teh URL naslovov lahko tudi dodate url parameter <b>&tag=<i>vrednost</i></b> (zahtevano samo pri poljubnih plačilih) s komentarjem vašega plačila.',
		'SetupPayBoxToHavePaymentCreatedAutomatically' => 'Nastavite vaš PayBox z url <b>%s</b> za avtomatsko kreiranje plačil po potrditvi.',
		'YourPaymentHasBeenRecorded' => 'Ta stran potrjuje, da je bilo vaše plačilo sprejeto. Hvala.',
		'YourPaymentHasNotBeenRecorded' => 'Vaše plačilo ni bilo sprejeto in prenos je bil preklican. Hvala.',
		'AccountParameter' => 'Parametri računa',
		'UsageParameter' => 'Parametri uporabe',
		'InformationToFindParameters' => 'Pomoč pri iskanju informacij računa %s',
		'PAYBOX_CGI_URL_V2' => 'Url Paybox CGI modula za plačila',
		'VendorName' => 'Ime prodajalca',
		'CSSUrlForPaymentForm' => 'url CSS vzorca obrazca plačila',
		'MessageOK' => 'Sporočilo na strani za potrditev plačila',
		'MessageKO' => 'Sporočilo na strani za preklic plačila'
);
?>