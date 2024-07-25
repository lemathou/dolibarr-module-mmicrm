<?php

dol_include_once('mmicrm/class/mmi_crm_relance.class.php');

$options = GETPOST('options');

mmi_crm_relance::propal_product_campagne($options);
