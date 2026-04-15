# Module Brevo pour Perfex CRM

Module d'intégration Brevo (anciennement Sendinblue) pour l'envoi d'emails transactionnels et de SMS depuis Perfex CRM.

## Fonctionnalités

- ✉️ **Envoi d'emails via API Brevo** : Alternative au SMTP classique
- 📱 **Envoi de SMS** : Notifications SMS directement depuis Perfex
- 🔧 **Configuration simple** : Interface d'administration intuitive
- ✅ **Tests intégrés** : Testez vos configurations directement depuis l'interface
- 📊 **Logs détaillés** : Mode debug pour le suivi des envois

## Installation

1. Le module est déjà dans `/modules/brevo/`
2. Allez dans **Configuration > Modules** dans Perfex
3. Activez le module **Brevo**

## Configuration

### 1. Obtenir votre clé API Brevo

1. Connectez-vous à votre compte Brevo : https://app.brevo.com
2. Allez dans **SMTP & API > API Keys** : https://app.brevo.com/settings/keys/api
3. Créez une nouvelle clé API v3
4. Copiez la clé (format : `xkeysib-...`)

### 2. Configurer le module dans Perfex

1. Allez dans **Configuration > Brevo** (ou via le menu latéral)
2. Collez votre clé API
3. Cliquez sur **"Tester la connexion"** pour vérifier

### 3. Configuration Email (Optionnel)

Si vous souhaitez utiliser Brevo pour l'envoi d'emails :

1. Cochez **"Utiliser Brevo SMTP pour les emails"**
2. Les paramètres par défaut sont :
   - Hôte : `smtp-relay.brevo.com`
   - Port : `587` (TLS)
3. Entrez vos identifiants SMTP Brevo :
   - Obtenez-les depuis : https://app.brevo.com/settings/keys/smtp
4. Testez l'envoi avec le bouton **"Envoyer test email"**

### 4. Configuration SMS (Optionnel)

Si vous souhaitez envoyer des SMS :

1. Cochez **"Activer l'envoi de SMS via Brevo"**
2. Définissez le nom de l'expéditeur (11 caractères max)
3. Testez avec un numéro au format international : `+237xxxxxxxxx`

**Note :** L'envoi de SMS nécessite des crédits SMS dans votre compte Brevo.

## Utilisation dans le code

### Envoyer un SMS

```php
// Charger le helper
$this->load->helper('brevo/brevo');

// Envoyer un SMS
$result = brevo_send_sms(
    '+237670000000',                    // Numéro au format international
    'Votre message ici',                // Contenu du SMS (max 160 caractères)
    'Perfex'                            // Nom de l'expéditeur (optionnel)
);

if ($result['success']) {
    echo 'SMS envoyé !';
} else {
    echo 'Erreur : ' . $result['message'];
}
```

### Envoyer un Email

```php
// Charger le helper
$this->load->helper('brevo/brevo');

// Envoyer un email
$result = brevo_send_email([
    'to' => [
        'email' => 'client@example.com',
        'name' => 'John Doe'            // Optionnel
    ],
    'subject' => 'Votre facture',
    'htmlContent' => '<h1>Bonjour</h1><p>Voici votre facture...</p>',
    'textContent' => 'Version texte...',  // Optionnel
    'from' => [                           // Optionnel (utilise config par défaut)
        'email' => 'noreply@votredomaine.com',
        'name' => 'Votre Entreprise'
    ],
    'replyTo' => [                        // Optionnel
        'email' => 'support@votredomaine.com'
    ]
]);

if ($result['success']) {
    echo 'Email envoyé !';
} else {
    echo 'Erreur : ' . $result['message'];
}
```

### Tester la connexion

```php
$this->load->helper('brevo/brevo');

$result = brevo_test_connection();
if ($result['success']) {
    echo 'Connexion OK';
    print_r($result['data']); // Infos du compte
}
```

## Intégration avec les emails Perfex

Pour que tous les emails de Perfex passent par Brevo, vous avez deux options :

### Option 1 : Via l'API (recommandé)

Modifiez la configuration email de Perfex pour utiliser le module Brevo à la place du système d'email classique.

### Option 2 : Via SMTP

1. Allez dans **Configuration > Paramètres > Email**
2. Configurez SMTP avec les paramètres Brevo :
   - Hôte SMTP : `smtp-relay.brevo.com`
   - Port : `587`
   - Username : Votre email Brevo
   - Password : Votre clé SMTP Brevo

## Logs et Debugging

Pour activer les logs détaillés :

1. Cochez **"Activer le mode debug"** dans les paramètres
2. Les logs seront disponibles dans les logs système de Perfex
3. Format des logs : `[BREVO] message {context}`

Consultez les logs dans `/application/logs/`

## Exemples d'utilisation

### Notification de paiement par SMS

```php
$invoice = $this->invoices_model->get($invoice_id);
$client = $this->clients_model->get($invoice->clientid);

if (!empty($client->phonenumber)) {
    brevo_send_sms(
        $client->phonenumber,
        "Votre paiement de {$invoice->total} a été reçu. Merci !",
        'Perfex'
    );
}
```

### Email de rappel de facture

```php
$invoice = $this->invoices_model->get($invoice_id);
$client = $this->clients_model->get($invoice->clientid);

brevo_send_email([
    'to' => ['email' => $client->email, 'name' => $client->company],
    'subject' => 'Rappel: Facture ' . $invoice->formatted_number,
    'htmlContent' => view('emails/invoice_reminder', ['invoice' => $invoice], true)
]);
```

## Support et ressources

- Documentation Brevo API : https://developers.brevo.com/
- Support Brevo : https://help.brevo.com/
- Perfex CRM : https://www.perfexcrm.com/

## Limites et quotas

- **Emails** : Selon votre plan Brevo (Free: 300/jour, Lite: illimité)
- **SMS** : Nécessite l'achat de crédits SMS
- **API** : Rate limit selon votre plan

Vérifiez vos limites dans : https://app.brevo.com/account/plan

## Changelog

### Version 1.0.0
- Première version
- Support email via API Brevo
- Support SMS via API Brevo
- Interface de configuration
- Tests intégrés
- Mode debug
