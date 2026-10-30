<?php
/**
 * Tests unitaires de mmi_crm_template::map() — RM3581.
 * Sans dépendance : php test/unit/mmi_crm_template_test.php
 */
require_once __DIR__.'/../../class/mmi_crm_template.class.php';

$fail = 0; $n = 0;
function check($label, $cond)
{
	global $fail, $n;
	$n++;
	if ($cond) { echo "ok   $label\n"; } else { $fail++; echo "FAIL $label\n"; }
}

check('une variable', mmi_crm_template::map(['nom'=>'Dupont'], 'Bonjour {$nom}') === 'Bonjour Dupont');
check('plusieurs variables', mmi_crm_template::map(['nom'=>'Dupont','ref'=>'P1'], '{$ref} pour {$nom}') === 'P1 pour Dupont');
check('variable répétée', mmi_crm_template::map(['nom'=>'X'], '{$nom} et {$nom}') === 'X et X');
check('variable absente inchangée', mmi_crm_template::map(['nom'=>'Dupont'], 'Bonjour {$ville}') === 'Bonjour {$ville}');
check('map vide', mmi_crm_template::map([], 'Bonjour {$nom}') === 'Bonjour {$nom}');
check('valeur vide', mmi_crm_template::map(['nom'=>''], 'Bonjour {$nom}!') === 'Bonjour !');

echo "\n$n test(s), $fail échec(s)\n";
exit($fail ? 1 : 0);
