<style type="text/css">
	.num {
		text-align: right;
	}
	td.cmd {
		background-color: #ffc;
	}
	td.rel {
		background-color: #fcf;
	}
</style>
<?php

$sql_begin = 0;
$sql_limit = 1000;

$sql_order = '';
$sql_join = [];
$sql_where = [];
$sql_having = [];

echo '<form method="GET">';
echo '<p>Raccourcis :';
echo ' <a href="?">TOUT</a>';
echo ' | <a href="?filters[cmd_nb_min]=1&filters[cmd_nb_max]=1">Une seule commande</a>';
echo ' | <a href="?filters[prospect]=prospect&filters[relance_max]=1">Prospect une seule relance</a>';
echo '</p>';
echo '<p><a href="javascript:;" onclick="$(\'#filters\').toggle()">Afficher/Cacher les filtres</a></p>';
echo '<table id="filters" border="1" style="display:none;">';

echo '<thead>';
echo '<tr>';
echo '<th>Filtrer</th>';
echo '<th>Afficher</th>';
echo '<th>Trier</th>';
echo '</tr>';
echo '</thead>';

echo '<tbody>';
echo '<tr>';

$join_list = [
	'c' => 'LEFT JOIN '.MAIN_DB_PREFIX.'commande c ON c.fk_soc=s.rowid',
	'k' => 'LEFT JOIN '.MAIN_DB_PREFIX.'contacttracking k ON k.fk_soc=s.rowid',
	'a' => 'LEFT JOIN '.MAIN_DB_PREFIX.'actioncomm a ON a.fk_soc=s.rowid AND a.datep > NOW()',
];
$filter_list = [
	'prospect' => [
		'type' => 'radio',
		'list' => [
			'client' => [
				'label' => 'Déjà client',
				'sql_join' => 'c',
				'sql_where' => 'c.fk_soc IS NOT NULL',
			],
			'prospect' =>  [
				'label' => 'Prospect',
				'sql_join' => 'c',
				'sql_where' => 'c.fk_soc IS NULL',
			],
		],
	],
	'cmd_mt' => [
		'label' => 'Cumul commande mini',
		'type' => 'int',
		'sql_join' => 'c',
		'sql_having' => 'SUM(c.total_ht) > %value',
	],
	'cmd_nb_min' => [
		'label' => 'Nb commandes min',
		'type' => 'int',
		'sql_join' => 'c',
		'sql_having' => 'COUNT(DISTINCT c.rowid) >= %value',
	],
	'cmd_nb_max' => [
		'label' => 'Nb commandes max',
		'type' => 'int',
		'sql_join' => 'c',
		'sql_having' => 'COUNT(DISTINCT c.rowid) <= %value',
	],
	'relance_histo' => [
		'label' => 'Pas relancé depuis n mois',
		'type' => 'int',
		'sql_join' => 'k', // Pas évident, la table peut service à plusieurs cas de figures
		'sql_where' => 'k.fk_soc IS NULL',
	],
	'relance_not_before_cmd' => [
		'label' => 'Pas relancé depuis dernière commande',
		'type' => 'bool',
		'sql_join' => ['c'=>'1', 'k'=>'DATE(k.date_creation) >= DATE(c.date_commande)'], // Pas évident, la table peut service à plusieurs cas de figures
		'sql_having' => 'COUNT(k.fk_soc) = 0',
	],
	'relance_min' => [
		'label' => 'Relancé au moins n fois',
		'type' => 'int',
		'sql_join' => 'k', // Pas évident, la table peut service à plusieurs cas de figures
		'sql_having' => 'COUNT(k.rowid) >= %value',
	],
	'relance_max' => [
		'label' => 'Relancé au max n fois',
		'type' => 'int',
		'sql_join' => 'k', // Pas évident, la table peut service à plusieurs cas de figures
		'sql_having' => 'COUNT(k.rowid) <= %value',
	],
	'relance_next' => [
		'type' => 'radio',
		'list' => [
			'relance_1' => [
				'label' => 'Relance programmée',
				'sql_join' => 'a',
				'sql_where' => 'a.fk_soc IS NOT NULL',
			],
			'relance_0' =>  [
				'label' => 'Pas de relance programmée',
				'sql_join' => 'a',
				'sql_where' => 'a.fk_soc IS NULL',
			],
		],
	],
	'groupe_client' => [
		'label' => 'Uniquement les groupes presta PRO',
		'type' => 'bool',
		'sql_where' => 's2.p_group > 3',
	],
];

