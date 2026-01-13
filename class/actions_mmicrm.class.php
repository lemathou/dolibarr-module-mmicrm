<?php

dol_include_once('custom/mmicommon/class/mmi_actions.class.php');
dol_include_once('custom/mmicrm/class/mmi_crm_relance.class.php');
dol_include_once('custom/mmicrm/class/mmi_crm_loyalty.class.php');

class ActionsMMICRM extends MMI_Actions_1_0
{
	const MOD_NAME = 'mmicrm';

	public function sendMail($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
        $this->resprints = '';

        //var_dump($parameters, $object); die();

		// If propal sent by email to customer (and not internal user !!)
		if ($this->in_context($parameters, 'mail')) {

			if (preg_match('/pro([0-9]+)/', $object->trackid, $matches)) {
				// Check if email sent to customer
				$addr_to = CMailFile::getValidAddress($object->addr_to, 2);
				
				$id = $matches[1];
				$propal = new Propal($this->db);
				$propal->fetch($id);
				$propal->fetch_thirdparty();
				$thirdparty = $propal->thirdparty;
				$cu_emails = [$thirdparty->email];
				foreach($thirdparty->contact_property_array('email') as $email) {
					if (!in_array($email, $cu_emails))
						$cu_emails[] = CMailFile::getValidAddress($email, 2);
				}
				if (in_array($addr_to, $cu_emails)) {
					// Send SMS after email
					if (getDolGlobalInt('MMI_CRM_SMS_AFTER_MAIL_AUTO') && empty($object->sendoptions['email_sms_noauto'])) {
						// Par défaut
						$options = [
							'recap' => 1,
							'email_send' => 1,
							'sms_message' => getDolGlobalString('MMI_CRM_SMS_AFTER_MAIL_MSG'),
							'nopaylink' => 1, // No payment link in SMS
						];
						$ret = mmi_crm_relance::object_sendsms($user, $propal, mmi_crm_relance::PROPAL_RELANCE_SMS_TPL, $options);
						//var_dump($ret); die();
					}
					// Create actioncomm after email
					if (getDolGlobalInt('MMI_CRM_RELANCE_AFTER_MAIL_AUTO') && empty($object->sendoptions['email_rdv_noauto'])) {
						$delai = getDolGlobalInt('MMI_CRM_RELANCE_AFTER_MAIL_AUTO_DELAI');
						$ret = mmi_crm_relance::object_relanceauto_agenda($user, $propal, ['delai'=>$delai]);
					}
				}
			}
		}

		if (!$error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	public function formAddObjectLine($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		$notices = []; // Notices
        $this->resprints = '';

		if ($this->in_context($parameters, 'ordercard')) {
			mmi_crm_loyalty::customer_order_discount_loyalty($object->socid);
		}

		if (!$error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}
}
