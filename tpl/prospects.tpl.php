<style type="text/css">
	/* Suivi commercial édité depuis la liste (RM2858). */
	#crm_list td.crm-urgent-col, #crm_list th.crm-urgent-col {
		width: 1.8em;
		text-align: center;
		padding: 0 2px;
	}
	#crm_list button.crm-urgent {
		border: 1px solid transparent;
		background: none;
		cursor: pointer;
		font-weight: bold;
		line-height: 1.2;
		width: 1.6em;
		color: #bbb;
		border-radius: 3px;
	}
	#crm_list button.crm-urgent.on {
		color: #fff;
		background-color: #c0392b;
	}
	#crm_list button.crm-urgent:hover {
		border-color: #c0392b;
	}
	#crm_list button.crm-urgent.pending {
		opacity: 0.4;
		cursor: wait;
	}
	/* Textarea bas au repos, agrandi au focus : la liste est dense, une note à
	   plusieurs lignes en permanence ferait exploser la hauteur des lignes. */
	#crm_list textarea.crm-note {
		width: 14em;
		min-width: 100%;
		height: 1.7em;
		resize: vertical;
		font-size: 0.9em;
		padding: 2px 4px;
		border: 1px solid transparent;
		background: rgba(127, 127, 127, 0.07);
		border-radius: 3px;
	}
	#crm_list textarea.crm-note:focus {
		height: 5em;
		border-color: #888;
		background: rgba(127, 127, 127, 0.15);
	}
	#crm_list textarea.crm-note.saved {
		border-color: #27ae60;
	}
	#crm_list textarea.crm-note.failed {
		border-color: #c0392b;
		background: rgba(192, 57, 43, 0.12);
	}
	/* Commerciaux désactivés : ils restent sélectionnables (retrouver le
	   portefeuille d'un partant) mais passent au second plan. Le tri les place
	   déjà en fin de liste. */
	.crmuserfilter option.crmuser-inactive {
		color: #999;
		font-style: italic;
	}
	/* Les deux familles de colonnes restent repérables d'un coup d'oeil, mais en
	   teintes translucides : un fond opaque clair rendait le texte illisible dès
	   que Dolibarr passait en thème sombre. */
	#crm_list td.cmd {
		background-color: rgba(214, 158, 46, 0.10);
	}
	#crm_list td.rel {
		background-color: rgba(159, 92, 190, 0.10);
	}
	#crm_list .sort {
		border: 1px solid transparent;
		padding: 1px;
		text-decoration: none;
	}
	#crm_list .sort.active {
		border-color: currentColor;
	}
	#crm_list .a-user,
	#crm_list .k-user,
	#crm_list .k-contact {
		opacity: 0.7;
		font-size: 0.9em;
	}

	#filters input[type="text"],
	#filters input:not([type]) {
		width: 97%;
		padding-right: 0;
		margin-right: 0;
	}
</style>
<?php

$p_group_list = [];
$sql = 'SELECT rowid AS `value`, label
	FROM `'.MAIN_DB_PREFIX.'c_societe_p_group`
	WHERE `active` = 1
	ORDER BY `pos`';
$q = $db->query($sql);
while($row = $q->fetch_array()) {
	$p_group_list[$row['value']] = $row;
}

// Combo utilisateurs natif Dolibarr (tri : actifs d'abord puis prénom, inactifs en fin)
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
$form = new Form($db);

/**
 * Rend un combo "utilisateur" Dolibarr pour un filtre (commercial, relancé par, ...).
 * forcecombo=1 : select natif (pas de select2 ajax, incompatible avec name="filters[...]").
 */
function user_filter_select($name, $value)
{
	global $db;
	$selected = (isset($value) && $value !== '' && (int) $value > 0) ? (int) $value : '';

	// Combo construit ici plutôt que par Form::select_dolusers() : le natif trie
	// déjà les actifs d'abord (ORDER BY u.statut DESC) mais ne sait pas GRISER les
	// comptes désactivés — il se contente d'accoler « (Désactivé) » au libellé,
	// texte traduit sur lequel on ne peut pas accrocher un style de façon fiable.
	// Le filtre d'entité reproduit celui du natif (RM2856).
	$sql = 'SELECT u.rowid, u.lastname, u.firstname, u.statut'
		.' FROM '.MAIN_DB_PREFIX.'user u'
		.' WHERE u.entity IN ('.getEntity('user').')'
		.' ORDER BY u.statut DESC, u.firstname ASC, u.lastname ASC';
	$q = $db->query($sql);
	$out = '<select name="filters['.$name.']" class="crmuserfilter maxwidth200">';
	$out .= '<option value="">&nbsp;</option>';
	if ($q) {
		while ($u = $q->fetch_assoc()) {
			$label = trim($u['firstname'].' '.$u['lastname']);
			if ($label === '')
				$label = '#'.$u['rowid'];
			$inactive = ((int) $u['statut'] === 0);
			$out .= '<option value="'.((int) $u['rowid']).'"'
				.($inactive ? ' class="crmuser-inactive"' : '')
				.($selected === (int) $u['rowid'] ? ' selected' : '').'>'
				.h($label).($inactive ? ' (inactif)' : '').'</option>';
		}
	}
	$out .= '</select>';
	return $out;
}

function date_fromsql($date)
{
	if (empty($date))
		return '';
	$e = explode('-', substr($date, 0, 10));
	$e = array_reverse($e);

	return implode('/', $e);
}

/** Échappement HTML d'une valeur pouvant être nulle (sortie de requête). */
function h($value)
{
	return dol_escape_htmltag((string) ($value ?? ''));
}

/** Initiales d'un utilisateur (prénom + nom), tolérantes aux valeurs nulles. */
function initials($firstname, $lastname)
{
	return dol_escape_htmltag(mb_substr((string) ($firstname ?? ''), 0, 1).mb_substr((string) ($lastname ?? ''), 0, 1));
}