$filters = GETPOST('filters');
if (!is_array($filters))
	$filters = [];
echo '<td>';
foreach($filter_list as $i=>$j) {
	if ($j['type']=='radio') {
		echo '<input id="filter_'.$i.'_all" type="radio" name="filters['.$i.']" value="" /> <label for="filter_'.$i.'_all">TOUT</label><br />';
		foreach($j['list'] as $i2=>$j2) {
			if (!empty($filters[$i]) && $filters[$i]==$i2) {
				$checked = ' checked';
				if (!empty($j2['sql_join'])) {
					if (is_string($j2['sql_join'])) {
						$sql_join[$j2['sql_join']] = $join_list[$j2['sql_join']];
					}
					elseif (is_array($j2['sql_join'])) {
						foreach($j2['sql_join'] as $k=>$l) {
							$sql_join[$k] = $join_list[$k].' AND '.$l;
						}
					}
				}
				if (!empty($j2['sql_where']))
					$sql_where[] = $j2['sql_where'];
				if (!empty($j2['sql_having']))
					$sql_having[] = $j2['sql_having'];
			}
			else {
				$checked = '';
			}
			echo '<input id="filter_'.$i.'_'.$i2.'" type="radio" name="filters['.$i.']"'.$checked.' value="'.$i2.'" /> <label for="filter_'.$i.'_'.$i2.'">'.$j2['label'].'</label><br />';
		}
	}
	elseif ($j['type']=='int') {
		if (isset($filters[$i]) && is_numeric($filters[$i])) {
			$value = $filters[$i];
			if (!empty($j['sql_join'])) {
				if (is_string($j['sql_join'])) {
					$sql_join[$j['sql_join']] = $join_list[$j['sql_join']];
				}
				elseif(is_array($j['sql_join'])) {
					foreach($j['sql_join'] as $k=>$l) {
						$sql_join[$k] = $join_list[$k].' AND '.$l;
					}
				}
			}
			if (!empty($j['sql_where']))
				$sql_where[] = str_replace('%value', $value, $j['sql_where']);
			if (!empty($j['sql_having']))
				$sql_having[] = str_replace('%value', $value, $j['sql_having']);

		}
		else {
			$value = '';
		}
		echo '<label for="filter_'.$i.'">'.$j['label'].'</label>: <input id="filter_'.$i.'" type="text" name="filters['.$i.']" value="'.$value.'" size="5" /><br />';
	}
	elseif ($j['type']=='checkbox' || $j['type']=='bool') {
		if (isset($filters[$i]) && $filters[$i]!='') {
			$checked = ' checked';
			if (!empty($j['sql_join'])) {
				if (is_string($j['sql_join'])) {
					$sql_join[$j['sql_join']] = $join_list[$j['sql_join']];
				}
				elseif(is_array($j['sql_join'])) {
					foreach($j['sql_join'] as $k=>$l) {
						$sql_join[$k] = $join_list[$k].' AND '.$l;
					}
				}
			}
			if (!empty($j['sql_where']))
				$sql_where[] = $j['sql_where'];
			if (!empty($j['sql_having']))
				$sql_having[] = $j['sql_having'];

		}
		else {
			$checked = '';
		}
		echo '<label for="filter_'.$i.'">'.$j['label'].'</label>: <input id="filter_'.$i.'" type="checkbox" name="filters['.$i.']" value="1"'.$checked.' /><br />';
	}
}
echo '</td>';

$cols_list = [
	'cmd_tot_mt' => ['label'=>'Commandes : Montant total'],
];
$cols = GETPOST('cols', 'array');
echo '<td>Colonnes supplémentaires :<br /><select name="cols[]" multiple>';
foreach($cols_list as $i=>$j) {
	if (in_array($i, $cols)) {
		$selected = ' selected';
	}
	else {
		$selected = '';
	}
	echo '<option value="'.$i.'"'.$selected.'>'.$j['label'].'</option>';
}
echo '</select></td>';

