#!/bin/php
<?php

$script_file = basename(__FILE__);

// Include Dolibarr environment
require_once __DIR__.'/../master_load.inc.php';

dol_include_once('mmicrm/class/mmi_crm_relance.class.php');

$relance_options = [
	'email' => 1,
	'sms' => 1,
	//'recap' => 1,
	'email_send' => 1,
	'email_suject'=> 'J-'.mmi_crm_relance::DAYS_MAX.' pour profiter de votre offre',
	'tplref' => 'propal_relance_valid_between_days',
];

mmi_crm_relance::propal_relance_valid_between_days($relance_options);

$relance_options = [
	'email' => 1,
	'sms' => 1,
	//'recap' => 1,
	'email_send' => 1,
	'days_max'=> -2,
	'days_min' => -2,
	'email_subject'=>'Dernière chance pour profiter de votre offre',
	'tplref'=>'propal_relance_valid_after_days',
	'update_fin_validite'=> 1
];

mmi_crm_relance::propal_relance_valid_between_days($relance_options);
