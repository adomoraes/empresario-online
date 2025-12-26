<?php

namespace Tests\Feature;

use Tests\TestCase;

class CategoryTest extends TestCase
{
    public function test_admin_can_create_category()
    {
        $token = $this->authenticateUser('admin');

        $response = $this->call('POST', '/categories', [
            'name' => 'Inteligência Artificial'
        ], ['Authorization' => "Bearer $token"]);

        $this->assertEquals(201, $response['status']);

        // Verificar Slug Automático
        $stmt = $this->pdo->prepare("SELECT slug FROM categories WHERE name = ?");
        $stmt->execute(['Inteligência Artificial']);
        $this->assertEquals('inteligencia-artificial', $stmt->fetchColumn());
    }

    public function test_admin_can_update_category()
    {
        $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES (5, 'Old Name', 'old-name')");
        $token = $this->authenticateUser('admin');

        $response = $this->call('PUT', '/categories', [
            'id' => 5,
            'name' => 'New Name'
        ], ['Authorization' => "Bearer $token"]);

        $this->assertEquals(200, $response['status']);

        // Verificar atualização do slug também
        $stmt = $this->pdo->prepare("SELECT slug FROM categories WHERE id = 5");
        $stmt->execute();
        $this->assertEquals('new-name', $stmt->fetchColumn());
    }

    public function test_admin_can_delete_category()
    {
        $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES (6, 'Temp', 'temp')");
        $token = $this->authenticateUser('admin');

        $response = $this->call('DELETE', '/categories', [
            'id' => 6
        ], ['Authorization' => "Bearer $token"]);

        $this->assertEquals(200, $response['status']);
    }
}
