# Guide de configuration - Module Ebilling + GPSWOX

## 📋 Checklist de configuration

Suivez ces étapes dans l'ordre pour configurer le module :

### ✅ Étape 1 : Installation du module

- [ ] Déposer le dossier `ebilling_gpswox` dans `/modules/`
- [ ] Activer le module dans Perfex (Modules → Ebilling + GPSWOX → Activer)
- [ ] Vérifier que la table `ebilling_transactions` est créée
- [ ] Vérifier que le custom field `gpswox_user_id` existe
- [ ] Vérifier que le mode de paiement "ebilling" est créé

### ✅ Étape 2 : Configuration Ebilling (LAB)

#### Dans Perfex

Aller dans **Réglages** → **Ebilling + GPSWOX** :

1. **Environnement** : `LAB (Test)`

2. **Identifiants** :
   ```
   Username : [votre username LAB]
   Shared Key : [votre shared key LAB]
   ```

3. **URLs** :
   ```
   API Base URL : https://lab.billing-easy.net
   Portail de paiement URL : https://test.billing-easy.net/
   Redirect URL (après paiement) : https://www.mbiratracker.com/espaceclient
   ```

4. **Sécurité** :
   ```
   Webhook Secret (optionnel) : [générer un secret aléatoire]
   ```
   Exemple : `ebilling_webhook_secret_2025_secure`

5. **Opérateurs** :
   ```
   USSD Push (Airtel, Moov) : Activé
   Paiement Carte (VISA/MasterCard) : Activé
   Notifications admin par email : Activé
   ```

6. **Sauvegarder**

#### Dans votre compte Ebilling LAB

1. Se connecter sur : https://lab.billing-easy.net
2. Aller dans **Settings** → **Webhooks**
3. Configurer l'URL webhook :
   ```
   https://www.mbiratracker.com/espaceclient/ebilling/webhook
   ```
4. Si vous avez configuré un secret, l'ajouter aussi dans Ebilling

### ✅ Étape 3 : Configuration GPSWOX

1. **Base URL** : `https://app.mbiratracker.com`

2. **API Key** :
   - Se connecter sur GPSWOX
   - Aller dans Settings → API
   - Copier votre API Key

3. **Sauvegarder**

### ✅ Étape 4 : Mapping clients → GPSWOX

Pour chaque client Perfex devant avoir accès au service GPSWOX :

1. Ouvrir la fiche client dans Perfex
2. Aller dans l'onglet **Informations personnalisées**
3. Renseigner le champ **GPSWOX User ID**
   - Trouver l'ID utilisateur dans GPSWOX (https://app.mbiratracker.com/admin/users/clients)
   - Exemple : `12345`
4. Sauvegarder

**Note** : Sans ce mapping, le renouvellement GPSWOX ne sera pas automatique.

### ✅ Étape 5 : Test en LAB

#### Test 1 : Création de facture

1. Créer une facture de test dans Perfex
2. Ouvrir la facture côté client
3. Cliquer sur **"Payer avec Ebilling"**
4. Vérifier la redirection vers le portail Ebilling
5. Vérifier que l'URL contient : `?invoice=BILL_xxx&redirect_url=...`

#### Test 2 : Paiement simulé

1. Sur le portail Ebilling, sélectionner un opérateur
2. Utiliser un numéro de test (fourni par Ebilling)
3. Valider le paiement
4. Attendre le callback webhook

#### Test 3 : Vérification callback

1. Vérifier les logs Perfex : `application/logs/log-YYYY-MM-DD.php`
2. Rechercher : `[EBG] Webhook payload received`
3. Vérifier que la facture est marquée **Payée**
4. Vérifier la transaction dans la table `ebilling_transactions`

#### Test 4 : Vérification GPSWOX

1. Se connecter sur GPSWOX
2. Vérifier que l'abonnement du client a été renouvelé (+30 jours)
3. Vérifier les logs : `[EBG] GPSWOX account renewed`

### ✅ Étape 6 : Passage en PRODUCTION

Une fois tous les tests réussis en LAB :

1. Dans Perfex, aller dans **Réglages** → **Ebilling + GPSWOX**

2. **Changer l'environnement** : `PRODUCTION`

