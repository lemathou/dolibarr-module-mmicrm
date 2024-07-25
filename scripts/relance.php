#!/bin/php
<?php

$script_file = basename(__FILE__);

// Include Dolibarr environment
require_once __DIR__.'/../master_load.inc.php';

dol_include_once('mmicrm/class/mmi_crm_relance.class.php');

$relance_options = [
	'email' => 1,
	//'sms' => 1,
	'recap' => 1,
	//'email_send' => 1,
];

mmi_crm_relance::propal_relance_valid_between_days($relance_options);
