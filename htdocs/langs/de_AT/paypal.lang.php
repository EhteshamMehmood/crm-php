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

$paypal = array(
		'CHARSET' => 'UTF-8',
		'PaypalSetup' => 'PayPal-Modul Setup',
		'PaypalDesc' => 'Dieses Modul bieten Seiten, die Zahlung ermöglichen <a href="http://www.paypal.com" target="_blank">PayPal</a> von Kunden. Dies kann für eine kostenlose Zahlung oder eine Zahlung zu einem bestimmten Dolibarr Objekt verwendet werden (Rechnung, Bestellung, ...)',
		'PaypalOrCBDoPayment' => 'Bezahlen Sie mit Kreditkarte oder Paypal',
		'PaypalDoPayment' => 'Bezahlen Sie mit Paypal',
		'PaypalCBDoPayment' => 'Bezahlen mit Kreditkarte',
		'PAYPAL_API_SANDBOX' => 'Mode test/sandbox',
		'PAYPAL_API_USER' => 'API username',
		'PAYPAL_API_PASSWORD' => 'API password',
		'PAYPAL_API_SIGNATURE' => 'API signature',
		'PAYPAL_API_INTEGRAL_OR_PAYPALONLY' => 'Offer payment "integral" (Credit card+Paypal) or "Paypal" only',
		'PAYPAL_CSS_URL' => 'Optionnal Url of CSS style sheet on payment page',
		'ThisIsTransactionId' => 'Dies ist id Geschäftsart: <b>%s</b>',
		'PAYPAL_ADD_PAYMENT_URL' => 'Fügen Sie die URL der Paypal Zahlung, wenn Sie ein Dokument per E-Mail senden',
		'PAYPAL_IPN_MAIL_ADDRESS' => 'E-Mail-Adresse für die sofortige Benachrichtigung der Zahlung (IPN)',
		'PredefinedMailContentLink' => 'You can click on the secure link below to make your payment via PayPal\n\n%s\n\n',
		'YouAreCurrentlyInSandboxMode' => 'You are currently in the "sandbox" mode',
);
?>