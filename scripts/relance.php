#!/bin/php
<?php

$script_file = basename(__FILE__);

// Include Dolibarr environment
require_once __DIR__.'/../master_load.inc.php';

// @todo Do something more clean, user "cron" for example, on user defined in module config
$user = new User($db);
$user->fetch(1);
$user->getRights();

dol_include_once('mmicrm/class/mmi_crm_relance.class.php');

// ref = propal_relance_valid_between_days
// Relances J-3
$relance_options = [
	'email' => 1,
	'sms' => 1,
	//'recap' => 1,
	'email_send' => 1, // @todo renommer en "send" ou inverser le test en "debug" ou "test"
	'days_max'=> 3, // @todo virer mmi_crm_relance::DAYS_MAX et mmi_crm_relance::DAYS_MIN
	'days_min' => 3,
	'email_suject' => 'J-'.mmi_crm_relance::DAYS_MAX.' pour profiter de votre offre', // @todo mettre la conf en BDD
	'tplref' => 'propal_relance_valid_between_days',
];

mmi_crm_relance::propal_relance_valid_between_days($relance_options);

// ref = propal_relance_valid_after_days
// Relances J+2
$relance_options = [
	'email' => 1,
	'sms' => 1,
	//'agenda_rappel' => 1,
	//'recap' => 1,
	'email_send' => 1,
	'days_max'=> -2,
	'days_min' => -2,
	'email_subject' => 'Dernière chance pour profiter de votre offre',
	'tplref' => 'propal_relance_valid_after_days',
	'update_fin_validite' => 4, // Adds 4 days (-2 to +2)
];

mmi_crm_relance::propal_relance_valid_between_days($relance_options);
