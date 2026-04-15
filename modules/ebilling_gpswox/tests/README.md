# Tests unitaires - Module Ebilling + GPSWOX

## Installation

```bash
# Installer PHPUnit (si pas déjà installé)
composer require --dev phpunit/phpunit

# Installer les dépendances de test
composer require --dev mockery/mockery
```

## Structure des tests

```
tests/
├── Ebilling_model_test.php    # Tests du modèle Ebilling
├── Gpswox_model_test.php      # Tests du modèle GPSWOX
├── Webhook_test.php            # Tests du contrôleur Webhook
├── bootstrap.php               # Bootstrap pour PHPUnit
└── README.md                   # Ce fichier
```

## Exécution des tests

### Tous les tests
```bash
phpunit --bootstrap tests/bootstrap.php tests/
```

### Un fichier de test spécifique
```bash
phpunit --bootstrap tests/bootstrap.php tests/Ebilling_model_test.php
```

### Avec couverture de code
```bash
phpunit --bootstrap tests/bootstrap.php --coverage-html coverage/ tests/
```

## Tests inclus

### Ebilling_model_test.php
- ✅ Formation de l'URL de base (LAB/PROD)
- ✅ Fallback environnement
- ✅ Validation des opérateurs
- ✅ Gestion credentials manquants
- ✅ Liste des opérateurs disponibles
- ✅ USSD Push avec validation

### Gpswox_model_test.php
- ✅ Formation URL de base
- ✅ Headers avec API Key
- ✅ Renouvellement de compte
- ✅ Période personnalisée

### Webhook_test.php
- ✅ Validation de payload (reference/external_reference)
- ✅ Validation des champs obligatoires
- ✅ Sécurité webhook (secret partagé)
- ✅ Gestion des erreurs

## Configuration

Créer un fichier `phpunit.xml` à la racine du projet :

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php"
         colors="true"
         verbose="true">
    <testsuites>
        <testsuite name="Ebilling GPSWOX Module">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <filter>
        <whitelist>
            <directory suffix=".php">models</directory>
            <directory suffix=".php">controllers</directory>
            <exclude>
                <directory>views</directory>
                <directory>tests</directory>
            </exclude>
        </whitelist>
    </filter>
</phpunit>
```

## Mocking

Pour les tests nécessitant des mocks (API externes, base de données), utiliser Mockery :

```php
use Mockery as m;

public function test_example()
{
    $mock = m::mock('Ebilling_model');
    $mock->shouldReceive('create_bill')
         ->once()
         ->andReturn('BILL_12345');

    $this->assertNotEmpty($mock->create_bill([]));
}

public function tearDown(): void
{
    m::close();
}
```

## Intégration continue

Exemple de configuration GitHub Actions (`.github/workflows/tests.yml`) :

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
          extensions: curl, json

      - name: Install dependencies
        run: composer install

      - name: Run tests
        run: phpunit --bootstrap tests/bootstrap.php tests/
```

## Notes

- Les tests utilisent la réflexion PHP pour tester les méthodes privées
- Les mocks de `get_option()` doivent être adaptés selon votre environnement de test Perfex
- Pour les tests d'intégration (appels API réels), créer un fichier séparé `IntegrationTest.php`
