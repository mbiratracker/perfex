# Module Ebilling + GPSWOX pour Perfex CRM

Module d'intégration complet permettant d'accepter des paiements via **Ebilling** (Mobile Money et cartes bancaires) et de synchroniser automatiquement les abonnements **GPSWOX** (plateforme de tracking GPS).

## 🎯 Fonctionnalités

✅ **Paiement multi-opérateurs**
- Airtel Money
- Moov Money
- VISA/MasterCard (Orabank)

✅ **Intégration GPSWOX**
- Activation automatique après paiement
- Renouvellement d'abonnement (30 jours par défaut)
- Mapping client Perfex ↔ utilisateur GPSWOX

✅ **Sécurité renforcée**
- Validation webhook avec secret partagé
- Logs détaillés de toutes les transactions
- Notifications admin par email

✅ **Support LAB et PRODUCTION**
- Configuration flexible par environnement
- URLs configurables (API & Portail)

## 📋 Prérequis

- **Perfex CRM** v2.9+ (CodeIgniter 3.x)
- **PHP** 7.4+
- **Extensions PHP** : curl, json, openssl
- **Compte Ebilling** (LAB ou PROD)
- **Compte GPSWOX** avec API Key

## 🚀 Installation

### 1. Téléchargement

Placer le dossier `ebilling_gpswox` dans :
```
/modules/ebilling_gpswox/
```

### 2. Activation

1. Aller dans **Modules** → **Ebilling + GPSWOX**
2. Cliquer sur **Activer**
3. Le module va automatiquement :
   - Créer la table `ebilling_transactions`
   - Créer le custom field `gpswox_user_id`
   - Créer le mode de paiement "Ebilling"

### 3. Configuration

Aller dans **Réglages** → **Ebilling + GPSWOX** et configurer :

#### Ebilling
- **Environnement** : LAB (test) ou PROD
- **Username** : Votre identifiant Ebilling
- **Shared Key** : Votre clé secrète Ebilling
- **API Base URL** :
  - LAB : `https://lab.billing-easy.net`
  - PROD : `https://stg.billing-easy.com`
- **Portail URL** :
  - LAB : `https://test.billing-easy.net/`
  - PROD : `https://staging.billing-easy.net/`
- **Webhook Secret** (optionnel) : Secret pour valider les callbacks

#### GPSWOX
- **Base URL** : `https://app.mbiratracker.com`
- **API Key** : Votre clé API GPSWOX

#### URL Webhook à configurer dans Ebilling
```
https://www.mbiratracker.com/espaceclient/ebilling/webhook
```
**Important** : Configurer cette URL dans votre compte Ebilling (LAB ou PROD)

### 4. Mapping clients

Pour chaque client Perfex :
1. Ouvrir la fiche client
2. Renseigner le champ **GPSWOX User ID** avec l'ID utilisateur GPSWOX correspondant
3. Sauvegarder

## 🔄 Flux de fonctionnement

```
┌─────────────┐
│   Client    │
│   Perfex    │
└──────┬──────┘
       │
       │ 1. Facture impayée
       │
       ▼
┌─────────────────┐
│  Paiement via   │
│    Ebilling     │
│ (Mobile/Carte)  │
└──────┬──────────┘
       │
       │ 2. Callback webhook
       │
       ▼
┌─────────────────┐
│  Perfex CRM     │
│  - Marque payé  │
│  - Appelle API  │
└──────┬──────────┘
       │
       │ 3. Activation service
       │
       ▼
┌─────────────────┐
│    GPSWOX       │
│  Renouvellement │
│    30 jours     │
└─────────────────┘
```

## 📁 Structure du module

```
ebilling_gpswox/
├── config/
│   └── routes.php                 # Routes personnalisées
├── controllers/
│   ├── Ebilling.php              # Création factures, redirection
│   └── Webhook.php               # Réception callbacks Ebilling
├── models/
│   ├── Ebilling_model.php        # API Ebilling (create_bill, ussd_push)
│   └── Gpswox_model.php          # API GPSWOX (renew_account)
├── helpers/
│   └── ebilling_helpers.php      # Fonctions utilitaires (logs, mapping)
├── views/
│   └── settings.php              # Page de configuration
├── tests/
│   ├── Ebilling_model_test.php   # Tests modèle Ebilling
│   ├── Gpswox_model_test.php     # Tests modèle GPSWOX
│   ├── Webhook_test.php          # Tests webhook
│   ├── bootstrap.php             # Bootstrap PHPUnit
│   └── README.md                 # Documentation tests
├── install.php                    # Migrations (tables, custom fields)
├── ebilling_gpswox.php           # Fichier principal (hooks, settings)
└── README.md                      # Ce fichier
```

## 🔌 API Endpoints

### Routes publiques

| Méthode | Route | Description |
|---------|-------|-------------|
| POST | `/ebilling/create/{invoice_id}` | Créer facture Ebilling et rediriger |
| POST | `/ebilling/ussd/{bill_id}` | Envoyer USSD Push |
| GET | `/ebilling/redirect/{bill_id}` | Redirection vers portail paiement |
| POST | `/ebilling/webhook` | Réception callback Ebilling |

### Paramètres USSD Push

