<?php

dol_include_once("/mmicommon/class/mmi_generic.class.php");

class mmi_crm_loyalty extends mmi_generic_1_0
{
	const MOD_NAME = 'mmicrm';

	public static function customer_order_discount_loyalty($socid)
	{
		global $db;

		$active = getDolGlobalInt('MMI_CRM_ORDER_DISCOUNT_LOYALTY_ACTIVE');
		if (!$active) {
			return;
		}

		$seuil = getDolGlobalInt('MMI_CRM_ORDER_DISCOUNT_LOYALTY_NB');

		$sql = 'SELECT COUNT(*) AS nb FROM `'.MAIN_DB_PREFIX.'commande` AS c'
					.' WHERE c.fk_soc = '.$socid
					.' AND c.fk_statut IN (1,2,3)'; // Validated, shipped, billed
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			$nb = $obj->nb;
		} else {
			$nb = 0;
		}
		var_dump($sql, $obj, $nb);
		if ($nb%$seuil == 0 && $nb >= $seuil) {
			//setEventMessage($langs->transnoentities('OrderInValidatedStatusAlert',$urlList),'warnings');
			setEventMessage('ATTENTION - déjà '.$nb.' commandes ! Le client bénéficie d\'une REMISE FIDELITE de '.getDolGlobalInt('MMI_CRM_ORDER_DISCOUNT_LOYALTY_RATE').'%','warnings');
		}
	}
}

mmi_crm_loyalty::__init();
