# MMICRM — CRM MMI : fidélité, relances, campagnes produits, commentaires de factures/commandes et liens courts.

> Module Dolibarr **MMICRM** (version 1.2.1), branche `20.0`.

## Fonctionnalités

### Fonctionnalités principales

- Relances clients (`mmi_crm_relance`) et fidélité (`mmi_crm_loyalty`).
- Campagnes produits (`product_campagne.php`) et templates de messages (`mmi_crm_template`).
- Commentaires de factures (`invoice_comments.php`) et de commandes (`order_comments.php`).
- Liens courts (`mmi_shlink`) et pages clients/commandes (`clients.php`, `orders.php`).

### Écrans et menus

- **CRM** — `/custom/mmicrm/index.php` (menu companies, droit `$user->rights->mmicrm->prospects->user`)
- **Clients et prospects** — `/custom/mmicrm/prospects.php` (menu companies, droit `$user->rights->mmicrm->prospects->user`)
- **MMICRMRelances** — `/custom/mmicrm/relances.php` (menu companies, droit `$user->rights->mmicrm->prospects->user`)

### Pages

- `admin/about.php`
- `admin/setup.php`
- `clients.php`
- `invoice_comments.php`
- `order_comments.php`
- `orders.php`
- `product_campagne.php`
- `product_comments.php`
- `propal_comments.php`
- `prospects.php`
- `relances.php`
- `supplier_invoice_comments.php`
- `supplier_order_comments.php`

### Permissions

- `mmicrm.prospects.user` — Suivi prospects

### Configuration

| Constante | Type | Rôle |
|---|---|---|
| `DOCUMENT_DRAFT_MASSACTION_CAN_SEND` | yesno | Autoriser l'envoi de mail en masse sur l'ensemble des documents au statut brouillon |
| `PROPAL_DRAFT_MASSACTION_CAN_SEND` | yesno | Autoriser l'envoi de mail en masse sur les devis au statut brouillon |
| `ORDER_DRAFT_MASSACTION_CAN_SEND` | yesno | Autoriser l'envoi de mail en masse sur les commandes au statut brouillon |
| `INVOICE_DRAFT_MASSACTION_CAN_SEND` | yesno | Autoriser l'envoi de mail en masse sur les factures au statut brouillon |
| `MMICRM_PROPAL_HIDE_SIGN` | yesno | Masquer la possibilité de signer la propale par le client |
| `MMICRM_USER_MAILFROM_NAME` | yesno | Champ personnalisé par utilisateur pour le nom de l'expéditeur des emails |
| `MMI_CRM_TOPIC_MAIN_ACTIVE` | yesno | Champ personnalisé dans les Propales, Commandes et factures pour qualifier la Demande principale du client |
| `MMICRM_RELANCES_SEP` | separator | Relances email et SMS |
| `MMICRM_RELANCES` | yesno | Activer relances par email/SMS |
| `MMICRM_EMAIL_TEMPLATE` | string | Template utilisé pour les relances email |
| `MMICRM_SMS_CLEAN_UNCIODE` | yesno | Nettoyer les caractères unicode interdit dans les envois de SMS |
| `MMICRM_SHLINK` | separator | Raccourcisseur URL Shlink |
| `MMICRM_SHLINK_URL` | string | Adresse serveur Shlink |
| `MMICRM_SHLINK_KEY` | securekey | Clé API serveur Shlink |
| `MMICRM_SHLINK_SCRIPT` | yesno | Execution de l'API Shlink via une commande en PHP 8.2 |
| `MMI_CRM_AFTER_MAIL_AUTO` | separator | Automatisations après envoi d'un email de propale |
| `MMI_CRM_SMS_AFTER_MAIL_AUTO` | yesno | Envoi automatique de SMS après l'envoi d'un email de propale |
| `MMI_CRM_SMS_AFTER_MAIL_MSG` | textarea | Message du SMS envoyé après l'envoi d'un email de propale |
| `MMI_CRM_RELANCE_AFTER_MAIL_AUTO` | yesno | Création relance auto après l'envoi d'un email de propale |
| `MMI_CRM_RELANCE_AFTER_MAIL_AUTO_ONLYIFNOT` | yesno | Création relance auto uniquement si pas de relance déjà créée |
| `MMI_CRM_RELANCE_AFTER_MAIL_AUTO_DELAI` | int | Délai (jours) de création relance auto |
| `MMI_CRM_ORDER` | separator | Options des commandes |
| `MMI_CRM_ORDER_DISCOUNT_LOYALTY_ACTIVE` | yesno | Activer les remises fidélité sur les commandes |
| `MMI_CRM_ORDER_DISCOUNT_LOYALTY_NB` | int | Nombre de commandes pour bénéfier d'une remise fidélité |
| `MMI_CRM_ORDER_DISCOUNT_LOYALTY_RATE` | decimal | Taux de la remise fidélité (pourcentage) |

### Hooks

- Contextes : `ordercard`, `mail`
- Callbacks : `sendMail`, `formAddObjectLine`

### Classes et composants

- `class/actions_mmicrm.class.php`
- `class/mmi_crm_loyalty.class.php`
- `class/mmi_crm_relance.class.php`
- `class/mmi_crm_template.class.php`
- `class/mmi_shlink.class.php`
- `lib/mmicrm.lib.php`

## Prérequis

- Dolibarr >= 11.0 (branche `20.0` : Dolibarr 20.0).
- Modules MMI : `mmicommon`. **`mmicommon` doit être activé en premier.**

## Installation

1. Copier le module dans `htdocs/custom/mmicrm` :

   ```sh
   cd htdocs/custom
   git clone <dépôt> mmicrm
   ```

2. Dans Dolibarr : **Configuration → Modules**, activer d'abord `mmicommon`, puis ce module.
3. Vérifier les permissions dans la fiche des utilisateurs concernés.

## Licence

GPLv3 ou ultérieure — voir `COPYING`. Documentation sous GFDL.

## Changelog

Voir [CHANGELOG.md](CHANGELOG.md) (évolutions datées).
