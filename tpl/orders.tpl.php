<style type="text/css">
#commandes tbody tr td {
	border-top: 1px solid #aaa;
	border-right: 1px solid #ddd;
}
</style>
<?php

$MAX_CT = 0.9;

$datediff_from = GETPOST('datediff_from', 'int');
if (empty($datediff_from))
	$datediff_from = 365;
$datediff_to = GETPOST('datediff_to', 'int');
if (empty($datediff_to))
	$datediff_to = 60;

$sql = 'SELECT rowid'
	.' FROM '.MAIN_DB_PREFIX.'c_type_contact t'
	.' WHERE t.element="commande" AND t.source="internal" AND t.code="SALESREPFOLL"';
//echo '<pre>'.$sql.'</pre>';
$q = $db->query($sql);
if($row=$q->fetch_assoc()) {
	$c_type_contact = $row['rowid'];
}

?>

<p>Liste des commandes non expédiées ou non facturées, avec traitements simplifiés</p>
<p>Filtres :</p>
<form method="GET" action="">
<table>
	<tbody>
		<tr>
			<th>depuis :</th>
			<td>moins de <input size="3" type="text" name="datediff_from" value="<?php echo $datediff_from; ?>" /> jours</td>
		</tr>
		<tr>
			<th>jusqu'à :</th>
			<td>moins de <input size="3" type="text" name="datediff_to" value="<?php echo $datediff_to; ?>" /> jours</td>
		</tr>
		<tr>
			<th>Commercial</th>
			<td><select name="user_id"></select></td>
		</tr>
		<tr>
			<td><input type="submit" value="Filtrer" /></td>
		</tr>
	</tbody>
</table>
</form>
<?php

$cmds = [];

$sql_from  = 'FROM '.MAIN_DB_PREFIX.'commande c'
	.' LEFT JOIN '.MAIN_DB_PREFIX.'commande_extrafields c2 ON c2.fk_object=c.rowid'
	.' INNER JOIN '.MAIN_DB_PREFIX.'societe s ON c.fk_soc=s.rowid';

$sql_where = 'c.date_commande IS NOT NULL'
	.' AND c.fk_statut >= 1'
	.' AND DATEDIFF(c.date_commande, NOW())<=-'.$datediff_to.' AND DATEDIFF(c.date_commande, NOW())>=-'.$datediff_from
	.' AND (c.fk_statut NOT IN (2, 3) OR c2.expe_ok != 1 OR c.facture != 1)';

// Commandes
$sql  = 'SELECT COUNT(c.rowid)'
.' '.$sql_from
.' LEFT JOIN '.MAIN_DB_PREFIX.'element_contact cu ON cu.fk_c_type_contact='.$c_type_contact.' AND cu.element_id=c.rowid'
.' LEFT JOIN '.MAIN_DB_PREFIX.'user u ON u.rowid=cu.fk_socpeople'
.' WHERE '.$sql_where;
//echo '<pre>'.$sql.'</pre>';
$q = $db->query($sql);
list($cmds_nb) = $q->fetch_row();

$sql  = 'SELECT c2.*, c.*, s.nom AS s_nom, s.code_client AS s_ref, u.firstname AS user_nom'
	.' '.$sql_from
	.' LEFT JOIN '.MAIN_DB_PREFIX.'element_contact cu ON cu.fk_c_type_contact='.$c_type_contact.' AND cu.element_id=c.rowid'
	.' LEFT JOIN '.MAIN_DB_PREFIX.'user u ON u.rowid=cu.fk_socpeople'
	.' WHERE '.$sql_where
	.' ORDER BY c.rowid'
	.' LIMIT 0, 500';
//echo '<pre>'.$sql.'</pre>';
$q = $db->query($sql);
while($row=$q->fetch_assoc()) {
	$cmds[$row['rowid']] = $row;
}

// Expéditions
$sql  = 'SELECT c.rowid AS fk_commande, e.rowid, e.ref, e.date_creation, e.date_valid'
	.' '.$sql_from
	.' INNER JOIN '.MAIN_DB_PREFIX.'commandedet cl ON cl.fk_commande=c.rowid'
	.' INNER JOIN '.MAIN_DB_PREFIX.'expeditiondet el ON el.fk_origin_line=cl.rowid'
	.' INNER JOIN '.MAIN_DB_PREFIX.'expedition e ON e.rowid=el.fk_expedition'
	.' WHERE '.$sql_where
	.' GROUP BY e.rowid';
