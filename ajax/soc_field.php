<?php
/* Copyright (C) 2026 Mathieu Moulin            <contact@iprospective.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       mmicrm/ajax/soc_field.php
 *  \brief      Écriture d'un champ de suivi CRM sur un tiers, depuis la liste
 *              CRM pro (RM2858).
 *
 *  Seuls deux extrafields sont acceptés — la liste blanche ci-dessous. Le nom du
 *  champ arrive du navigateur : sans elle, ce point d'entrée écrirait n'importe
 *  quel extrafield de societe.
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

/**
 * Termine la requête sur une réponse JSON.
 *
 * @param  int   $http    Code HTTP
 * @param  array $payload Corps de la réponse
 * @return void
 */
function mmicrm_json($http, $payload)
{
	// header() plutôt que top_httphead() : celle-ci vit dans main.inc.php et son
	// comportement dépend des constantes NOREQUIRE* posées plus haut.
	header('Content-Type: application/json; charset=UTF-8');
	http_response_code($http);
	echo json_encode($payload);
	exit;
}

// Champs autorisés, et comment normaliser la valeur reçue.
$allowed = array(
	'crm_urgent' => 'bool',
	'crm_note'   => 'text',
);

$field = GETPOST('field', 'aZ09');
$socid = GETPOST('socid', 'int');

if (!isset($allowed[$field])) {
	mmicrm_json(400, array('ok' => false, 'error' => 'Champ non autorisé.'));
}
if ($socid <= 0) {
	mmicrm_json(400, array('ok' => false, 'error' => 'Tiers non précisé.'));
}

// Droits : écrire ici revient à écrire sur la fiche tiers.
if (empty($user->rights->societe->creer)) {
	mmicrm_json(403, array('ok' => false, 'error' => "Vous n'avez pas le droit de modifier ce tiers."));
}

$object = new Societe($db);
if ($object->fetch($socid) <= 0) {
	mmicrm_json(404, array('ok' => false, 'error' => 'Tiers introuvable.'));
}

if ($allowed[$field] === 'bool') {
	$value = GETPOST('value', 'int') ? 1 : 0;
} else {
	// 'restricthtml' laisserait passer du balisage ; la cellule est un textarea,
	// on veut du texte brut. La longueur est bornée pour ne pas transformer la
	// colonne en dépotoir.
	$value = trim(GETPOST('value', 'restricthtml'));
	// 2e argument à 0 : dol_string_nohtmltag() supprime les retours à la ligne par
	// défaut, ce qui écraserait une note saisie sur plusieurs lignes.
	$value = dol_string_nohtmltag($value, 0);
	if (dol_strlen($value) > 2000) {
		$value = dol_substr($value, 0, 2000);
	}
}

$object->array_options['options_'.$field] = $value;

$db->begin();
$result = $object->updateExtraField($field);
if ($result < 0) {
	$db->rollback();
	mmicrm_json(500, array('ok' => false, 'error' => $object->error ? $object->error : 'Échec de l\'enregistrement.'));
}
$db->commit();

mmicrm_json(200, array('ok' => true, 'field' => $field, 'socid' => $socid, 'value' => $value));