$sort_list = [
	'nom' => [
		'label' => 'Nom',
		'sql_order' => 's.nom',
	],
	'relance_last' => [
		'label' => 'Dernière relance',
		'sql_join' => 'k',
		'sql_order' => 'MAX(k.date_creation) ASC',
	],
	'relance_next' => [
		'label' => 'Prochaine relance',
		'sql_join' => 'a',
		'sql_order' => 'MAX(a.datep) ASC',
	],
	'commandes_nb' => [
		'label' => 'Nb commandes',
		'sql_join' => 'c',
		'sql_order' => 'COUNT(DISTINCT c.rowid) DESC',
	],
	'commande_last' => [
		'label' => 'Dernière commande',
		'sql_join' => 'c',
		'sql_order' => 'MAX(c.date_commande) ASC',
	],
];
$sort = GETPOST('sort');
echo '<td><select name="sort"><option value="">--</option>';
foreach($sort_list as $i=>$j) {
	if ($sort==$i) {
		$selected = ' selected';
		if (!empty($j['sql_join']))
			$sql_join[$j['sql_join']] = $join_list[$j['sql_join']];
		$sql_order = $j['sql_order'];
	}
	else {
		$selected = '';
	}
	echo '<option value="'.$i.'"'.$selected.'>'.$j['label'].'</option>';
}
echo '</select></td>';
echo '<td><input type="submit" value="Actualiser" /></td>';
echo '</tr>';
echo '</tbody>';
echo '</table>';
echo '</form>';

$p_groups = [];
$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'c_societe_p_group';
$q = $db->query($sql);
while($row = $q->fetch_assoc()) {
        $p_groups[$row['rowid']] = $row;
}

// Base
$sql = 'SELECT s.rowid, s.nom, s.name_alias, s.code_client, s.town, s2.p_group
	FROM '.MAIN_DB_PREFIX.'societe s
	INNER JOIN '.MAIN_DB_PREFIX.'societe_extrafields s2 ON s2.fk_object=s.rowid
	'.(!empty($sql_join) ?' '.implode(' ', $sql_join) :'').'
	WHERE s2.pro = 1
		'.(!empty($sql_where) ?' AND ('.implode(' AND ', $sql_where).')' :'').'
	GROUP BY s.rowid
	'.($sql_having  ?'HAVING '.implode(' AND ', $sql_having) :'').'
	'.($sql_order  ?'ORDER BY '.$sql_order :'').'
	LIMIT '.$sql_begin.', '.$sql_limit.'
';
//echo '<pre>'.$sql.'</pre>';
$q = $db->query($sql);
//var_dump($q);
echo '<p>'.$q->num_rows.' enregistrements</p>';
$l = $l_s = [];
while($row = $q->fetch_assoc()) {
	$l[$row['rowid']] = $row;
	$l_s[] = $row['rowid'];
}
//var_dump($l);

// Commandes
$sql = 'SELECT c.fk_soc AS rowid,
		COUNT(DISTINCT c.rowid) AS tot_nb, ROUND(SUM(c.total_ht), 2) AS tot_mt,
		MAX(c.rowid) AS last_rowid, MAX(c.date_commande) AS last_date
	FROM '.MAIN_DB_PREFIX.'commande c
	WHERE c.fk_soc IN ('.implode(', ', $l_s).')
	GROUP BY c.fk_soc';
//echo $sql;
$q = $db->query($sql);
//var_dump($q);

$l_c = [];
while($row = $q->fetch_assoc()) {
	$l[$row['rowid']] = array_merge($l[$row['rowid']], $row);
	if (!empty($row['last_rowid']))
		$l_c[] = $row['last_rowid'];
}
// Dernière commande
if (!empty($l_c)) {
	$sql = 'SELECT c.fk_soc AS rowid,
		c.rowid AS last_rowid, c.date_commande AS last_date, ROUND(c.total_ht, 2) as last_mt
	FROM '.MAIN_DB_PREFIX.'commande c
	WHERE c.rowid IN ('.implode(', ', $l_c).')
	GROUP BY c.fk_soc';
	//echo $sql;
	$q = $db->query($sql);
	//var_dump($q);
	while($row = $q->fetch_assoc()) {
		$l[$row['rowid']] = array_merge($l[$row['rowid']], $row);
	}
}

// Actioncom (Prochaines Relances)
if (!empty($l_s)) {
	$sql = 'SELECT a.fk_soc AS rowid, COUNT(DISTINCT a.id) AS a_nb, MAX(a.datep) as a_last_date, MAX(a.id) as a_last_rowid
		FROM '.MAIN_DB_PREFIX.'actioncomm a
		WHERE a.fk_soc IN ('.implode(', ', $l_s).') AND a.datep > NOW()
		GROUP BY a.fk_soc';
	//echo $sql;
	$q = $db->query($sql);
	//var_dump($q);
	while($row = $q->fetch_assoc()) {
		$l[$row['rowid']] = array_merge($l[$row['rowid']], $row);
	}
}