3. **Identifiants PROD** :
   ```
   Username : [votre username PROD]
   Shared Key : [votre shared key PROD]
   ```

4. **URLs PROD** :
   ```
   API Base URL : https://stg.billing-easy.com
   Portail de paiement URL : https://staging.billing-easy.net/
   ```

5. **Webhook Secret PROD** :
   - Générer un nouveau secret (différent de LAB)
   - Le configurer aussi dans votre compte Ebilling PROD

6. **Sauvegarder**

7. **Dans Ebilling PROD** :
   - Configurer la même URL webhook
   - Ajouter le nouveau secret

### ✅ Étape 7 : Test final en PRODUCTION

1. Créer une facture de test minime (ex: 100 FCFA)
2. Effectuer un paiement réel
3. Vérifier tous les flux comme en LAB
4. Valider que l'abonnement GPSWOX est bien renouvelé

## 🔍 URLs de référence

### LAB (Test)
```
API Ebilling     : https://lab.billing-easy.net/api/v1/merchant
Portail Ebilling : https://test.billing-easy.net/
Dashboard        : https://lab.billing-easy.net
```

### PRODUCTION
```
API Ebilling     : https://stg.billing-easy.com/api/v1/merchant
Portail Ebilling : https://staging.billing-easy.net/
Dashboard        : https://stg.billing-easy.com
```

### GPSWOX
```
Application : https://app.mbiratracker.com
API         : https://app.mbiratracker.com/api
Admin       : https://app.mbiratracker.com/admin
```

### Perflex
```
Admin  : https://www.mbiratracker.com/espaceclient/admin
Client : https://www.mbiratracker.com/espaceclient
```

## 🔐 Sécurité - Génération du Webhook Secret

### Méthode 1 : Ligne de commande

```bash
# Linux/Mac
openssl rand -base64 32

# Windows PowerShell
[System.Convert]::ToBase64String((1..32 | ForEach-Object { Get-Random -Minimum 0 -Maximum 256 }))
```

### Méthode 2 : PHP

```php
echo bin2hex(random_bytes(32));
```

### Méthode 3 : En ligne

Utiliser : https://randomkeygen.com/ (section "Fort Knox Passwords")

**Important** :
- Ne JAMAIS réutiliser le même secret entre LAB et PROD
- Changer le secret régulièrement (tous les 3-6 mois)
- Ne JAMAIS commit le secret dans un repo Git

## 📊 Monitoring

### Vérifications quotidiennes

1. **Transactions en attente** :
   ```sql
   SELECT * FROM tblbilling_transactions
   WHERE status = 'pending'
   AND created_at < NOW() - INTERVAL 24 HOUR;
   ```

2. **Logs d'erreurs** :
   ```bash
   grep "[EBG].*error" application/logs/log-$(date +%Y-%m-%d).php
   ```

3. **Clients sans mapping GPSWOX** :
   - Vérifier la page de settings (section Statistiques)
   - Vérifier les logs : `[EBG] GPSWOX mapping not found`

### Alertes à configurer

- Email admin si paiement reçu mais facture non marquée payée
- Email admin si mapping GPSWOX manquant lors d'un paiement
- Email admin si erreur API GPSWOX

## 🚨 Troubleshooting rapide

| Problème | Cause probable | Solution |
|----------|----------------|----------|
| Webhook ne reçoit rien | URL mal configurée dans Ebilling | Vérifier URL exacte, tester avec curl |
| Facture non payée après callback | Reference ne correspond pas | Vérifier format numéro facture |
| GPSWOX non renouvelé | Mapping manquant | Ajouter `gpswox_user_id` au client |
| "Missing credentials" | Username/Key vides | Vérifier settings |
| Erreur 403 webhook | Secret incorrect | Vérifier secret des 2 côtés |

## 📞 Contacts support

### Ebilling
- Email : support@billing-easy.net
- Dashboard LAB : https://lab.billing-easy.net
- Dashboard PROD : https://stg.billing-easy.com

### GPSWOX
- Email : support@gpswox.com
- Dashboard : https://app.mbiratracker.com

### Technique
- Logs Perfex : `application/logs/`
- Recherche : `[EBG]`

---

**✅ Configuration terminée ! Vous êtes prêt à accepter des paiements.**
