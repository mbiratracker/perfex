<?php
/**
 * Tests unitaires pour le contrôleur Webhook
 *
 * Pour exécuter les tests:
 * phpunit --bootstrap tests/bootstrap.php tests/Webhook_test.php
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Webhook_test extends CI_TestCase
{
    protected $controller;

    public function setUp(): void
    {
        parent::setUp();
        $this->controller = new Webhook();
    }

    /**
     * Test: Validation de payload valide
     */
    public function test_validate_payload_valid()
    {
        $payload = [
            'reference' => 'INV-001',
            'status' => 'success',
            'amount' => '5000'
        ];

        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('validate_payload');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $payload);

        $this->assertTrue($result);
    }

    /**
     * Test: Validation de payload avec external_reference
     */
    public function test_validate_payload_external_reference()
    {
        $payload = [
            'external_reference' => 'INV-002',
            'status' => 'success',
            'amount' => '3000'
        ];

        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('validate_payload');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $payload);

        $this->assertTrue($result);
    }

    /**
     * Test: Validation échoue si reference manquante
     */
    public function test_validate_payload_missing_reference()
    {
        $payload = [
            'status' => 'success',
            'amount' => '5000'
        ];

        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('validate_payload');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $payload);

        $this->assertFalse($result);
    }

    /**
     * Test: Validation échoue si status manquant
     */
    public function test_validate_payload_missing_status()
    {
        $payload = [
            'reference' => 'INV-003',
            'amount' => '5000'
        ];

        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('validate_payload');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $payload);

        $this->assertFalse($result);
    }

    /**
     * Test: Validation échoue si payload vide
     */
    public function test_validate_payload_empty()
    {
        $payload = [];

        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('validate_payload');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $payload);

        $this->assertFalse($result);
    }

    /**
     * Test: Validation de sécurité avec secret correct
     */
    public function test_validate_webhook_security_valid_secret()
    {
        $this->mock_get_option('eb_webhook_secret', 'my_secret_key');

        // Mock de l'en-tête HTTP
        $_SERVER['HTTP_X_EBILLING_SECRET'] = 'my_secret_key';

        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('validate_webhook_security');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller);

        $this->assertTrue($result);
    }

    /**
     * Test: Validation de sécurité échoue avec secret incorrect
     */
    public function test_validate_webhook_security_invalid_secret()
    {
        $this->mock_get_option('eb_webhook_secret', 'my_secret_key');

        // Mock de l'en-tête HTTP avec mauvais secret
        $_SERVER['HTTP_X_EBILLING_SECRET'] = 'wrong_secret';

        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('validate_webhook_security');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller);

        $this->assertFalse($result);
    }

    /**
     * Test: Validation de sécurité passe si aucun secret configuré
     */
    public function test_validate_webhook_security_no_secret()
    {
        $this->mock_get_option('eb_webhook_secret', '');

        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('validate_webhook_security');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller);

        $this->assertTrue($result);
    }

    /**
     * Mock helper pour get_option
     */
    private function mock_get_option($key, $value)
    {
        // Implémenter selon votre framework de mock
    }
}
