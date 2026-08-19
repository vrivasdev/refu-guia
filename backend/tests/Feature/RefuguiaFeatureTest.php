<?php

namespace Tests\Feature;

require_once __DIR__ . '/../TestCase.php';
require_once __DIR__ . '/../CreatesApplication.php';

use Tests\TestCase;

class RefuguiaFeatureTest extends TestCase
{
    public function test_api_healthcheck_returns_success()
    {
        $response = $this->getJson('/api/health');
        $response->assertStatus(200);
    }

    public function test_user_authentication_with_valid_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'carmen.lopez@refuguia.org',
            'password' => 'carmen123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'token', 'user']);
    }

    public function test_user_authentication_rejects_invalid_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'carmen.lopez@refuguia.org',
            'password' => 'wrong_password_123'
        ]);

        $response->assertStatus(401);
    }

    public function test_mcp_tools_catalog_returns_5_skills_with_markdown()
    {
        $response = $this->getJson('/api/mcp/tools');
        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'tools']);
        
        $this->assertEquals(5, count($response->json('tools')));
    }
}
