<?php

llxHeader('', $langs->trans($page_name), $help_url);

$h = 0;
$head[$h][0] = dol_buildpath("/mmicrm/index.php", 1);
$head[$h][1] = $langs->trans("MMICRMIndex");
$head[$h][2] = 'index';
$h++;
$head[$h][0] = dol_buildpath("/mmicrm/prospects.php", 1);
$head[$h][1] = $langs->trans("MMICRMProspects");
$head[$h][2] = 'prospects';
$h++;
$head[$h][0] = dol_buildpath("/mmicrm/orders.php", 1);
$head[$h][1] = $langs->trans("MMICRMOrders");
$head[$h][2] = 'orders';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');
print dol_get_fiche_head($head, $page_ref, $langs->trans($page_name), 0, 'mmicrm@mmicrm');

require_once 'tpl/'.$page_ref.'.tpl.php';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
