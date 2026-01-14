<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\JournalEntryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_an_account()
    {
        $account = Account::factory()->create([
            'account_code' => 'ACC001',
            'account_name' => 'Cash Account',
            'account_type' => 'asset',
            'opening_balance' => 1000.00,
        ]);

        $this->assertDatabaseHas('accounts', [
            'account_code' => 'ACC001',
            'account_name' => 'Cash Account',
            'account_type' => 'asset',
            'opening_balance' => 1000.00,
        ]);

        $this->assertEquals('ACC001', $account->account_code);
        $this->assertEquals('Cash Account', $account->account_name);
        $this->assertEquals('asset', $account->account_type);
    }

    /** @test */
    public function it_has_a_parent_account_relationship()
    {
        $parent = Account::factory()->create();
        $child = Account::factory()->create([
            'parent_account_id' => $parent->id,
        ]);

        $this->assertInstanceOf(Account::class, $child->parentAccount);
        $this->assertEquals($parent->id, $child->parentAccount->id);
    }

    /** @test */
    public function it_has_child_accounts_relationship()
    {
        $parent = Account::factory()->create();
        $child1 = Account::factory()->create(['parent_account_id' => $parent->id]);
        $child2 = Account::factory()->create(['parent_account_id' => $parent->id]);

        $this->assertCount(2, $parent->childAccounts);
        $this->assertTrue($parent->childAccounts->contains($child1));
        $this->assertTrue($parent->childAccounts->contains($child2));
    }

    /** @test */
    public function it_has_transactions_relationship()
    {
        $account = Account::factory()->create();
        Transaction::factory()->count(3)->create(['account_id' => $account->id]);

        $this->assertCount(3, $account->transactions);
    }

    /** @test */
    public function it_has_journal_entry_items_relationship()
    {
        $account = Account::factory()->create();
        JournalEntryItem::factory()->count(2)->create(['account_id' => $account->id]);

        $this->assertCount(2, $account->journalEntryItems);
    }

    /** @test */
    public function it_calculates_current_balance_for_asset_account()
    {
        $account = Account::factory()->asset()->create([
            'opening_balance' => 1000.00,
        ]);

        // Create transactions: debits increase, credits decrease
        Transaction::factory()->create([
            'account_id' => $account->id,
            'debit_amount' => 500.00,
            'credit_amount' => 0,
        ]);

        Transaction::factory()->create([
            'account_id' => $account->id,
            'debit_amount' => 0,
            'credit_amount' => 200.00,
        ]);

        // Asset: opening + debits - credits = 1000 + 500 - 200 = 1300
        $this->assertEquals(1300.00, $account->current_balance);
    }

    /** @test */
    public function it_calculates_current_balance_for_liability_account()
    {
        $account = Account::factory()->liability()->create([
            'opening_balance' => 1000.00,
        ]);

        // Create transactions: credits increase, debits decrease
        Transaction::factory()->create([
            'account_id' => $account->id,
            'debit_amount' => 0,
            'credit_amount' => 500.00,
        ]);

        Transaction::factory()->create([
            'account_id' => $account->id,
            'debit_amount' => 200.00,
            'credit_amount' => 0,
        ]);

        // Liability: opening + credits - debits = 1000 + 500 - 200 = 1300
        $this->assertEquals(1300.00, $account->current_balance);
    }

    /** @test */
    public function it_calculates_current_balance_for_expense_account()
    {
        $account = Account::factory()->expense()->create([
            'opening_balance' => 0.00,
        ]);

        Transaction::factory()->create([
            'account_id' => $account->id,
            'debit_amount' => 300.00,
            'credit_amount' => 0,
        ]);

        // Expense: opening + debits - credits = 0 + 300 - 0 = 300
        $this->assertEquals(300.00, $account->current_balance);
    }

    /** @test */
    public function it_calculates_total_debits()
    {
        $account = Account::factory()->create();
        
        Transaction::factory()->create([
            'account_id' => $account->id,
            'debit_amount' => 100.00,
            'credit_amount' => 0,
        ]);

        Transaction::factory()->create([
            'account_id' => $account->id,
            'debit_amount' => 200.00,
            'credit_amount' => 0,
        ]);

        $this->assertEquals(300.00, $account->total_debits);
    }

    /** @test */
    public function it_calculates_total_credits()
    {
        $account = Account::factory()->create();
        
        Transaction::factory()->create([
            'account_id' => $account->id,
            'debit_amount' => 0,
            'credit_amount' => 150.00,
        ]);

        Transaction::factory()->create([
            'account_id' => $account->id,
            'debit_amount' => 0,
            'credit_amount' => 250.00,
        ]);

        $this->assertEquals(400.00, $account->total_credits);
    }

    /** @test */
    public function it_returns_full_name_attribute()
    {
        $account = Account::factory()->create([
            'account_code' => 'ACC001',
            'account_name' => 'Cash Account',
        ]);

        $this->assertEquals('ACC001 - Cash Account', $account->full_name);
    }

    /** @test */
    public function it_checks_if_account_has_transactions()
    {
        $accountWithTransactions = Account::factory()->create();
        Transaction::factory()->create(['account_id' => $accountWithTransactions->id]);

        $accountWithoutTransactions = Account::factory()->create();

        $this->assertTrue($accountWithTransactions->hasTransactions());
        $this->assertFalse($accountWithoutTransactions->hasTransactions());
    }

    /** @test */
    public function it_checks_if_account_has_children()
    {
        $parent = Account::factory()->create();
        $child = Account::factory()->create(['parent_account_id' => $parent->id]);

        $accountWithoutChildren = Account::factory()->create();

        $this->assertTrue($parent->hasChildren());
        $this->assertFalse($accountWithoutChildren->hasChildren());
    }

    /** @test */
    public function it_scopes_active_accounts()
    {
        Account::factory()->count(3)->create(['is_active' => true]);
        Account::factory()->count(2)->create(['is_active' => false]);

        $activeAccounts = Account::active()->get();

        $this->assertCount(3, $activeAccounts);
        $this->assertTrue($activeAccounts->every(fn ($account) => $account->is_active));
    }

    /** @test */
    public function it_scopes_inactive_accounts()
    {
        Account::factory()->count(3)->create(['is_active' => true]);
        Account::factory()->count(2)->create(['is_active' => false]);

        $inactiveAccounts = Account::inactive()->get();

        $this->assertCount(2, $inactiveAccounts);
        $this->assertTrue($inactiveAccounts->every(fn ($account) => !$account->is_active));
    }

    /** @test */
    public function it_scopes_by_account_type()
    {
        Account::factory()->count(3)->asset()->create();
        Account::factory()->count(2)->liability()->create();

        $assetAccounts = Account::ofType('asset')->get();

        $this->assertCount(3, $assetAccounts);
        $this->assertTrue($assetAccounts->every(fn ($account) => $account->account_type === 'asset'));
    }

    /** @test */
    public function it_scopes_parent_accounts()
    {
        $parent1 = Account::factory()->create(['parent_account_id' => null]);
        $parent2 = Account::factory()->create(['parent_account_id' => null]);
        Account::factory()->create(['parent_account_id' => $parent1->id]);

        $parentAccounts = Account::parentAccounts()->get();

        $this->assertCount(2, $parentAccounts);
        $this->assertTrue($parentAccounts->every(fn ($account) => $account->parent_account_id === null));
    }

    /** @test */
    public function it_scopes_children_of_parent()
    {
        $parent = Account::factory()->create();
        $child1 = Account::factory()->create(['parent_account_id' => $parent->id]);
        $child2 = Account::factory()->create(['parent_account_id' => $parent->id]);
        Account::factory()->create(['parent_account_id' => null]);

        $children = Account::childrenOf($parent->id)->get();

        $this->assertCount(2, $children);
        $this->assertTrue($children->every(fn ($account) => $account->parent_account_id === $parent->id));
    }

    /** @test */
    public function it_scopes_search_by_code()
    {
        Account::factory()->create(['account_code' => 'ACC001', 'account_name' => 'Cash']);
        Account::factory()->create(['account_code' => 'ACC002', 'account_name' => 'Bank']);
        Account::factory()->create(['account_code' => 'ACC003', 'account_name' => 'Inventory']);

        $results = Account::search('ACC001')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('ACC001', $results->first()->account_code);
    }

    /** @test */
    public function it_scopes_search_by_name()
    {
        Account::factory()->create(['account_code' => 'ACC001', 'account_name' => 'Cash Account']);
        Account::factory()->create(['account_code' => 'ACC002', 'account_name' => 'Bank Account']);
        Account::factory()->create(['account_code' => 'ACC003', 'account_name' => 'Inventory']);

        $results = Account::search('Cash')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Cash Account', $results->first()->account_name);
    }

    /** @test */
    public function it_returns_validation_rules()
    {
        $rules = Account::rules();

        $this->assertArrayHasKey('account_code', $rules);
        $this->assertArrayHasKey('account_name', $rules);
        $this->assertArrayHasKey('account_type', $rules);
        $this->assertArrayHasKey('parent_account_id', $rules);
        $this->assertArrayHasKey('opening_balance', $rules);
    }

    /** @test */
    public function it_excludes_fields_from_validation_rules()
    {
        $rules = Account::rules(['account_code']);

        $this->assertArrayNotHasKey('account_code', $rules);
        $this->assertArrayHasKey('account_name', $rules);
    }

    /** @test */
    public function it_validates_parent_account_exists()
    {
        $parent = Account::factory()->create();
        $account = Account::factory()->create();

        $this->assertTrue(Account::validateParentAccount($account->id, $parent->id));
        $this->assertFalse(Account::validateParentAccount($account->id, 99999)); // Non-existent parent
    }

    /** @test */
    public function it_validates_account_cannot_be_parent_of_itself()
    {
        $account = Account::factory()->create();

        $this->assertFalse(Account::validateParentAccount($account->id, $account->id));
    }

    /** @test */
    public function it_checks_if_account_is_ancestor_of_another()
    {
        $grandparent = Account::factory()->create();
        $parent = Account::factory()->create(['parent_account_id' => $grandparent->id]);
        $child = Account::factory()->create(['parent_account_id' => $parent->id]);

        $this->assertTrue($grandparent->isAncestorOf($child->id));
        $this->assertTrue($parent->isAncestorOf($child->id));
        $this->assertFalse($child->isAncestorOf($grandparent->id));
    }

    /** @test */
    public function it_returns_hierarchy_path()
    {
        $parent = Account::factory()->create(['account_name' => 'Assets']);
        $child = Account::factory()->create([
            'account_name' => 'Current Assets',
            'parent_account_id' => $parent->id,
        ]);
        $grandchild = Account::factory()->create([
            'account_name' => 'Cash',
            'parent_account_id' => $child->id,
        ]);

        $this->assertEquals('Assets > Current Assets > Cash', $grandchild->hierarchy_path);
        $this->assertEquals('Assets > Current Assets', $child->hierarchy_path);
        $this->assertEquals('Assets', $parent->hierarchy_path);
    }

    /** @test */
    public function it_gets_tree_structure()
    {
        $parent1 = Account::factory()->create(['account_name' => 'Assets', 'is_active' => true]);
        $parent2 = Account::factory()->create(['account_name' => 'Liabilities', 'is_active' => true]);
        
        $child1 = Account::factory()->create([
            'account_name' => 'Current Assets',
            'parent_account_id' => $parent1->id,
            'is_active' => true,
        ]);
        
        $child2 = Account::factory()->create([
            'account_name' => 'Fixed Assets',
            'parent_account_id' => $parent1->id,
            'is_active' => true,
        ]);

        $tree = Account::getTreeStructure();

        $this->assertGreaterThanOrEqual(2, $tree->count()); // At least two parent accounts
        
        // Find the Assets tree in the collection
        $assetsTree = $tree->first(function ($item) {
            return $item['account_name'] === 'Assets';
        });
        
        $this->assertNotNull($assetsTree);
        $this->assertCount(2, $assetsTree['children']); // Two children
    }

    /** @test */
    public function it_casts_opening_balance_to_decimal()
    {
        $account = Account::factory()->create(['opening_balance' => 1234.56]);

        // Laravel's decimal cast returns a string, not a float
        $this->assertIsString($account->opening_balance);
        $this->assertEquals('1234.56', $account->opening_balance);
    }

    /** @test */
    public function it_casts_is_active_to_boolean()
    {
        $activeAccount = Account::factory()->create(['is_active' => true]);
        $inactiveAccount = Account::factory()->create(['is_active' => false]);

        $this->assertIsBool($activeAccount->is_active);
        $this->assertTrue($activeAccount->is_active);
        $this->assertFalse($inactiveAccount->is_active);
    }
}