```json
{
  "msisdn": "066123456",
  "system": "airtelmoney"  // ou "moovmoney4"
}
```

Réponse :
```json
{
  "status": true,
  "message": "Accepted"
}
```

### Webhook Ebilling

Payload attendu :
```json
{
  "reference": "INV-0001",
  "external_reference": "INV-0001",
  "amount": "5000",
  "status": "success",
  "transactionid": "TXN123456",
  "operator": "airtelmoney"
}
```

## 🔒 Sécurité

### Validation webhook

Le webhook vérifie :
1. **Secret partagé** (optionnel) : Header `X-Ebilling-Secret`
2. **Champs obligatoires** : `reference`, `status`
3. **Format payload** : JSON valide

### Gestion des erreurs

- Tous les appels API sont loggés dans `application/logs/`
- Format des logs : `[EBG] message {context}`
- Notifications email admin activables (erreurs critiques)

### Bonnes pratiques

- ✅ Utiliser HTTPS pour toutes les communications
- ✅ Ne jamais exposer `Shared Key` ou `API Key`
- ✅ Configurer un `Webhook Secret`
- ✅ Tester en LAB avant production
- ✅ Vérifier les logs régulièrement

## 🧪 Tests unitaires

Le module inclut des tests PHPUnit complets.

### Installation PHPUnit

```bash
composer require --dev phpunit/phpunit
composer require --dev mockery/mockery
```

### Exécution des tests

```bash
# Tous les tests
phpunit --bootstrap tests/bootstrap.php tests/

# Tests spécifiques
phpunit --bootstrap tests/bootstrap.php tests/Ebilling_model_test.php
phpunit --bootstrap tests/bootstrap.php tests/Webhook_test.php

# Avec couverture
phpunit --bootstrap tests/bootstrap.php --coverage-html coverage/ tests/
```

### Tests inclus

- ✅ Formation URLs (LAB/PROD)
- ✅ Validation opérateurs
- ✅ Gestion credentials manquants
- ✅ Validation payload webhook
- ✅ Sécurité webhook (secret)
- ✅ Renouvellement GPSWOX

Voir `tests/README.md` pour plus de détails.

## 📊 Base de données

### Table `ebilling_transactions`

| Champ | Type | Description |
|-------|------|-------------|
| id | BIGINT | ID auto-incrémenté |
| invoice_id | INT | ID facture Perfex |
| external_reference | VARCHAR(191) | Numéro facture Perfex |
| bill_id | VARCHAR(64) | ID facture Ebilling |
| amount | DECIMAL(12,2) | Montant |
| operator | VARCHAR(64) | Opérateur (airtelmoney, moovmoney4, etc.) |
| status | VARCHAR(50) | pending, paid, failed |
| callback_payload | LONGTEXT | Payload JSON complet |
| created_at | DATETIME | Date création |
| updated_at | DATETIME | Date dernière mise à jour |

### Custom field `gpswox_user_id`

- **Slug** : `gpswox_user_id`
- **Type** : input
- **Visible** : Uniquement dans l'admin
- **Usage** : Mapping client Perfex → utilisateur GPSWOX

## 🐛 Dépannage

### Webhook ne reçoit pas les callbacks

1. Vérifier que l'URL webhook est correctement configurée dans Ebilling
2. Vérifier que l'URL est accessible publiquement (pas de firewall)
3. Vérifier les logs : `application/logs/log-YYYY-MM-DD.php`

### Paiement réussi mais facture non marquée payée

1. Vérifier le payload webhook dans les logs
2. Vérifier que `external_reference` correspond au numéro de facture
3. Vérifier les permissions du webhook

### Service GPSWOX non activé

1. Vérifier que le client a un `gpswox_user_id` rempli
2. Vérifier l'API Key GPSWOX
3. Vérifier les logs API GPSWOX

### Erreur "Missing credentials"

1. Vérifier Username et Shared Key dans les settings
2. Vérifier l'environnement (LAB/PROD)
3. Tester la connexion API Ebilling

## 📞 Support

### Logs

Tous les événements sont loggés :
```
application/logs/log-YYYY-MM-DD.php
```

Rechercher : `[EBG]`

### Debugging

Activer le mode debug Perfex :
```php
// config.php
define('PERFEX_DEBUG', true);
```

### Contact

- **Ebilling** : support@billing-easy.net
- **GPSWOX** : support@gpswox.com

## 📝 Changelog

### v1.1 (2025)
- ✅ Support multi-opérateurs (Airtel, Moov, Carte)
- ✅ Sécurité webhook améliorée
- ✅ Notifications admin par email
- ✅ Tests unitaires complets
- ✅ Configuration LAB/PROD séparée
- ✅ Gestion d'erreurs robuste
- ✅ Création automatique custom field et mode de paiement

### v1.0 (Initial)
- Intégration de base Ebilling + GPSWOX

## 📜 Licence

Ce module est propriétaire et destiné à un usage interne uniquement.

## 👥 Auteurs

Développé pour **Mbira Tracker**
- Perfex : https://www.mbiratracker.com/espaceclient
- GPSWOX : https://app.mbiratracker.com

---

**Pour toute question technique, consulter la documentation Ebilling et GPSWOX.**
