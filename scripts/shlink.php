#!/bin/php
<?php

$script_file = basename(__FILE__);

// Include Dolibarr environment
require_once __DIR__.'/../env.inc.php';
require_once __DIR__.'/../master_load.inc.php';

dol_include_once('mmicrm/class/mmi_shlink.class.php');

if(empty($argv[1]))
	die('Missing arg : URL');
$url = $argv[1];

$shorturl = mmi_shlink::generate($url);
//var_dump($shorturl); die();
echo str_replace('http://', 'https://', $shorturl->shortUrl);
