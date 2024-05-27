<?php

require_once DOL_DOCUMENT_ROOT."/core/lib/company.lib.php";
require_once DOL_DOCUMENT_ROOT."/societe/class/societe.class.php";
require_once DOL_DOCUMENT_ROOT."/contact/class/contact.class.php";
require_once DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php";
require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT."/core/class/CSMSFile.class.php";
dol_include_once("/ovh/class/ovhsms.class.php");

class mmi_crm_relance extends mmi_generic_1_0
{
	const MOD_NAME = 'mmicrm';

	// Expération dans moins de ... jours
	const DAYS_MIN = 3;
	// Expération dans plus de ... jours
	const DAYS_MAX = 3;
	// Pas de relance depuis ... jouts
	const DAYS_LASTMAIL = 10;

	// Max envoi simultané
	const RELANCE_MAX = 5;

	// Qualifier le projet par les catégorie des produits du devis
	const PROJECT_BY_CAT = false;
	const PROJECT_DEFAULT_NAME = 'de matériel de piscine';

	// Templates
	const PROPAL_RELANCE_EMAIL_TPL = 1;
	const PROPAL_RELANCE_SMS_TPL = 1;

	public static function propal_relance_valid_between_days($options=[], $days_max=NULL, $days_min=NULL)
	{
		global $user, $db;

		if (is_null($days_max))
			$days_max = static::DAYS_MAX;
		if (is_null($days_min))
			$days_min = static::DAYS_MIN;
		//var_dump($days);

		// Devis ouverts,
		// échus dans un intervalle entre $days_min et $days_max jours,
		// sans relance depuis DAYS_LASTMAIL jours
		// sans commande associée
		//, , DATEDIFF(d.fin_validite, NOW()) AS datediff
		$sql = 'SELECT DISTINCT d.rowid
			FROM '.MAIN_DB_PREFIX.'propal d
			INNER JOIN '.MAIN_DB_PREFIX.'societe s
				ON s.rowid=d.fk_soc
			LEFT JOIN '.MAIN_DB_PREFIX.'actioncomm am
				ON am.code = "AC_PROPAL_SENTBYMAIL" AND am.elementtype="propal" AND am.fk_element=d.rowid
				AND DATEDIFF(am.datec, NOW()) >= -'.static::DAYS_LASTMAIL.'
			LEFT JOIN '.MAIN_DB_PREFIX.'element_element dc
				ON dc.sourcetype="propal" AND dc.fk_source=d.rowid AND dc.targettype="commande"
			WHERE d.fk_statut=1
				AND DATEDIFF(d.fin_validite, NOW()) >= '.$days_min.' AND DATEDIFF(d.fin_validite, NOW()) <= '.$days_max.'
				AND am.id IS NULL
				AND dc.rowid IS NULL
			GROUP BY d.rowid';
		echo '<pre>'.$sql.'</pre>';
		// Count

		// Group send
		$q = $db->query($sql);
		$nb_total = $q->num_rows;
		//var_dump($nb_total); die();
		$nb = 0;
		while(list($id)=$q->fetch_row()) {
			//var_dump($id);
			$object = new Propal($db);
			$object->fetch($id);
			$object->fetch_thirdparty();
			// c_email_templates
			if (!empty($options['email']))
				static::object_sendmail_template($user, $object, static::PROPAL_RELANCE_EMAIL_TPL, $options);
			if (!empty($options['sms']))
				static::object_sendsms($user, $object, static::PROPAL_RELANCE_SMS_TPL, $options);

			$nb++;
			if ($nb>=static::RELANCE_MAX)
				break;
		}
	}

