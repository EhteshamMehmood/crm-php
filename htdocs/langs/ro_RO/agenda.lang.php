<?php
/* Copyright (C) 2012	Regis Houssin	<regis.houssin@capnetworks.com>
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

$agenda = array(
		'CHARSET' => 'UTF-8',
		'Actions' => 'Acţiuni',
		'ActionsArea' => 'Acţiuni domeniu (Evenimente si sarcini)',
		'Agenda' => 'Ordinea de zi',
		'Agendas' => 'Agendas',
		'Calendar' => 'Calendar',
		'Calendars' => 'Calendare',
		'LocalAgenda' => 'Locale calendar',
		'AffectedTo' => 'Afectate de',
		'DoneBy' => 'Adoptată de către',
		'Events' => 'Evenimente',
		'MyEvents' => 'Evenimente',
		'OtherEvents' => 'Alte evenimente',
		'ListOfActions' => 'Lista de evenimente',
		'Location' => 'Locaţie',
		'EventOnFullDay' => 'Eveniment pe zi',
		'SearchAnAction' => 'Cautati o acţiune / sarcina',
		'MenuToDoActions' => 'Toate acţiunile incomplet',
		'MenuDoneActions' => 'Toate acţiunile oprite',
		'MenuToDoMyActions' => 'Incomplet acţiunile mele',
		'MenuDoneMyActions' => 'My oprite acţiuni',
		'ListOfEvents' => 'Lista de evenimente Dolibarr',
		'ActionsAskedBy' => 'Acţiuni înregistrate de către',
		'ActionsToDoBy' => 'Acţiuni afectate de',
		'ActionsDoneBy' => 'Acţiuni făcut de către',
		'AllMyActions' => 'Toate acţiunile / sarcini',
		'AllActions' => 'Toutes les acţiuni / sarcini',
		'ViewList' => 'Vezi lista',
		'ViewCal' => 'Vezi calendarul',
		'ViewDay' => 'Ziua vedere',
		'ViewWeek' => 'Săptămâna vedere',
		'ViewWithPredefinedFilters' => 'Vezi cu filtre predefinite',
		'AutoActions' => 'Completarea automată a ordinii de zi',
		'AgendaAutoActionDesc' => 'Definiţi aici evenimente pentru care doriţi Dolibarr de a crea automat o acţiune în ordine de zi. Dacă nimic nu este verificat (implicit), doar manualul de acţiuni vor fi incluse în ordinea de zi.',
		'AgendaSetupOtherDesc' => 'Această pagină permite să configuraţi alţi parametri de modul de ordine de zi.',
		'AgendaExtSitesDesc' => 'Această pagină vă permite să declare sursele externe de calendare pentru a vedea evenimentele în ordinea de zi Dolibarr.',
		'ActionsEvents' => 'Evenimente pentru Dolibarr care va crea o acţiune în ordinea de zi în mod automat',
		'PropalValidatedInSpeedealing' => 'Proposal %s validated',
		'InvoiceValidatedInSpeedealing' => 'Invoice %s validated',
		'InvoiceBackToDraftInSpeedealing' => 'Invoice %s go back to draft status',
		'OrderValidatedInSpeedealing' => 'Order %s validated',
		'OrderApprovedInSpeedealing' => 'Order %s approved',
		'OrderBackToDraftInSpeedealing' => 'Order %s go back to draft status',
		'OrderCanceledInSpeedealing' => 'Order %s canceled',
		'InterventionValidatedInSpeedealing' => 'Intervention %s validated',
		'ProposalSentByEMail' => '%s comerciale propunerea trimisă prin e-mail',
		'OrderSentByEMail' => '%s comanda clientului trimise prin e-mail',
		'InvoiceSentByEMail' => '%s factura clientului trimise prin e-mail',
		'SupplierOrderSentByEMail' => '%s comandă cu furnizorul trimise prin e-mail',
		'SupplierInvoiceSentByEMail' => '%s factura furnizorului trimise prin e-mail',
		'ShippingSentByEMail' => '%s de expediere trimise prin e-mail',
		'InterventionSentByEMail' => '%s de intervenţie trimise prin e-mail',
		'NewCompanyToSpeedealing' => 'Third party created',
		'DateActionPlannedStart' => 'Planificate data de început',
		'DateActionPlannedEnd' => 'Planificate data de sfârşit',
		'DateActionDoneStart' => 'Real Data începerii',
		'DateActionDoneEnd' => 'Real data de sfârşit',
		'DateActionStart' => 'Data începerii',
		'DateActionEnd' => 'Data de final',
		'AgendaUrlOptions1' => 'Puteţi, de asemenea, să adăugaţi următorii parametri la filtru de iesire:',
		'AgendaUrlOptions2' => '<b>login=<b>login= %s</b> pentru a limita producţia de acţiuni create de, afectate pentru a face sau de către <b>utilizator %s.</b>',
		'AgendaUrlOptions3' => '<b>logina=<b>logina= %s</b> pentru a limita producţia de acţiuni create de <b>utilizator %s.</b>',
		'AgendaUrlOptions4' => '<b>logint=<b>logint= %s</b> pentru a limita producţia de acţiunile afectate de <b>utilizator %s.</b>',
		'AgendaUrlOptions5' => '<b>logind=<b>logind= %s</b> pentru a limita producţia de acţiuni efectuată de către <b>utilizatorul %s.</b>',
		'AgendaShowBirthdayEvents' => 'Arata de ziua lui de contact',
		'AgendaHideBirthdayEvents' => 'Ascunde ziua lui de contact',
		'Event' => 'Événement',
		'Activities' => 'Tâches/activités',
		'NewActions' => 'Nouvelles<br>actions',
		'DoActions' => 'Actions<br>en cours',
		'SumMyActions' => 'Actions réalisées<br>par moi cette année',
		'SumActions' => 'Actions au total<br>cette année',
		'DateEchAction' => 'Date d\'échéance',
		'StatusActionTooLate' => 'Action en retard',
		'MyTasks' => 'Mes tâches',
		'MyDelegatedTasks' => 'Mes tâches déléguées',
		'ProdPlanning' => 'Planning de production',
		// External Sites ical
		'ExportCal' => 'Export calendar',
		'ExtSites' => 'Import calendare externe',
		'ExtSitesEnableThisTool' => 'Arată calendare externe, în ordinea de zi',
		'ExtSitesNbOfAgenda' => 'Numărul de calendare',
		'AgendaExtNb' => 'Calendar NB %s',
		'ExtSiteUrlAgenda' => 'URL-ul pentru a accesa. ICal fişier',
		'ExtSiteNoLabel' => 'Nu Descriere'
);
?>