function col_sort_aff($sort_name)
{
	global $sort_list, $filters_url, $sort, $sort_order;

	$sort_name_order = ($sort==$sort_name ?($sort_order=='DESC' ?'DESC' :'ASC') :(!empty($sort_list[$sort_name]['sql_order']) ?$sort_list[$sort_name]['sql_order'] :'ASC'));

	return '<a class="sort'.($sort==$sort_name && $sort_name_order=='ASC' ?' active' :'').'" href="?'.$filters_url.'&sort='.$sort_name.'&sort_order=ASC">'.('&darr;').'</a>'
		.'<a class="sort'.($sort==$sort_name && $sort_name_order=='DESC' ?' active' :'').'" href="?'.$filters_url.'&sort='.$sort_name.'&sort_order=DESC">'.('&uarr;').'</a> '.(!empty($sort_list[$sort_name]['label_short']) ?$sort_list[$sort_name]['label_short'] :$sort_list[$sort_name]['label']);
}

function col_filter_aff($filter_name)
{
	global $filter_list, $filters_url, $filters;
	if (empty($filter_list[$filter_name]))
		return '';

	if (in_array($filter_list[$filter_name]['type'], ['string', 'int'])) {
		return '<input name="filters['.$filter_name.']" value="'.(!empty($filters[$filter_name]) ?h($filters[$filter_name]) :'').'" />';
	}
	elseif ($filter_list[$filter_name]['type'] == 'userselect') {
		return user_filter_select($filter_name, $filters[$filter_name] ?? '');
	}
	elseif ($filter_list[$filter_name]['type'] == 'radio') {
		$ret = '<select name="filters['.$filter_name.']" onchange="this.form.submit()"><option value="">--</option>';
		foreach($filter_list[$filter_name]['list'] as $name=>$option)
			$ret .= '<option value="'.h($name).'"'.(!empty($filters[$filter_name]) && $filters[$filter_name]==$name ?' selected' :'').'>'.h($option['label']).'</option>';
		$ret .= '</select>';
		return $ret;
	}
	elseif ($filter_list[$filter_name]['type'] == 'bool') {
		return '<input type="hidden" name="filters['.$filter_name.']" value="" />'
			.'<input id="filter_'.$filter_name.'" type="checkbox" name="filters['.$filter_name.']" value="1"'.(!empty($filters[$filter_name]) ?' checked' :'').' onchange="this.form.submit()" style="width: auto;" /> <label for="filter_'.$filter_name.'">'.$filter_list[$filter_name]['label'].'</label>';
	}
	elseif ($filter_list[$filter_name]['type'] == 'select') {
		$ret = '<select name="filters['.$filter_name.']" onchange="this.form.submit()"><option value="">--</option>';
		foreach($filter_list[$filter_name]['list'] as $name=>$option)
			$ret .= '<option value="'.h($name).'"'.(!empty($filters[$filter_name]) && $filters[$filter_name]==$name ?' selected' :'').'>'.h($option['label']).'</option>';
		$ret .= '</select>';
		return $ret;
	}
}

/**
 * Prédicat SQL « cet événement est un contact client » (RM2779).
 *
 * L'agenda est massivement alimenté par des événements automatiques : sur ce parc,
 * 97,8 % des actioncomm passés sont des AC_OTH_AUTO, dont l'essentiel n'est que de
 * la traçabilité (« Tiers X modifié », « Commande COxxxx validée »). Les afficher
 * bruts noierait la colonne. On ne retient donc que les contacts réels : les codes
 * de contact d'un côté, et de l'autre les mails partis automatiquement, qui sont
 * bien un contact même s'ils sont générés (« E-mail envoyé par Calicote à … »).
 *
 * Les deux réglages sont des constantes, pour pouvoir ajuster le tri à l'usage sans
 * repasser par une MEP :
 *   MMICRM_PROSPECTS_EVENT_CODES    codes retenus, séparés par des virgules
 *   MMICRM_PROSPECTS_EVENT_AUTOMAIL motif LIKE rattrapant les mails automatiques
 *                                   (vide = ne rien rattraper)
 *
 * @param string $a  alias de la table actioncomm
 * @param string $ca alias de la table c_actioncomm jointe dessus
 * @return string    prédicat SQL, toujours entre parenthèses
 */
function mmicrm_event_is_contact_sql($a, $ca)
{
	global $db;

	$codes = getDolGlobalString('MMICRM_PROSPECTS_EVENT_CODES');
	if ($codes === '')
		$codes = 'AC_TEL,AC_RDV,AC_EMAIL,AC_EMAIL_IN,AC_FAX,AC_SMS,AC_SMS_IN,AC_INT,AC_OTH';
	$in = [];
	foreach (explode(',', $codes) as $code) {
		$code = trim($code);
		if ($code !== '')
			$in[] = '"'.$db->escape($code).'"';
	}

	$conds = [];
	if (!empty($in))
		$conds[] = $ca.'.code IN ('.implode(', ', $in).')';

	$automail = getDolGlobalString('MMICRM_PROSPECTS_EVENT_AUTOMAIL');
	if ($automail === '')
		$automail = '%mail envoy%';
	if ($automail !== '-')
		$conds[] = '('.$ca.'.code = "AC_OTH_AUTO" AND '.$a.'.label LIKE "'.$db->escape($automail).'")';

	return empty($conds) ? '(1=1)' : '('.implode(' OR ', $conds).')';
}

$sql_begin = 0;
$sql_limit = 1000;

$sql_order = '';
$sql_sort = '';
$sql_join = [];
$sql_where = [];
$sql_having = [];

