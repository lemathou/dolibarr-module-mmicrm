<?php
/* Copyright (C) 2026 MMI Mathieu Moulin <contact@iprospective.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    mmicrm/class/mmi_crm_template.class.php
 * \brief   Substitution des variables de gabarit {$cle} (sans dépendance Dolibarr).
 */

class mmi_crm_template
{
	/**
	 * Remplace les variables {$cle} d'un texte par leurs valeurs.
	 *
	 * @param array  $map    Variables (cle => valeur)
	 * @param string $string Texte source
	 * @return string
	 */
	public static function map($map, $string)
	{
		$map_from = $map_to = [];
		foreach ($map as $key => $value) {
			$map_from[] = '{$'.$key.'}';
			$map_to[] = $value;
		}
		return str_replace($map_from, $map_to, $string);
	}
}