$q = $db->query($sql);
//echo '<pre>'.$sql.'</pre>';
while($row=$q->fetch_assoc()) {
	if (!isset($cmds[$row['fk_commande']]))
		continue;
	//var_dump($row);
	$cmds[$row['fk_commande']]['e_list'][$row['rowid']] = $row;
}

// Factures
$sql  = 'SELECT c.rowid AS fk_commande, f.rowid, f.ref, f.datef AS date_facture, f.date_valid, f.total_ht, GROUP_CONCAT(r.amount_ht, ";", IF(r.fk_facture IS NOT NULL,fk_facture,""), ";", IF(r.fk_facture_line IS NOT NULL,r.fk_facture_line,""), ";", if(r.description IS NOT NULL,r.description,"")) AS remise_except'
	.' '.$sql_from
	.' INNER JOIN '.MAIN_DB_PREFIX.'element_element j ON j.fk_source=c.rowid AND j.sourcetype=\'commande\' AND j.targettype=\'facture\''
	.' INNER JOIN '.MAIN_DB_PREFIX.'facture f ON f.rowid=j.fk_target'
	.' LEFT JOIN '.MAIN_DB_PREFIX.'societe_remise_except r ON r.fk_facture_source=f.rowid'
	.' WHERE '.$sql_where
	.' GROUP BY f.rowid';
$q = $db->query($sql);
//echo '<pre>'.$sql.'</pre>';
while($row=$q->fetch_assoc()) {
	// @todo optimiser !
	if (!isset($cmds[$row['fk_commande']]))
		continue;
	if (!empty($row['remise_except'])) {
		//var_dump($row['remise_except']);
		foreach(explode(',', $row['remise_except']) as $r_line)
			$cmds[$row['fk_commande']]['remise_except'][] = $r_line;
	}
	$cmds[$row['fk_commande']]['f_list'][$row['rowid']] = $row;
}

echo '<p>'.$cmds_nb.' enregistrements - Affichage 1 => '.count($cmds).'</p>';

?>
<table id="commandes" cellspacing="0" class="liste">
	<thead>
	<tr>
		<th>REF</th>
		<th>Commercial</th>
		<th>Date créa<br />Date valid<br />Date clôture</th>
		<!--<th>Statut</th>-->
		<th>Montant HT</th>
		<th>Ref client<br />Note privée</th>
		<th>Client</th>
		<th>Acomptes/Avoirs</th>
		<th>Facturation</th>
		<th>Expéditions</th>
		<!--<th>Réceptions</th>-->
		<th></th>
	</tr>
	</thead>
	<tbody>
<?php

