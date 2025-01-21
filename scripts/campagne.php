#!/bin/php
<?php

$script_file = basename(__FILE__);

// Include Dolibarr environment
require_once __DIR__.'/../master_load.inc.php';

dol_include_once('mmicrm/class/mmi_crm_relance.class.php');

// agv to get
foreach ($argv as $key=>$arg) {
	if ($key==0)
		continue;
    $e=explode("=",$arg);
    if(count($e)==2)
        $_GET[$e[0]]=$e[1];
    else    
        $_GET[$e[0]]=0;
}

var_dump($_GET);// die();

$options = [
//	'email' => 1,
//	'sms' => 1,
	//'recap' => 1,
//	'email_send' => 1,
];
if (isset($_GET['campagne']))
	$options['campagne'] = $_GET['campagne'];
if (!empty($_GET['justcount']))
	$options['justcount'] = true;
if (!empty($_GET['email_send']))
	$options['email_send'] = true;
if (!empty($_GET['days_lastemail']))
	$options['days_lastemail'] = $_GET['days_lastemail'];
var_dump($options);

mmi_crm_relance::propal_product_campagne($options);