// Relances
if (!empty($l_s)) {
	$sql = 'SELECT k.fk_soc AS rowid, COUNT(DISTINCT k.rowid) AS k_nb, MAX(k.date_creation) as k_last_date, MAX(k.rowid) AS k_last_rowid
		FROM '.MAIN_DB_PREFIX.'contacttracking k
		WHERE k.fk_soc IN ('.implode(', ', $l_s).')
		GROUP BY k.fk_soc
	';
	//echo $sql;
	$q = $db->query($sql);
	//var_dump($q);
	while($row = $q->fetch_assoc()) {
		$l[$row['rowid']] = array_merge($l[$row['rowid']], $row);
		$l_k[] = $row['k_last_rowid'];
	}
}

function date_fromsql($date)
{
	$e = explode('-', substr($date, 0, 10));
	$e = array_reverse($e);
	return implode('/', $e);
}

$filters_url = '';
foreach($filters as $i=>$j) {
	$filters_url .= '&filters['.$i.']='.$j;
}
foreach($cols as $i=>$j) {
	$filters_url .= '&cols['.$i.']='.$j;
}
//var_dump($filters_url); die();

echo '<table border="1" cellpadding="2">';
echo '<tr>';
echo '<th rowspan="2"><a href="?'.$filters_url.'&sort=nom">U</a> Client</th>';
echo '<th rowspan="2">Nom alternatif</th>';
echo '<th rowspan="2">Groupe</th>';
echo '<th rowspan="2">Ville</th>';
echo '<th colspan="'.(in_array('cmd_tot_mt', $cols) ?'2' :'1').'">Total commandes</th>';
echo '<th colspan="2">Dernière commande</th>';
echo '<th colspan="3">Relances</th>';
echo '</tr>';

echo '<tr>';
echo '<th><a href="?'.$filters_url.'&sort=commandes_nb">U</a> Nbre</th>';
if (in_array('cmd_tot_mt', $cols))
	echo '<th>Montant</th>';
echo '<th><a href="?'.$filters_url.'&sort=commande_last">U</a> Date</th>';
echo '<th>Montant</th>';
echo '<th>Nombre</th>';
echo '<th><a href="?'.$filters_url.'&sort=relance_last">U</a> Dernière</th>';
echo '<th><a href="?'.$filters_url.'&sort=relance_next">U</a> Prochaine</th>';
echo '</tr>';
foreach($l as $row) {
	echo '<tr>';
	echo '<td><a href="/comm/card.php?socid='.$row['rowid'].'">'.$row['nom'].'</a></td>';
	echo '<td>'.$row['name_alias'].'</td>';
	echo '<td>'.(!empty($row['p_group']) ?$p_groups[$row['p_group']]['label'] :'').'</td>';
	echo '<td>'.$row['town'].'</td>';
	echo '<td class="cmd num">'.$row['tot_nb'].'</td>';
	if (in_array('cmd_tot_mt', $cols))
		echo '<td class="cmd num">'.($row['tot_mt'] ?$row['tot_mt'].'&nbsp;&euro;' :'').'</td>';
	echo '<td class="cmd"><a href="/commande/card.php?id='.$row['last_rowid'].'">'.date_fromsql($row['last_date']).'</a></td>';
	echo '<td class="cmd num">'.($row['last_mt'] ?$row['last_mt'].'&nbsp;&euro;' :'').'</td>';
	echo '<td class="rel num">'.$row['k_nb'].'</td>';
	echo '<td class="rel num"><a href="/custom/contacttracking/contacttracking_card.php?id='.$row['k_last_rowid'].'">'.date_fromsql($row['k_last_date']).'</a></td>';
	echo '<td class="rel num"><a href="/ccomm/action/card.php?id='.$row['a_last_rowid'].'">'.date_fromsql($row['a_last_date']).'</a></td>';
	echo '<td class="num"><a href="/comm/action/card.php?action=create&originid='.$row['rowid'].'&socid='.$row['rowid'].'&backtopage=%2Fcustom%2Fmmicrm%2Fprospects.php&datep='.date('Ymd000000', time()+86400).'&label=Rappeler prospect">Agenda</a></td>';
	echo '</tr>';
}
echo '</table>';
