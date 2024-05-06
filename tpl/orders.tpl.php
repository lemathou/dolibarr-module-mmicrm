<style type="text/css">
#commandes tbody tr td {
	border-top: 1px solid #aaa;
	border-right: 1px solid #ddd;
}
</style>
<?php

$datediff = 60;

?>

<p>Liste des commandes non expédiées ou non facturées, de plus de <?php echo $datediff; ?> jours, avec traitements simplifiés</p>

<?php

$datediff = 60;

$sql_from  = 'FROM '.MAIN_DB_PREFIX.'commande c
	LEFT JOIN '.MAIN_DB_PREFIX.'commande_extrafields c2 ON c2.fk_object=c.rowid
	INNER JOIN '.MAIN_DB_PREFIX.'societe s ON c.fk_soc=s.rowid
	LEFT JOIN '.MAIN_DB_PREFIX.'commandedet cl ON cl.fk_commande=c.rowid
	LEFT JOIN '.MAIN_DB_PREFIX.'expeditiondet el ON el.fk_origin_line=cl.rowid
	LEFT JOIN '.MAIN_DB_PREFIX.'expedition e ON e.rowid=el.fk_expedition';

$sql_where = 'c.date_commande IS NOT NULL
	AND c.fk_statut >= 1
	AND DATEDIFF(c.date_commande, NOW())<=-'.$datediff.'
	AND (c2.expe_ok != 1 OR c.facture != 1)';

$sql_count  = 'SELECT COUNT(DISTINCT c.rowid)
	'.$sql_from.'
	WHERE '.$sql_where;
$q = $db->query($sql_count);
list($nb) = $q->fetch_row();
echo '<p>'.$nb.' enregistrements</p>';

?>
<table id="commandes" cellspacing="0">
	<theah>
	<tr>
		<th>REF</th>
		<th>Date</th>
		<th>Date clôture</th>
		<th>Statut</th>
		<th>Montant HT</th>
		<th>Note privée</th>
		<th>Ref client</th>
		<th>Client</th>
		<th>Facturé</th>
		<th>Expédié tout</th>
		<th>Expéditions</th>
		<th>Réceptions</th>
		<th></th>
	</tr>
	</theah>
	<tbody>
<?php

$sql  = 'SELECT c2.*, c.*, DATEDIFF(c.date_commande, NOW()) AS datediff, s.nom, GROUP_CONCAT(e.rowid, "#", e.ref, "#", e.date_creation, "#", e.date_valid SEPARATOR ",") AS e_list
	'.$sql_from.'
	WHERE '.$sql_where.'
	GROUP BY c.rowid
	ORDER BY c.date_commande DESC
	LIMIT 0, 100';

$q = $db->query($sql);
while($row=$q->fetch_assoc()) {
	echo '<tr>';
	echo '<td><a href="/commande/card.php?id='.$row['rowid'].'">'.$row['ref'].'</a></td>';
	echo '<td>'.$row['date_commande'].'</td>';
	echo '<td>'.substr($row['date_cloture'], 0, 10).'</td>';
	echo '<td>'.$row['fk_statut'].'</td>';
	echo '<td>'.$row['amount_ht'].'</td>';
	echo '<td><div style="max-width: 250px;max-height: 3em;overflow:auto;">'.$row['note_private'].'</div></td>';
	echo '<td><div style="max-width: 250px;max-height: 1.5em;overflow:auto;">'.$row['ref_client'].'</div></td>';
	echo '<td><a href="/comm/card.php?socid='.$row['fk_soc'].'">'.$row['nom'].'</a></td>';
	echo '<td>'.$row['facture'].'</td>';
	echo '<td>'.$row['expe_ok'].'</td>';
	echo '<td><div style="max-width: 250px;max-height: 3em;overflow:auto;">';
	$e_list = !empty($row['e_list']) ?explode(",", $row['e_list']) :[];
	if (!empty($e_list) && ($e_nb=count($e_list))>1)
		echo $e_nb.' expéditions :<br />';
	foreach($e_list as $e) {
		$e = explode('#', $e);
		echo '<a href="expedition/card.php?id='.$e[0].'">'.$e[1].'</a> du '.substr($e[2], 0, 10).'<br />';
	}
	echo '</div></td>';
	echo '<td></td>';
	echo '<td>EXPEDIER<br />RECEVOIR<br />FACTURER</td>';
	echo '<td></td>';
	echo '</tr>';
}

?>
</tbody>
</table>
