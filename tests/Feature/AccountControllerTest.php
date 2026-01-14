<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Account;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_accounts()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Account::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson('/api/accounts');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_account()
    {
        $user = User::factory()->create(['role' => 'admin']);
        
        $accountData = [
            'account_code' => '1001',
            'account_name' => 'Cash on Hand',
            'account_type' => 'Asset',
            'opening_balance' => 0,
        ];

        $response = $this->actingAs($user)->postJson('/api/accounts', $accountData);

        $response->assertStatus(201)
                 ->assertJsonFragment(['account_name' => 'Cash on Hand']);
        
        $this->assertDatabaseHas('accounts', ['account_code' => '1001']);
    }
}
