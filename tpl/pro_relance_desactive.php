<?php

// Recherche des prospects sans commande dont le compte est créé depuis 3 mois 1/2
// Problématique : 
$sql = 'SELECT DISTINCT s.rowid'
	.' FROM '.MAIN_DB_PREFIX.'societe s'
	.' LEFT JOIN '.MAIN_DB_PREFIX.'commande c ON c.fk_soc=s.rowid';