echo '<form method="GET">';
echo '<input type="hidden" name="cols_save" value="1" />';
echo '<p>Raccourcis :';
echo ' <a href="?">TOUT</a>';
echo ' | <a href="?filters[cmd_nb_min]=1&filters[cmd_nb_max]=1">Une seule commande</a>';
echo ' | <a href="?filters[prospect]=prospect&filters[relance_max]=1">Prospect une seule relance</a>';
echo '</p>';
echo '<p><a href="javascript:;" onclick="$(\'#filters\').toggle()">Afficher/Cacher les filtres supplémentaires</a></p>';
echo '<table id="filters" class="border centpercent" style="display:none;">';

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
	// Dernière commande par tiers, en table dérivée : sert au TRI des colonnes
	// « dernière relance » et « dernier événement », qui masquent leur valeur
	// quand elle précède la dernière commande (RM2856). Un LEFT JOIN direct sur
	// commande multiplierait les lignes et fausserait les COUNT du même SELECT.
	'cmax' => 'LEFT JOIN (SELECT cmi.fk_soc, MAX(cmi.date_commande) AS dc'
		.' FROM '.MAIN_DB_PREFIX.'commande cmi GROUP BY cmi.fk_soc) cmax ON cmax.fk_soc=s.rowid',
	'a' => 'LEFT JOIN '.MAIN_DB_PREFIX.'actioncomm a ON a.fk_soc=s.rowid AND a.datep > NOW()',
	// 'a' ne voit que les événements à venir : le tri sur les événements réalisés
	// a besoin de son propre join (RM2779), filtré comme la colonne qu'il trie.
	// Table dérivée plutôt que deux LEFT JOIN en cascade : le filtre a besoin de
	// c_actioncomm, qui ne peut pas être joint avant actioncomm dans un ON.
	'a2' => 'LEFT JOIN (SELECT a2i.fk_soc, MAX(a2i.datep) AS datep'
		.' FROM '.MAIN_DB_PREFIX.'actioncomm a2i'
		.' LEFT JOIN '.MAIN_DB_PREFIX.'c_actioncomm ca2i ON ca2i.id=a2i.fk_action'
		.' WHERE a2i.datep <= NOW() AND '.mmicrm_event_is_contact_sql('a2i', 'ca2i')
		.' GROUP BY a2i.fk_soc) a2 ON a2.fk_soc=s.rowid',
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
		'col_only' => true,
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
	// Suivi commercial (RM2858) : societe_extrafields est déjà joint en INNER
	// pour p_group, aucun join supplémentaire n'est nécessaire.
	'crm_urgent' => [
		'label' => 'À traiter en urgence',
		'type' => 'checkbox',
		'sql_where' => 's2.crm_urgent = 1',
	],
	'relance_histo' => [
		'label' => 'Pas relancé depuis n mois',
		'type' => 'int',
		'sql_join' => 'k', // Pas évident, la table peut service à plusieurs cas de figures
		'sql_where' => 'k.fk_soc IS NULL',
	],
	'relance_histo_user' => [
		'label' => 'Relancé par utilisateur',
		'type' => 'userselect',
		'sql_where' => '(SELECT COUNT(k2.rowid) FROM '.MAIN_DB_PREFIX.'contacttracking AS k2 WHERE k2.fk_soc=s.rowid AND k2.fk_user_creat = %value LIMIT 1) > 0',
	],
	'relance_not_before_cmd' => [
		'label' => 'Pas relancé depuis dernière commande',
		'type' => 'bool',
		'sql_join' => 'c',
		'sql_where' => '(SELECT COUNT(k2.rowid) FROM '.MAIN_DB_PREFIX.'contacttracking AS k2 WHERE k2.fk_soc=s.rowid AND (c.rowid IS NULL OR k2.date_creation >= DATE(c.date_commande)) LIMIT 1) = 0',
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
		'col_only' => true,
	],
	'client_ville' => [
		'label' => 'Ville client',
		'type' => 'string',
		'sql_where' => 's.town LIKE "%%value%"',
		'col_only' => true,
	],
	'client_nom' => [
		'label' => 'Nom client',
		'type' => 'string',
		'sql_where' => 's.nom LIKE "%%value%" OR s.name_alias LIKE "%%value%"',
		'col_only' => true,
	],
	'groupe_client' => [
		'label' => 'Groupe',
		'type' => 'select',
		'list' => $p_group_list,
		'sql_where' => 's2.p_group = %value',
		'col_only' => true,
	],
	'groupe_client_pro' => [
		'label' => 'PRO',
		'type' => 'bool',
		'sql_where' => 's2.p_group > 3',
		'col_only' => true,
	],
	'commercial' => [
		'label' => 'Commercial',
		'type' => 'userselect',
		'sql_where' => '(SELECT COUNT(scf.rowid) FROM '.MAIN_DB_PREFIX.'societe_commerciaux scf WHERE scf.fk_soc = s.rowid AND scf.fk_user = %value LIMIT 1) > 0',
	],
	'catalogue_pro' => [
		'label' => 'A un catalogue pro',
		'type' => 'bool',
		'sql_where' => 's2.p_catalogpro = 1',
		'col_only' => true,
	],
];

$filters_url = '';
$filters = GETPOST('filters');
if (!is_array($filters))
	$filters = [];
