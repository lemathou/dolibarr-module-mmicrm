<?php
/* Copyright (C) 2023 Mathieu Moulin <mathieu@iprospective.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    mmicrm/admin/about.php
 * \ingroup mmicrm
 * \brief   About page of module MMICRM.
 */

// Load Dolibarr environment
require_once 'main_load.inc.php';
$langs->load("mmicrm@mmicrm");

$help_url = '';
$page_name = "MMICRMRelances";
$page_ref = 'relances';

require_once 'page.inc.php';
