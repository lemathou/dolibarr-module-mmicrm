<?php

dol_include_once('custom/mmicommon/class/mmi_actions.class.php');
dol_include_once('custom/mmicrm/class/mmi_crm_relance.class.php');

class ActionsMMICRM extends MMI_Actions_1_0
{
	const MOD_NAME = 'mmicrm';

	public function sendMail($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
        $this->resprints = '';

        //var_dump($parameters, $object->trackid); die();
		// If propal sent by email
		if ($this->in_context($parameters, 'mail') && preg_match('/pro([0-9]+)/', $object->trackid, $matches)) {
			
			$id = $matches[1];
			$propal = new Propal($this->db);
			$propal->fetch($id);
			$object = $propal; // Set object to propal for further processing
			$object->fetch_thirdparty();
			//var_dump($id, $object); die();

			// Send SMS after email
			if (getDolGlobalInt('MMI_CRM_SMS_AFTER_MAIL_AUTO')) {
				// Par défaut
				$options = [
					'recap' => 1,
					'email_send' => 1,
					'sms_message' => getDolGlobalString('MMI_CRM_SMS_AFTER_MAIL_MSG'),
					'nopaylink' => 1, // No payment link in SMS
				];
				$ret = mmi_crm_relance::object_sendsms($user, $object, mmi_crm_relance::PROPAL_RELANCE_SMS_TPL, $options);
				//var_dump($ret); die();
			}
			// Create actioncomm after email
			if (getDolGlobalInt('MMI_CRM_RELANCE_AFTER_MAIL_AUTO')) {
				$delai = getDolGlobalInt('MMI_CRM_RELANCE_AFTER_MAIL_AUTO_DELAI');
				$ret = mmi_crm_relance::object_relanceauto_agenda($user, $object, ['delai'=>$delai]);
			}
		}

		if (!$error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}
}
