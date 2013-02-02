<?php
/* Copyright (C) 2004-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2013	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2012		Herve Prot				<herve.prot@symeos.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *       \file       htdocs/install/index.php
 *       \ingroup    install
 *       \brief      This page redirect to page install.php or update.php
 */

// Si fichier conf existe deja et rempli, on est pas sur une premiere install,
// on ne passe donc pas par la page de choix de langue
if (1==2 && file_exists($conffile) && isset($dolibarr_main_url_root))
{
    header("Location: update.php");
    exit;
}
else
{
	header("Location: install.php");
	exit;
}

?>
