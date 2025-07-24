<?php
/* Copyright (C) 2025 Mathieu Moulin            <contact@iprospective.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   \file       mmicrm/order_comments.php
 *   \brief      Comments on order card
 */

require_once 'env.inc.php';
require_once 'main_load.inc.php';

$elementtype = 'supplier_order';
$tab_name = 'comment';
$tab_ref = 'supplier_order_comment';

require_once '../mmicommon/tabs/objecttab.inc.php';