foreach($cmds as $rowid=>$row) {
	$f_total_ht = 0;
	if (!empty($row['f_list'])) {
		foreach($row['f_list'] as $e) {
			$f_total_ht += $e['total_ht'];
		}
	}
	echo '<tr class="oddeven">';
	echo '<td><a href="/commande/card.php?id='.$rowid.'">'.$row['ref'].'</a></td>';
	echo '<td>'.$row['user_nom'].'</td>';
	echo '<td><span style="color: grey;">'.$row['date_commande'].'</span><br />'.substr($row['date_valid'], 0, 10).'<br /><b>'.substr($row['date_cloture'], 0, 10).'</b></td>';
	//echo '<td>'.$row['fk_statut'].'</td>';
	echo '<td class="right">'.price($row['total_ht']).'</td>';
	echo '<td><div style="max-width: 250px;max-height: 6.5em;overflow-y: scroll;">'.$row['ref_client'].'<br />'.$row['note_private'].'</div></td>';
	echo '<td><a href="/comm/card.php?socid='.$row['fk_soc'].'">'.$row['s_nom'].'</a></td>';
	echo '<td><div style="max-width: 250px;max-height: 7em;overflow-y: scroll;">';
	if (!empty($row['remise_except'])) {
		foreach($row['remise_except'] as $re_line) {
			//echo $re_line.'<br />';
			$re_line = explode(';', $re_line);
			echo price($re_line[0]).' '.($re_line[3]=='(DEPOSIT)' ?' (ACOMPTE)' :($re_line[3]=='(CREDIT_NOTE)' ?' (AVOIR)' :'')).($re_line[1] ?' => <a href="/compta/facture/card.php?id='.$re_line[1].'" style="color:green;">OK</a>' :($re_line[2] ?' => <span style="color:green;">OK</span>' :' => <span style="font-weight:bold;color:red;">A UTILISER</span>')).'<br />';
		}
	}
	echo '</div></td>';
	echo '<td><div style="max-width: 250px;max-height: 7em;overflow-y: scroll;">';
	if ($row['facture']) {
		if (empty($row['f_list']))
			echo '<span style="font-weight: bold;color:red;">Classé Facturé sans facture</span>';
		else
			echo 'Classé Facturé';
	}
	if (!empty($row['f_list'])) {
		$f_nb = count($row['f_list']);
		echo ' ('.$f_nb.') '.price($f_total_ht).' => '.(round($row['total_ht']-$f_total_ht, 2)==0 ?'<span style="font-weight: bold;color:green;">OK</span>' :'<span style="font-weight: bold;color:red;">Reste '.price($row['total_ht']-$f_total_ht)).'</span><br />';
		foreach($row['f_list'] as $e) {
			echo '-&nbsp;<a href="/compta/facture/card.php?id='.$e['rowid'].'">'.$e['ref'].'</a> du '.substr($e['date_facture'], 0, 10).'<br />';
		}
		if ($f_total_ht == 0) {
			echo '<span style="font-weight: bold;color:red;">AVOIR Total ? <a href="/commande/card.php?id='.$rowid.'#builddoc_form" style="font-weight: bold;color:blue;">Annuler Cmd ?</a></span><br />';
		}
		elseif (abs($row['total_ht'] - $f_total_ht) <= $MAX_CT && !$row['facture']) {
			echo '<a href="/commande/card.php?id='.$rowid.'#builddoc_form" style="font-weight: bold;color:blue;">Classer facturé ?</a><br />';
		}
		elseif (abs($row['total_ht'] - $f_total_ht) > $MAX_CT && !$row['facture']) {
			echo '<a href="/commande/card.php?id='.$rowid.'#builddoc_form" style="font-weight: bold;color:blue;">Facturer ?</a><br />';
		}
	}
	echo '</div></td>';
	echo '<td><div style="max-width: 250px;max-height: 7em;overflow-y: scroll;">';
	if (!empty($row['date_livraison']))
		echo '<span style="'.(!$row['expe_ok'] ?'font-weight: bold;color:red;' :'').'">Date prévue : '.substr($row['date_livraison'], 0, 10).'</span><br />';
	if ($row['fk_statut']==2)
		echo 'Expédié'.(!$row['expe_ok'] ?' partiel... - <a href="/expedition/shipment.php?id='.$rowid.'" style="font-weight: bold;color:blue;">Expédier ?</a>' :'<br /><a href="/commande/card.php?id='.$rowid.'#builddoc_form" style="font-weight: bold;color:blue;">Classer livré ?</a>');
	elseif ($row['fk_statut']==3)
		echo 'Livré'.(!$row['expe_ok'] ?' (partiellement)' :' (et tout expédié) => <span style="font-weight:bold;color:green;">OK</span>');
	elseif(!$row['expe_ok'])
		echo '<a href="/expedition/shipment.php?id='.$rowid.'" style="font-weight: bold;color:blue;">Expédier ?</a>';
	else
		echo 'Tout expédié => <span style="font-weight:bold;color:green;">OK</span><br /><a href="/commande/card.php?id='.$rowid.'#builddoc_form" style="font-weight: bold;color:blue;">Classer livré ?</a>';
	if (!empty($row['e_list'])) {
		$e_nb = count($row['e_list']);
		echo ' ('.$e_nb.')<br />';
		foreach($row['e_list'] as $e) {
			echo '-&nbsp;<a href="/expedition/card.php?id='.$e['rowid'].'">'.$e['ref'].'</a> du '.substr($e['date_creation'], 0, 10).'<br />';
		}
	}
	echo '</div></td>';
	//echo '<td></td>';
	//echo '<td>EXPEDIER<br />FACTURER</td>';
	echo '<td><input type="checkbox" name="cmds[]" value="'.$rowid.'" /></td>';
	echo '</tr>';
}

?>
</tbody>
</table>