	public static function object_sendmail_template($user, $object, $c_email_template_id, $options=[])
	{
		global $conf, $langs, $db, $soc, $hookmanager;

		$recap = !empty($options['recap']);
		$send = !empty($options['email_send']);

		$massaction = 'confirm_presend';
		$_POST['oneemailperrecipient'] = 'on';
		$_POST['addmaindocfile'] = 'on';
		$_POST['sendmail'] = 'on';
	
		$object_type = get_class($object);
		$objectclass = $object_type;
		$type = strtolower($object_type);

		if (static::PROJECT_BY_CAT) {
			// Catégories de produit dans leslignes
			$cats = [];
			foreach($object->lines as $objectdet) {
				if (!empty($objectdet->fk_product)) {
					$product = new Product($db);
					$product->fetch($objectdet->fk_product);
					//var_dump($product->array_options);
					if (!empty($product->array_options['options_fk_categorie'])) {
						$cat = new Categorie($db);
						$cat->fetch($product->array_options['options_fk_categorie']);
						var_dump($cat);
						// BOucle parents pour choper le premier qu'on veut afficher
						while (empty($cat->array_options['options_relance_disp']) && !empty($cat->fk_parent)) {
							$cat->fetch($cat->fk_parent);
							var_dump($cat);
						}
						if (! empty($cat->array_options['options_relance_disp'])) {
							$cats[] = $cat->label;
						}
					}
				}
				//var_dump();
			}
			$cats = array_unique($cats);
			//var_dump($cats); die();
			if (!empty($cats)) {
				$projet = ' de '.implode(', ', $cats);
			}
		}
		if (empty($projet)) {
			$projet = ' '.static::PROJECT_DEFAULT_NAME;
		}
		
		// Client
		$thirdparty = $object->thirdparty;
		//var_dump($thirdparty); die();
		//var_dump($thirdparty->nom); die();

		// Commercial
		$commerciaux = $thirdparty->getSalesRepresentatives($user);
		//var_dump($commerciaux);
		if (!empty($commerciaux)) {
			$commercial = array_pop($commerciaux);
			//var_dump($commercial); die();
		}
		else {
			$commercial = [];
			$commercial['firstname'] = 'Pisceen';
			$commercial['lastname'] = '';
			$commercial['email'] = 'contact@dercya.com';
			$commercial['office_phone'] = '04 69 11 00 79';
		}
		//var_dump($commercial);

		// Clé de paiement
		$securekey = dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN').$type.$object->ref, 2);
		$payment_url = 'https://erp.dercya.com/public/payment/newpayment.php?source=propal&ref='.$object->ref.'&securekey='.$securekey;

		// Construction email
		$_POST['receiver'] = $thirdparty->nom.' <'.$thirdparty->email.'>';
		//var_dump($_POST['receiver']); die();
		if (!empty($commercial['id'])) {
			$_POST['fromtype'] = 'user';
			$user = new User($db);
			$user->fetch($commercial['id']);
		}
		else {
			$_POST['fromtype'] = 'company';
		}
		$_POST['subject'] = 'J-'.static::DAYS_MAX.' pour profiter de votre offre';
		$_POST['message'] = 'Bonjour'.(true ?' '.$thirdparty->nom :'').",\r\n\r\n"
			.'Faisant suite à nos échanges et l’envoi de votre devis N°'.$object->ref.' concernant votre projet d’achat de matériel pour votre piscine, je vous rappelle que ma propostion commerciale expire dans 72H00.'."\r\n\r\n"
			//.'Faisant suite à nos échanges et l’envoi de votre devis N°'.$object->ref.' concernant votre projet'.$projet.', je vous rappelle que ma propostion commerciale expire dans 72H00.'."\r\n\r\n"
			.'Si vous souhaitez profiter de mon offre, je vous invite à cliquer sur le lien suivant pour effectuer votre règlement sécurisé :'."\r\n"
			.$payment_url."\r\n\r\n"
			.'Restant à votre écoute, je vous souhaite une excellente journée.'."\r\n\r\n"
			.'Bien cordialement'."\r\n"
			.'Best regards'."\r\n"."\r\n"
			.$commercial['firstname'].($commercial['lastname'] ?' '.$commercial['lastname'] :'')."\r\n"
			.$commercial['email']."\r\n"
			.'TEL: '.$commercial['office_phone']."\r\n"."\r\n"
			.'https://pisceen.com'."\r\n";
		
		if($recap) {
			echo '<hr />';
			echo '<p><i>EMAIL</i></p>';
			echo '<p>FROM: <strong>'.(!empty($commercial['email_sender_name']) ?$commercial['email_sender_name'] :$commercial['firstname'].' '.$commercial['lastname']).' &lt;'.$commercial['email'].'&gt;'.'</strong></p>';
			echo '<p>TO: <strong>'.htmlspecialchars($_POST['receiver']).'</strong></p>';
			echo '<p>SUBJECT: <strong>'.$_POST['subject'].'</strong></p>';
			echo '<pre>'.$_POST['message'].'</pre>';
			return;
		}
		if ($send) {
			$toselect = [$object->id];
			$uploaddir = DOL_DOCUMENT_ROOT.'/../documents/propale';
			require DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
		}
	}

