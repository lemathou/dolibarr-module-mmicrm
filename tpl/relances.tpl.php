<?php

dol_include_once('mmicrm/class/mmi_crm_relance.class.php');

$relance_options = GETPOST('options');

mmi_crm_relance::propal_relance_valid_between_days($relance_options);
