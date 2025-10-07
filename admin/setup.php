<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Mathieu Moulin <mathieu@iprospective.fr>
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
 * \file    mmicrm/admin/setup.php
 * \ingroup mmicrm
 * \brief   MMICRM setup page.
 */

// Load Dolibarr environment
require_once '../env.inc.php';
require_once '../main_load.inc.php';

$arrayofparameters = array(
	'DOCUMENT_DRAFT_MASSACTION_CAN_SEND'=>array('type'=>'yesno', 'enabled'=>1),
	'PROPAL_DRAFT_MASSACTION_CAN_SEND'=>array('type'=>'yesno', 'enabled'=>1),
	'ORDER_DRAFT_MASSACTION_CAN_SEND'=>array('type'=>'yesno', 'enabled'=>1),
	'INVOICE_DRAFT_MASSACTION_CAN_SEND'=>array('type'=>'yesno', 'enabled'=>1),
	'MMICRM_PROPAL_HIDE_SIGN'=>array('type'=>'yesno', 'enabled'=>1),
	'MMICRM_USER_MAILFROM_NAME'=>array('type'=>'yesno', 'enabled'=>1),
	'MMI_CRM_TOPIC_MAIN_ACTIVE'=>array('type'=>'yesno', 'enabled'=>1),
	
	'MMICRM_RELANCES_SEP'=>array('type'=>'separator', 'enabled'=>1),
	'MMICRM_RELANCES'=>array('type'=>'yesno', 'enabled'=>1),
	'MMICRM_EMAIL_TEMPLATE'=>array('type'=>'string', 'enabled'=>1),
	'MMICRM_SMS_CLEAN_UNCIODE'=>array('type'=>'yesno', 'enabled'=>1),

	'MMICRM_SHLINK'=>array('type'=>'separator', 'enabled'=>1),
	'MMICRM_SHLINK_URL'=>array('type'=>'string', 'enabled'=>1),
	'MMICRM_SHLINK_KEY'=>array('type'=>'securekey', 'enabled'=>1),
	'MMICRM_SHLINK_SCRIPT'=>array('type'=>'yesno', 'enabled'=>1),
	
	'MMI_CRM_AFTER_MAIL_AUTO'=>array('type'=>'separator', 'enabled'=>1),
	'MMI_CRM_SMS_AFTER_MAIL_AUTO'=>array('type'=>'yesno', 'enabled'=>1),
	'MMI_CRM_SMS_AFTER_MAIL_MSG'=>array('type'=>'textarea', 'enabled'=>1),
	'MMI_CRM_RELANCE_AFTER_MAIL_AUTO'=>array('type'=>'yesno', 'enabled'=>1),
	'MMI_CRM_RELANCE_AFTER_MAIL_AUTO_ONLYIFNOT'=>array('type'=>'yesno', 'enabled'=>1),
	'MMI_CRM_RELANCE_AFTER_MAIL_AUTO_DELAI'=>array('type'=>'int', 'enabled'=>1),

	'MMI_CRM_ORDER'=>array('type'=>'separator', 'enabled'=>1),
	'MMI_CRM_ORDER_DISCOUNT_LOYALTY_ACTIVE'=>array('type'=>'yesno', 'enabled'=>1),
	'MMI_CRM_ORDER_DISCOUNT_LOYALTY_NB'=>array('type'=>'int', 'enabled'=>1),
	'MMI_CRM_ORDER_DISCOUNT_LOYALTY_RATE'=>array('type'=>'decimal', 'enabled'=>1),
);

require_once('../../mmicommon/admin/mmisetup_1.inc.php');