echo '<td>';
foreach($filter_list as $i=>$j) {

	if ($j['type']=='userselect') {
		if (!empty($filters[$i]) && (int) $filters[$i] > 0 && !empty($j['sql_where']))
			$sql_where[] = str_replace('%value', (int) $filters[$i], $j['sql_where']);
		echo '<label>'.$j['label'].' '.user_filter_select($i, $filters[$i] ?? '').'</label><br />';
	}
	elseif ($j['type']=='select') {
		echo '<select name="filters['.$i.']" onchange="this.form.submit()"><option value="">--</option>';
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
				if (!empty($j['sql_where']))
					$sql_where[] = str_replace('%value', (int) $j2['value'], $j['sql_where']);
				if (!empty($j2['sql_where']))
					$sql_where[] = $j2['sql_where'];
				if (!empty($j2['sql_having']))
					$sql_having[] = $j2['sql_having'];
			}
			else {
				$checked = '';
			}

			echo '<option value="'.h($i2).'"'.$checked.'>'.h($j2['label']).'</option>';
		}
		echo '</select>';
	}
	elseif ($j['type']=='radio') {
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

			if (empty($j['col_only'])) {
				echo '<input id="filter_'.$i.'_all" type="radio" name="filters['.$i.']" value="" /> <label for="filter_'.$i.'_all">TOUT</label><br />'
					.'<input id="filter_'.$i.'_'.$i2.'" type="radio" name="filters['.$i.']"'.$checked.' value="'.$i2.'" /> <label for="filter_'.$i.'_'.$i2.'">'.$j2['label'].'</label><br />';
			}
		}
	}
	elseif ($j['type']=='int') {
		if (isset($filters[$i]) && is_numeric($filters[$i])) {
			$value = 0 + $filters[$i];
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
		
		if (empty($j['col_only']))
			echo '<label for="filter_'.$i.'">'.$j['label'].'</label>: <input id="filter_'.$i.'" type="text" name="filters['.$i.']" value="'.h($value).'" size="5" /><br />';
	}
	elseif ($j['type']=='string') {
		if (isset($filters[$i]) && !empty($filters[$i])) {
			// $value part dans un LIKE construit par concaténation : à échapper avant
			// toute chose (elle vient de GETPOST).
			$value = $db->escape($filters[$i]);
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


		if (empty($j['col_only']))
			echo '<label for="filter_'.$i.'">'.$j['label'].'</label>: <input id="filter_'.$i.'" type="text" name="filters['.$i.']" value="'.h($filters[$i] ?? '').'" size="5" /><br />';
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


		if (empty($j['col_only']))
			echo '<label for="filter_'.$i.'">'.$j['label'].'</label>: <input id="filter_'.$i.'" type="checkbox" name="filters['.$i.']" value="1"'.$checked.' /><br />';
	}
}
echo '</td>';

$cols_list = [
	'ville' => ['label'=>'Ville'],
	'commercial' => ['label'=>'Commercial'],
	'cmd_tot_mt' => ['label'=>'Commandes : Montant total'],
	'code_client' => ['label'=>'Code client'],
	'catalogue_pro' => ['label'=>'Catalogue pro'],
	'urgent' => ['label'=>'Urgence (RM2858)'],
	'crm_note' => ['label'=>'Note de suivi (RM2858)'],
];
// Colonnes montrées à qui n'a jamais enregistré de préférence : les deux nouvelles
// le sont, sans quoi la fonctionnalité resterait invisible à l'arrivée sur la page.
$cols_default = ['urgent', 'crm_note'];
$cols = GETPOST('cols', 'array');
if (GETPOST('cols_save', 'int')) {
	// Soumission du formulaire de filtres -> on mémorise le choix (même vide)
	if (!function_exists('dol_set_user_param'))
		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	dol_set_user_param($db, $conf, $user, ['MMICRM_PROSPECTS_COLS' => implode(',', $cols)]);
}
elseif (!GETPOSTISSET('cols') && !empty($user->conf->MMICRM_PROSPECTS_COLS)) {
	// Pas de cols dans l'URL (arrivée sur la page, raccourci) -> préférence sauvegardée
	$cols = explode(',', $user->conf->MMICRM_PROSPECTS_COLS);
}
elseif (!GETPOSTISSET('cols') && !isset($user->conf->MMICRM_PROSPECTS_COLS)) {
	// Jamais de préférence enregistrée -> défaut. On distingue « pas de préférence »
	// d'une préférence VIDE (l'utilisateur a tout décoché) : isset(), pas empty().
	$cols = $cols_default;
}
echo '<td>Colonnes optionnelles :<br />';
foreach($cols_list as $i=>$j) {
	if (in_array($i, $cols)) {
		$checked = ' checked';
		$filters_url .= '&cols[]='.$i;
	}
	else {
		$checked = '';
	}
	echo '<label><input type="checkbox" name="cols[]" value="'.$i.'"'.$checked.' style="width: auto;" /> '.$j['label'].'</label><br />';
}
echo '</td>';

$sort_list = [
	'nom' => [
		'label' => 'Nom Client',
		'label_short' => 'Nom',
		'sql_sort' => 's.nom',
	],
	// Les deux colonnes suivantes masquent leur valeur quand elle précède la
	// dernière commande (RM2856) : le tri doit voir la MÊME chose que l'affichage,
	// sinon une ligne vide se retrouve triée sur une date invisible.
	'relance_last' => [
		'label' => 'Dernière relance',
		'label_short' => 'Dernière',
		'sql_join' => 'k',
		'sql_join_extra' => ['cmax'],
		'sql_sort' => 'CASE WHEN cmax.dc IS NULL OR MAX(k.date_creation) > cmax.dc THEN MAX(k.date_creation) END',
	],
	'evenement_last' => [
		'label' => 'Dernier événement réalisé',
		'label_short' => 'Dern. évé.',
		'sql_join' => 'a2',
		'sql_join_extra' => ['cmax'],
		'sql_sort' => 'CASE WHEN cmax.dc IS NULL OR MAX(a2.datep) > cmax.dc THEN MAX(a2.datep) END',
		'sql_order' => 'DESC',
	],
	'relance_next' => [
		'label' => 'Prochaine relance',
		'label_short' => 'Prochaine',
		'sql_join' => 'a',
		'sql_sort' => 'MIN(a.datep)',
	],
	'commandes_nb' => [
		'label' => 'Nb commandes',
		'label_short' => 'Nbre',
		'sql_join' => 'c',
		'sql_sort' => 'COUNT(DISTINCT c.rowid)',
		'sql_order' => 'DESC',
	],
	'commande_last' => [
		'label' => 'Dernière commande',
		'label_short' => 'Date',
		'sql_join' => 'c',
		'sql_sort' => 'MAX(c.date_commande)',
	],
	'client_lastupdate' => [
		'label' => 'Dernière action/modif',
		'label_short' => 'Date',
		'sql_sort' => 'MAX(s.tms)',
	],
];
$sort = GETPOST('sort');
$sort_order = GETPOST('sort_order');
echo '<td><select name="sort"><option value="">--</option>';
foreach($sort_list as $i=>$j) {
	if ($sort==$i) {
		$selected = ' selected';
		if (!empty($j['sql_join']))
			$sql_join[$j['sql_join']] = $join_list[$j['sql_join']];
		// Joins dont l'expression de tri a besoin en plus du sien (RM2856).
		if (!empty($j['sql_join_extra']))
			foreach ($j['sql_join_extra'] as $jx)
				$sql_join[$jx] = $join_list[$jx];
		if (! $sort_order && !empty($j['sql_order']))
			$sort_order = $j['sql_order'];
		$sql_sort = $j['sql_sort'].($sort_order ? ' '.$sort_order :'');
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

$p_groups = [];
$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'c_societe_p_group';
$q = $db->query($sql);
while($row = $q->fetch_assoc()) {
        $p_groups[$row['rowid']] = $row;
}

// Base
$l = $l_s = [];
$sql = 'SELECT s.rowid, s.tms, s.nom, s.name_alias, s.code_client, s.town, s2.p_group,
		s2.crm_urgent, s2.crm_note,
		s2.p_catalogpro, s2.p_catalogpro_url, s2.p_catalogpro_info
	FROM '.MAIN_DB_PREFIX.'societe s
	INNER JOIN '.MAIN_DB_PREFIX.'societe_extrafields s2 ON s2.fk_object=s.rowid
	'.(!empty($sql_join) ?' '.implode(' ', $sql_join) :'').'
	WHERE s2.pro = 1
		'.(!empty($sql_where) ?' AND ('.implode(' AND ', $sql_where).')' :'').'
	GROUP BY s.rowid
	'.($sql_having  ?'HAVING '.implode(' AND ', $sql_having) :'').'
	'.($sql_sort  ?'ORDER BY '.$sql_sort :'').'
	LIMIT '.$sql_begin.', '.$sql_limit.'
';
$q = $db->query($sql);
echo '<p>'.$q->num_rows.' enregistrements</p>';
while($row = $q->fetch_assoc()) {
	$l[$row['rowid']] = $row;
	$l_s[] = $row['rowid'];
}

if (! empty($l_s)) {

	$l_c = [];
	// Commandes
	$sql = 'SELECT c.fk_soc AS rowid,
			COUNT(DISTINCT c.rowid) AS tot_nb, ROUND(SUM(c.total_ht), 2) AS tot_mt,
			MAX(c.rowid) AS last_rowid, MAX(c.date_commande) AS c_last_date
		FROM '.MAIN_DB_PREFIX.'commande c
		WHERE c.fk_soc IN ('.implode(', ', $l_s).')
		GROUP BY c.fk_soc';
	$q = $db->query($sql);

	while($row = $q->fetch_assoc()) {
		$l[$row['rowid']] = array_merge($l[$row['rowid']], $row);
		if (!empty($row['last_rowid']))
			$l_c[] = $row['last_rowid'];
	}
	// Dernière commande
	if (!empty($l_c)) {
		$sql = 'SELECT c.fk_soc AS rowid,
			c.rowid AS last_rowid, c.date_commande AS c_last_date, ROUND(c.total_ht, 2) as last_mt
		FROM '.MAIN_DB_PREFIX.'commande c
		WHERE c.rowid IN ('.implode(', ', $l_c).')
		GROUP BY c.fk_soc';
		$q = $db->query($sql);
		while($row = $q->fetch_assoc()) {
			$l[$row['rowid']] = array_merge($l[$row['rowid']], $row);
		}
	}

	$l_a = [];
	// Actioncom (prochaines relances = événements à venir)
	// La ligne retenue est la plus PROCHE (MIN(datep)) : SUBSTRING_INDEX(GROUP_CONCAT(...))
	// est l'idiome MySQL du « id de la ligne qui porte l'extremum ». MAX(a.id) ne le
	// garantissait pas, et MAX(a.datep) affichait la relance la plus LOINTAINE.
	$sql = 'SELECT a.fk_soc AS rowid, COUNT(DISTINCT a.id) AS a_nb, MIN(a.datep) AS a_last_date,
			SUBSTRING_INDEX(GROUP_CONCAT(a.id ORDER BY a.datep ASC, a.id ASC), \',\', 1) AS a_last_rowid
		FROM '.MAIN_DB_PREFIX.'actioncomm a
		WHERE a.fk_soc IN ('.implode(', ', $l_s).') AND a.datep > NOW()
		GROUP BY a.fk_soc';
	$q = $db->query($sql);
	while($row = $q->fetch_assoc()) {
		$l[$row['rowid']] = array_merge($l[$row['rowid']], $row);
		$l_a[] = (int) $row['a_last_rowid'];
	}
	if (!empty($l_a)) {
		$sql = 'SELECT a.fk_soc AS rowid, u.firstname AS a_last_user_firstname, u.lastname AS a_last_user_lastname
			FROM '.MAIN_DB_PREFIX.'actioncomm a
			LEFT JOIN '.MAIN_DB_PREFIX.'user AS u ON u.rowid=a.fk_user_action
			WHERE a.id IN ('.implode(', ', $l_a).')';
		$q = $db->query($sql);
		while($row = $q->fetch_assoc()) {
			$l[$row['rowid']] = array_merge($l[$row['rowid']], $row);
		}
	}

	$l_a2 = [];
	// Actioncom (derniers événements RÉALISÉS — RM2779)
	// Ces données étaient déjà calculées mais jamais affichées, et la boucle interne
	// réutilisait $row/$q de la boucle externe : l'itération s'arrêtait à la première
	// société. L'alias a_last_rowid écrasait en plus l'id de la prochaine relance.
	if (!empty($l_s)) {
		$sql = 'SELECT a.fk_soc AS rowid, COUNT(DISTINCT a.id) AS a_past_nb, MAX(a.datep) AS a_past_last_date,
				SUBSTRING_INDEX(GROUP_CONCAT(a.id ORDER BY a.datep DESC, a.id DESC), \',\', 1) AS a_past_last_rowid
			FROM '.MAIN_DB_PREFIX.'actioncomm a
			LEFT JOIN '.MAIN_DB_PREFIX.'c_actioncomm ca ON ca.id=a.fk_action
			WHERE a.fk_soc IN ('.implode(', ', $l_s).') AND a.datep <= NOW()
				AND '.mmicrm_event_is_contact_sql('a', 'ca').'
			GROUP BY a.fk_soc';
		$q = $db->query($sql);
		while($row = $q->fetch_assoc()) {
			$l[$row['rowid']] = array_merge($l[$row['rowid']], $row);
			$l_a2[] = (int) $row['a_past_last_rowid'];
		}
	}
	if (!empty($l_a2)) {
		$sql = 'SELECT a.fk_soc AS rowid, a.label AS a_past_last_label, ca.libelle AS a_past_last_type,
				u.firstname AS a_past_last_user_firstname, u.lastname AS a_past_last_user_lastname
			FROM '.MAIN_DB_PREFIX.'actioncomm a
			LEFT JOIN '.MAIN_DB_PREFIX.'user AS u ON u.rowid=a.fk_user_action
			LEFT JOIN '.MAIN_DB_PREFIX.'c_actioncomm ca ON ca.id=a.fk_action
			WHERE a.id IN ('.implode(', ', $l_a2).')';
		$q = $db->query($sql);
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
		$q = $db->query($sql);
		while($row = $q->fetch_assoc()) {
			$l[$row['rowid']] = array_merge($l[$row['rowid']], $row);
			$l_k[] = $row['k_last_rowid'];
			$sql = 'SELECT k.mode_contact AS k_last_contact_type, u.firstname AS k_last_user_firstname, u.lastname AS k_last_user_lastname
				FROM '.MAIN_DB_PREFIX.'contacttracking k
				LEFT JOIN '.MAIN_DB_PREFIX.'user AS u ON u.rowid=k.fk_user_creat
				WHERE k.rowid = '.$row['k_last_rowid'];
			$q2 = $db->query($sql);
			$row2 = $q2->fetch_assoc();
			$l[$row['rowid']] = array_merge($l[$row['rowid']], $row2);
		}
	}

	// Commercial (colonne optionnelle)
	if (in_array('commercial', $cols)) {
		$sql = 'SELECT sc.fk_soc AS rowid,
				GROUP_CONCAT(DISTINCT CONCAT(u.firstname, " ", u.lastname) SEPARATOR ", ") AS commercial
			FROM '.MAIN_DB_PREFIX.'societe_commerciaux sc
			INNER JOIN '.MAIN_DB_PREFIX.'user u ON u.rowid = sc.fk_user
			WHERE sc.fk_soc IN ('.implode(', ', $l_s).')
			GROUP BY sc.fk_soc';
		$q = $db->query($sql);
		while($row = $q->fetch_assoc()) {
			$l[$row['rowid']] = array_merge($l[$row['rowid']], $row);
		}
	}
}

// Une relance ou un événement ANTÉRIEUR à la dernière commande n'appelle plus
// d'action : le client a commandé depuis, la relance a porté. On neutralise la
// valeur ici plutôt qu'à l'echo, pour que tout ce qui suit (affichage compris)
// voie la même chose que le tri SQL, qui applique la même règle (RM2856).
// Sans effet sur « Dernière action/modif » : une date écartée ici est par
// construction inférieure à c_last_date, qui reste dans le max ci-dessous.
foreach ($l as &$row) {
	$last_cmd = $row['c_last_date'] ?? null;
	if (empty($last_cmd))
		continue;
	if (!empty($row['k_last_date']) && $row['k_last_date'] < $last_cmd)
		$row['k_last_rowid'] = $row['k_last_date'] = null;
	if (!empty($row['a_past_last_date']) && $row['a_past_last_date'] < $last_cmd)
		$row['a_past_last_rowid'] = $row['a_past_last_date'] = null;
}
unset($row);

// « Dernière action/modif » : uniquement des dates PASSÉES. a_last_date est la
// prochaine relance (datep > NOW()) : l'inclure faisait afficher une date future
// dans une colonne censée dire quand le tiers a bougé pour la dernière fois.
foreach($l as &$row) {
	$row['udates'] = [$row['tms']];
	if (isset($row['k_last_date']))
		$row['udates'][] = $row['k_last_date'];
	if (isset($row['c_last_date']))
		$row['udates'][] = $row['c_last_date'];
	if (isset($row['a_past_last_date']))
		$row['udates'][] = $row['a_past_last_date'];
	$row['udate'] = max($row['udates']);
}
unset($row);

// resort if needed (client_lastupdate)
if ($sort == 'client_lastupdate') {
	if ($sort_order == 'DESC')
		uasort($l, function($a, $b) {
			if ($a['udate'] < $b['udate'])
				return 1;
			elseif ($a['udate'] > $b['udate'])
				return -1;
			else
				return 0;
		});
	else
		uasort($l, function($a, $b) {
			if ($a['udate'] > $b['udate'])
				return 1;
			elseif ($a['udate'] < $b['udate'])
				return -1;
			else
				return 0;
		});
}

foreach($filters as $i=>$j) {
	if (is_array($j))
		continue;
	$filters_url .= '&filters['.rawurlencode($i).']='.rawurlencode($j);
}
foreach($cols as $i=>$j) {
	$filters_url .= '&cols['.rawurlencode($i).']='.rawurlencode($j);
}

//$filters_url .= '&sort='.$sort.'&sort_order='.$sort_order;

echo '<table id="crm_list" class="noborder centpercent">';
echo '<tr class="liste_titre">';
if (in_array('urgent', $cols))
	echo '<th rowspan="2" class="crm-urgent-col" title="À traiter en urgence">!</th>';
echo '<th rowspan="2">'.col_sort_aff('nom').'</th>';
//echo '<th rowspan="2">Nom alternatif</th>';
echo '<th rowspan="2">Groupe</th>';
if (in_array('ville', $cols))
	echo '<th rowspan="2">Ville</th>';
if (in_array('commercial', $cols))
	echo '<th rowspan="2">Commercial</th>';
if (in_array('code_client', $cols))
	echo '<th rowspan="2">Code client</th>';
if (in_array('catalogue_pro', $cols))
	echo '<th rowspan="2">Catalogue pro</th>';
echo '<th width="100">Dernière Action/Modif</th>';
echo '<th width="100" colspan="'.(in_array('cmd_tot_mt', $cols) ?'2' :'1').'">Total commandes</th>';
echo '<th colspan="2">Dernière commande</th>';
echo '<th colspan="4">Relances</th>';
if (in_array('crm_note', $cols))
	echo '<th rowspan="2">Note de suivi</th>';
echo '</tr>';

echo '<tr class="liste_titre">';
echo '<th>'.col_sort_aff('client_lastupdate').'</th>';
echo '<th>'.col_sort_aff('commandes_nb').'</th>';
if (in_array('cmd_tot_mt', $cols))
	echo '<th>Montant</th>';
echo '<th>'.col_sort_aff('commande_last').'</th>';
echo '<th>Montant</th>';
echo '<th>Nombre</th>';
echo '<th>'.col_sort_aff('relance_last').'</th>';
echo '<th>'.col_sort_aff('evenement_last').'</th>';
echo '<th>'.col_sort_aff('relance_next').'</th>';
echo '</tr>';

echo '<tr class="liste_titre">';
if (in_array('urgent', $cols))
	echo '<th>'.col_filter_aff('crm_urgent').'</th>';
echo '<th>'.col_filter_aff('client_nom').'</th>';
echo '<th>'.col_filter_aff('groupe_client_pro').'<br />'.col_filter_aff('groupe_client').'</th>';
if (in_array('ville', $cols))
	echo '<th>'.col_filter_aff('client_ville').'</th>';
if (in_array('commercial', $cols))
	echo '<th>'.col_filter_aff('commercial').'</th>';
if (in_array('code_client', $cols))
	echo '<th></th>';
if (in_array('catalogue_pro', $cols))
	echo '<th>'.col_filter_aff('catalogue_pro').'</th>';
echo '<th>'.col_filter_aff('client_lastupdate').'</th>';
echo '<th>'.col_filter_aff('prospect').'</th>';
if (in_array('cmd_tot_mt', $cols))
	echo '<th>'.col_filter_aff('cmd_tot_mt').'</th>';
echo '<th>'.col_filter_aff('commande_last').'</th>';
echo '<th>'.col_filter_aff('mt').'</th>';
echo '<th>'.col_filter_aff('nb').'</th>';
echo '<th>'.col_filter_aff('relance_not_before_cmd').'<br />'.col_filter_aff('relance_histo_user').'</th>';
echo '<th></th>';
echo '<th>'.col_filter_aff('relance_next').'</th>';
if (in_array('crm_note', $cols))
	echo '<th></th>';
echo '</tr>';


foreach($l as $row) {
	echo '<tr class="oddeven">';
	if (in_array('urgent', $cols)) {
		$urgent = !empty($row['crm_urgent']);
		echo '<td class="crm-urgent-col"><button type="button" class="crm-urgent'.($urgent ? ' on' : '')
			.'" data-socid="'.((int) $row['rowid']).'" data-value="'.($urgent ? 1 : 0).'"'
			.' title="'.($urgent ? 'Retirer l\'urgence' : 'Marquer à traiter en urgence').'">'
			.($urgent ? '!' : '·').'</button></td>';
	}
	echo '<td><a href="/comm/card.php?socid='.((int) $row['rowid']).'">'.h($row['nom']).'</a>'.(!empty($row['name_alias']) ?' ('.h($row['name_alias']).')' :'').'</td>';
	echo '<td>'.(!empty($row['p_group']) ?h($p_groups[$row['p_group']]['label'] ?? '') :'').'</td>';
	if (in_array('ville', $cols))
		echo '<td>'.h($row['town']).'</td>';
	if (in_array('commercial', $cols))
		echo '<td>'.h($row['commercial'] ?? '').'</td>';
	if (in_array('code_client', $cols))
		echo '<td>'.h($row['code_client'] ?? '').'</td>';
	if (in_array('catalogue_pro', $cols)) {
		// 1 catalogue -> lien cliquable ; plusieurs -> message (pas d'URL, on ne
		// choisit pas a la place de l'utilisateur) ; aucun -> case vide.
		if (!empty($row['p_catalogpro_url']))
			$cat = '<a href="'.h($row['p_catalogpro_url']).'" target="_blank" rel="noopener">'
				.h($row['p_catalogpro_info'] ?: 'Catalogue').'</a>';
		elseif (!empty($row['p_catalogpro']))
			$cat = h($row['p_catalogpro_info'] ?? '');
		else
			$cat = '';
		echo '<td>'.$cat.'</td>';
	}
	echo '<td class="center nowraponall">'.date_fromsql($row['udate']).'</td>';
	echo '<td class="cmd right">'.((int) ($row['tot_nb'] ?? 0)).'</td>';
	if (in_array('cmd_tot_mt', $cols))
		echo '<td class="cmd right nowraponall">'.(!empty($row['tot_mt']) ?price($row['tot_mt'], 0, $langs, 1, -1, -1, 'auto') :'').'</td>';
	echo '<td class="cmd nowraponall">'.(!empty($row['last_rowid']) ?'<a href="/commande/card.php?id='.((int) $row['last_rowid']).'">'.date_fromsql($row['c_last_date']).'</a>' :'').'</td>';
	echo '<td class="cmd right nowraponall">'.(!empty($row['last_mt']) ?price($row['last_mt'], 0, $langs, 1, -1, -1, 'auto') :'').'</td>';

	// Bloc Relances : nombre | dernière relance (contacttracking) |
	// dernier événement réalisé (agenda, RM2779) | prochaine relance (agenda).
	echo '<td class="rel right">'.((int) ($row['k_nb'] ?? 0)).'</td>';
	echo '<td class="rel right nowraponall">'.(!empty($row['k_last_rowid'])
		?'<span class="k-user" title="'.h(trim($row['k_last_user_firstname'].' '.$row['k_last_user_lastname'])).'">'.initials($row['k_last_user_firstname'] ?? '', $row['k_last_user_lastname'] ?? '').'</span>'
			.' <span class="k-contact" title="'.h($row['k_last_contact_type']).'">'.h(mb_substr((string) ($row['k_last_contact_type'] ?? ''), 0, 4)).'</span>'
			.' <a href="/custom/contacttracking/contacttracking_card.php?id='.((int) $row['k_last_rowid']).'">'.date_fromsql($row['k_last_date']).'</a>'
		:'').'</td>';
	echo '<td class="rel right nowraponall">'.(!empty($row['a_past_last_rowid'])
		?'<span class="a-user" title="'.h(trim($row['a_past_last_user_firstname'].' '.$row['a_past_last_user_lastname'])).'">'.initials($row['a_past_last_user_firstname'] ?? '', $row['a_past_last_user_lastname'] ?? '').'</span>'
			.' <a href="/comm/action/card.php?id='.((int) $row['a_past_last_rowid']).'" title="'.h(trim(($row['a_past_last_type'] ?? '').' — '.($row['a_past_last_label'] ?? ''), ' —')).'">'.date_fromsql($row['a_past_last_date']).'</a>'
		:'').'</td>';
	echo '<td class="rel right nowraponall">'.(!empty($row['a_last_rowid'])
		?'<span class="a-user" title="'.h(trim($row['a_last_user_firstname'].' '.$row['a_last_user_lastname'])).'">'.initials($row['a_last_user_firstname'] ?? '', $row['a_last_user_lastname'] ?? '').'</span>'
			.' <a href="/comm/action/card.php?id='.((int) $row['a_last_rowid']).'">'.date_fromsql($row['a_last_date']).'</a>'
		:'').'</td>';

	echo '<td class="right nowraponall"><a href="/custom/contacttracking/contacttracking_list.php?socid='.((int) $row['rowid']).'&element_type=thirdparty">Histo</a> | <a href="/comm/action/list.php?sortfield=a.id&sortorder=desc&begin=&contextpage=actioncommlist&search_filtert=-1&search_socid='.((int) $row['rowid']).'&mode=show_month&">Evé</a></td>';
	echo '<td class="right nowraponall"><a href="/comm/action/card.php?action=create&originid='.((int) $row['rowid']).'&socid='.((int) $row['rowid']).'&backtopage=%2Fcustom%2Fmmicrm%2Fprospects.php&datep='.date('Ymd000000', time()+86400).'&label=Rappeler prospect">Agenda</a></td>';
	if (in_array('crm_note', $cols)) {
		echo '<td class="crm-note-col"><textarea class="crm-note" rows="1" data-socid="'.((int) $row['rowid'])
			.'" placeholder="…">'.h($row['crm_note'] ?? '').'</textarea></td>';
	}
	echo '</tr>';
}
echo '</table>';

echo '</form>';

// Auto-submit des combos utilisateur natifs (Form::select_dolusers) au changement
echo '<script>jQuery(function($){ $(".crmuserfilter").on("change", function(){ this.form.submit(); }); });</script>';

// Suivi commercial édité en place (RM2858). Écriture optimiste : l'affichage
// bascule tout de suite, et REVIENT en arrière si le serveur refuse — un échec
// silencieux ferait croire à l'utilisateur que sa note est enregistrée.
$ajax_url = dol_buildpath('/mmicrm/ajax/soc_field.php', 1);
?>
<script>
jQuery(function($) {
	var url = <?php echo json_encode($ajax_url); ?>;

	function save(field, socid, value) {
		return $.post(url, { field: field, socid: socid, value: value, token: <?php echo json_encode(newToken()); ?> });
	}

	$('#crm_list').on('click', 'button.crm-urgent', function() {
		var $b = $(this);
		if ($b.hasClass('pending')) return;
		var was = parseInt($b.attr('data-value'), 10) ? 1 : 0;
		var now = was ? 0 : 1;
		$b.addClass('pending').toggleClass('on', now === 1).text(now ? '!' : '\u00b7');
		save('crm_urgent', $b.attr('data-socid'), now)
			.done(function() {
				$b.attr('data-value', now)
				  .attr('title', now ? "Retirer l'urgence" : 'Marquer à traiter en urgence');
			})
			.fail(function(xhr) {
				// Retour à l'état d'avant : le serveur n'a rien écrit.
				$b.toggleClass('on', was === 1).text(was ? '!' : '\u00b7');
				alert((xhr.responseJSON && xhr.responseJSON.error) || "L'urgence n'a pas pu être enregistrée.");
			})
			.always(function() { $b.removeClass('pending'); });
	});

	// Enregistrement à la sortie du champ, et seulement si le texte a changé :
	// un simple passage au clavier ne doit pas déclencher d'écriture.
	$('#crm_list').on('focus', 'textarea.crm-note', function() {
		$(this).data('initial', this.value).removeClass('saved failed');
	});
	$('#crm_list').on('blur', 'textarea.crm-note', function() {
		var $t = $(this);
		if ($t.data('initial') === this.value) return;
		var value = this.value;
		save('crm_note', $t.attr('data-socid'), value)
			.done(function(r) {
				// Le serveur peut avoir tronqué ou nettoyé : on réaffiche ce qu'il a gardé.
				if (r && typeof r.value === 'string') $t.val(r.value);
				$t.data('initial', $t.val()).addClass('saved');
			})
			.fail(function(xhr) {
				$t.addClass('failed');
				alert((xhr.responseJSON && xhr.responseJSON.error) || "La note n'a pas pu être enregistrée.");
			});
	});
});
</script>
<?php
