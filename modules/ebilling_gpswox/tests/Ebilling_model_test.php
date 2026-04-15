<?php
/**
 * Tests unitaires pour Ebilling_model
 *
 * Pour exécuter les tests:
 * phpunit --bootstrap tests/bootstrap.php tests/Ebilling_model_test.php
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Ebilling_model_test extends CI_TestCase
{
    protected $model;

    public function setUp(): void
    {
        parent::setUp();
        $this->CI->load->model('ebilling_gpswox/ebilling_model');
        $this->model = $this->CI->ebilling_model;
    }

    /**
     * Test: Vérifier que l'URL de base est correctement formée
     */
    public function test_base_url_formation()
    {
        // Mock get_option
        $this->mock_get_option('eb_base_url', 'https://lab.billing-easy.net');

        $reflection = new ReflectionClass($this->model);
        $method = $reflection->getMethod('base');
        $method->setAccessible(true);

        $result = $method->invoke($this->model);

        $this->assertEquals('https://lab.billing-easy.net/api/v1/merchant', $result);
    }

    /**
     * Test: Vérifier le fallback environnement LAB
     */
    public function test_base_url_fallback_lab()
    {
        $this->mock_get_option('eb_base_url', '');
        $this->mock_get_option('eb_environment', 'lab');

        $reflection = new ReflectionClass($this->model);
        $method = $reflection->getMethod('base');
        $method->setAccessible(true);

        $result = $method->invoke($this->model);

        $this->assertStringContainsString('lab.billing-easy.net', $result);
    }

    /**
     * Test: Vérifier le fallback environnement PROD
     */
    public function test_base_url_fallback_prod()
    {
        $this->mock_get_option('eb_base_url', '');
        $this->mock_get_option('eb_environment', 'prod');

        $reflection = new ReflectionClass($this->model);
        $method = $reflection->getMethod('base');
        $method->setAccessible(true);

        $result = $method->invoke($this->model);

        $this->assertStringContainsString('stg.billing-easy.com', $result);
    }

    /**
     * Test: Validation des opérateurs
     */
    public function test_valid_operators()
    {
        $reflection = new ReflectionClass($this->model);
        $method = $reflection->getMethod('is_valid_operator');
        $method->setAccessible(true);

        // Opérateurs valides
        $this->assertTrue($method->invoke($this->model, 'airtelmoney'));
        $this->assertTrue($method->invoke($this->model, 'moovmoney4'));
        $this->assertTrue($method->invoke($this->model, 'ORABANK_NG'));
        $this->assertTrue($method->invoke($this->model, 'card'));

        // Opérateurs invalides
        $this->assertFalse($method->invoke($this->model, 'invalid_operator'));
        $this->assertFalse($method->invoke($this->model, ''));
    }

    /**
     * Test: Création de facture - credentials manquants
     */
    public function test_create_bill_missing_credentials()
    {
        $this->mock_get_option('eb_username', '');
        $this->mock_get_option('eb_shared_key', '');

        $payload = [
            'amount' => '1000',
            'payer_name' => 'Test User',
            'external_reference' => 'INV-001'
        ];

        $result = $this->model->create_bill($payload);

        $this->assertFalse($result);
    }

    /**
     * Test: Obtenir les opérateurs disponibles
     */
    public function test_get_available_operators()
    {
        $this->mock_get_option('eb_enable_ussd', '1');
        $this->mock_get_option('eb_enable_card', '1');

        $operators = $this->model->get_available_operators();

        $this->assertIsArray($operators);
        $this->assertGreaterThan(0, count($operators));

        foreach ($operators as $op) {
            $this->assertArrayHasKey('value', $op);
            $this->assertArrayHasKey('label', $op);
            $this->assertArrayHasKey('enabled', $op);
            $this->assertTrue($op['enabled']);
        }
    }

    /**
     * Test: Opérateurs désactivés
     */
    public function test_get_available_operators_disabled()
    {
        $this->mock_get_option('eb_enable_ussd', '0');
        $this->mock_get_option('eb_enable_card', '0');

        $operators = $this->model->get_available_operators();

        $this->assertIsArray($operators);
        $this->assertEquals(0, count($operators));
    }

    /**
     * Test: USSD Push avec opérateur invalide
     */
    public function test_ussd_push_invalid_operator()
    {
        $this->mock_get_option('eb_username', 'test_user');
        $this->mock_get_option('eb_shared_key', 'test_key');

        $result = $this->model->ussd_push('BILL123', '066123456', 'invalid_operator');

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString('non supporté', $result['message']);
    }

    /**
     * Mock helper pour get_option
     */
    private function mock_get_option($key, $value)
    {
        // Implémenter selon votre framework de mock
        // Exemple avec Mockery ou PHPUnit mocks
    }
}