	public static function object_sendsms($user, $object, $sms_templace_id, $options=[])
	{
		global $db, $langs;

		$recap = !empty($options['recap']);
		$send = !empty($options['email_send']);

		$error=0;

		// Client
		$thirdparty = $object->thirdparty;
		$socid = $object->thirdparty->id;

		// Commercial
		$commerciaux = $thirdparty->getSalesRepresentatives($user);
		//var_dump($commerciaux);
		if (!empty($commerciaux)) {
			$commercial = array_pop($commerciaux);
			//var_dump($commercial); die();
		}
		else {
			$commercial = [];
			$commercial['firstname'] = 'Pisceen';
			$commercial['lastname'] = '';
			$commercial['email'] = 'contact@dercya.com';
			$commercial['office_phone'] = '04 69 11 00 79';
		}

		$smsfrom	= 'PISCEEN';
		$receiver   = 'thirdparty';
		$sendto		= $thirdparty->phone;
		$deliveryreceipt= 0;
		$deferred   = 0;
		$priority   = 2; // low
		$class      = 1; // Type: Standard
		$errors_to  = '';
		$nostop		= 1; // A voir c'est de la relance il faut qu'ils puissent stopper... Par ailleurs, comment on a l'info stop concrètement ??
		
		$object_type = get_class($object);
		$type = strtolower($object_type);
		$securekey = dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN').$type.$object->ref, 2);
		$payment_url = $GLOBALS['dolibarr_main_url_root'].'/public/payment/newpayment.php?source=propal&ref='.$object->ref.'&securekey='.$securekey;

		if (getDolGlobalString('MMICRM_SHLINK_SCRIPT')) {
			$shlink_command = '/usr/bin/php8.2 -f '.DOL_DOCUMENT_ROOT.'/custom/mmicrm/scripts/shlink.php "'.$payment_url.'"'; // 2>&1
			//var_dump($shlink_command);
			$shorturl = exec($shlink_command, $shlink_output, $shlink_result);
			//var_dump($shlink_result, $shlink_output);
			//var_dump($shorturl);
			$shorturl = str_replace('http://', 'https://', $shorturl);
		}
		else {
			dol_include_once("/mmicrm/class/mmi_shlink.class.php");
			$shortlink = mmi_shlink::generate($payment_url);
			$shorturl = str_replace('http://', 'https://', $shortlink->shortUrl);
		}
		$body		= 'Bonjour 👋, c’est '.(!empty($commercial['email_sender_name']) ?$commercial['email_sender_name'] :(!empty($commercial['firstname']) ?$commercial['firstname'].' de pisceen.com' :'pisceen.com')).''."\r\n".'Je fais suite à nos échanges et vous rappelle que notre offre est encore valable 72 heures '.$shorturl."\r\n".'N’hésitez pas à me rappeler '.$commercial['office_phone']."\r\n".'Belle journée ☀️';
		//var_dump($body); die();
		
		if ((empty($sendto) || ! str_replace('+', '', $sendto)) && (! empty($receiver) && $receiver != '-1')) {
			$sendto=$thirdparty->contact_get_property($receiver, 'mobile');
		}

		// Test param
		if (empty($body)) {
			setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentities("Message")), 'errors');
			$error++;
		}
		if (empty($smsfrom) || ! str_replace('+', '', $smsfrom)) {
			setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentities("SmsFrom")), 'errors');
			$error++;
		}
		if ((empty($sendto) || ! str_replace('+', '', $sendto)) && (empty($receiver) || $receiver == '-1')) {
			setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentities("SmsTo")), 'errors');
			$error++;
		}

		if (! $error) {
			/*$actionmsg2 = $langs->transnoentities('SMSSentBy').' '.$smsfrom;
			if ($body)
			{
				$actionmsg = $langs->transnoentities('SMSFrom').': '.dol_escape_htmltag($smsfrom);
				$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('To').': '.dol_escape_htmltag($sendto));
				$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody').":");
				$actionmsg = dol_concatdesc($actionmsg, $body);
			}

			$triggersendname = 'SMS_SENT';*/

			// Make substitutions into message
			$substitutionarrayfortest=array();
			complete_substitutions_array($substitutionarrayfortest, $langs);
			$body=make_substitutions($body, $substitutionarrayfortest);

			if($recap) {
				echo '<hr />';
				echo '<p><i>SMS</i></p>';
				echo '<p><strong>'.$sendto.'</strong></p>';
				echo '<pre>'.$body.'</pre>';
				return;
			}
			if ($send) {
				//if (empty($sendcontext)) $sendcontext = 'standard';
				$smsfile = new CSMSFile($sendto, $smsfrom, $body, $deliveryreceipt, $deferred, $priority, $class);  // This define OvhSms->login, pass, session and account

				$smsfile->nostop = $nostop;
				$smsfile->socid = $socid;
				$smsfile->contactid = 0;
				$smsfile->contact_id = 0;
				$smsfile->fk_project = 0;

				// Send the SMS
				$result=$smsfile->sendfile(); // This send SMS. It also includes run of triggers 'SENTBYSMS'.

				if ($result > 0) {
					$object = $thirdparty;

					setEventMessages($langs->trans("SmsSuccessfulySent", $smsfrom, $sendto), null);
				} else {
					setEventMessages($langs->trans("ResultKo").' (sms from'.$smsfrom.' to '.$sendto.')<br>'.$smsfile->error, null, 'errors');
				}
			}
		}
	}
}

mmi_crm_relance::__init();
