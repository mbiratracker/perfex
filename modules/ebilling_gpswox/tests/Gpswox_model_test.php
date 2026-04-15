<?php
/**
 * Tests unitaires pour Gpswox_model
 *
 * Pour exécuter les tests:
 * phpunit --bootstrap tests/bootstrap.php tests/Gpswox_model_test.php
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Gpswox_model_test extends CI_TestCase
{
    protected $model;

    public function setUp(): void
    {
        parent::setUp();
        $this->CI->load->model('ebilling_gpswox/gpswox_model');
        $this->model = $this->CI->gpswox_model;
    }

    /**
     * Test: Vérifier que l'URL de base est correctement formée
     */
    public function test_base_url_formation()
    {
        $this->mock_get_option('gpswox_base_url', 'https://app.mbiratracker.com');

        $reflection = new ReflectionClass($this->model);
        $method = $reflection->getMethod('base');
        $method->setAccessible(true);

        $result = $method->invoke($this->model);

        $this->assertEquals('https://app.mbiratracker.com', $result);
    }

    /**
     * Test: Vérifier le fallback URL par défaut
     */
    public function test_base_url_fallback()
    {
        $this->mock_get_option('gpswox_base_url', '');

        $reflection = new ReflectionClass($this->model);
        $method = $reflection->getMethod('base');
        $method->setAccessible(true);

        $result = $method->invoke($this->model);

        $this->assertEquals('https://gpswox.com', $result);
    }

    /**
     * Test: Headers contiennent l'API Key
     */
    public function test_headers_include_api_key()
    {
        $this->mock_get_option('gpswox_api_key', 'test_api_key_12345');

        $reflection = new ReflectionClass($this->model);
        $method = $reflection->getMethod('headers');
        $method->setAccessible(true);

        $headers = $method->invoke($this->model);

        $this->assertIsArray($headers);
        $this->assertContains('Authorization: Bearer test_api_key_12345', $headers);
        $this->assertContains('Accept: application/json', $headers);
        $this->assertContains('Content-Type: application/json', $headers);
    }

    /**
     * Test: Renouvellement de compte avec user_id valide
     */
    public function test_renew_account_valid_user()
    {
        $this->mock_get_option('gpswox_api_key', 'valid_key');
        $this->mock_get_option('gpswox_base_url', 'https://app.mbiratracker.com');

        // Mock de la réponse cURL (à implémenter selon votre framework de test)
        // Pour cet exemple, on teste juste que la méthode ne retourne pas d'erreur

        $user_id = 123;
        $period = 30;

        $result = $this->model->renew_account($user_id, $period);

        // Vérifier que le résultat est une chaîne (réponse API)
        $this->assertIsString($result);
    }

    /**
     * Test: Renouvellement avec période personnalisée
     */
    public function test_renew_account_custom_period()
    {
        $this->mock_get_option('gpswox_api_key', 'valid_key');
        $this->mock_get_option('gpswox_base_url', 'https://app.mbiratracker.com');

        $user_id = 456;
        $period = 90; // 3 mois

        $result = $this->model->renew_account($user_id, $period);

        $this->assertIsString($result);
    }

    /**
     * Mock helper pour get_option
     */
    private function mock_get_option($key, $value)
    {
        // Implémenter selon votre framework de mock
    }
}
