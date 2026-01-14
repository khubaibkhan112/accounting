<?php

namespace Tests\Unit;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\User;
use App\Models\Customer;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_transaction()
    {
        $account = Account::factory()->create();
        $user = User::factory()->create();

        $transaction = Transaction::factory()->create([
            'account_id' => $account->id,
            'date' => '2024-01-15',
            'description' => 'Test transaction',
            'debit_amount' => 100.00,
            'credit_amount' => 0,
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'description' => 'Test transaction',
            'debit_amount' => '100.00',
            'credit_amount' => '0.00',
        ]);

        $this->assertEquals($account->id, $transaction->account_id);
        $this->assertEquals('Test transaction', $transaction->description);
    }

    /** @test */
    public function it_has_an_account_relationship()
    {
        $account = Account::factory()->create();
        $transaction = Transaction::factory()->create(['account_id' => $account->id]);

        $this->assertInstanceOf(Account::class, $transaction->account);
        $this->assertEquals($account->id, $transaction->account->id);
    }

    /** @test */
    public function it_has_a_creator_relationship()
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $transaction->creator);
        $this->assertEquals($user->id, $transaction->creator->id);
    }

    /** @test */
    public function it_has_a_customer_relationship()
    {
        $customer = Customer::factory()->create();
        $transaction = Transaction::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $transaction->customer);
        $this->assertEquals($customer->id, $transaction->customer->id);
    }

    /** @test */
    public function it_has_an_employee_relationship()
    {
        $employee = Employee::factory()->create();
        $transaction = Transaction::factory()->create(['employee_id' => $employee->id]);

        $this->assertInstanceOf(Employee::class, $transaction->employee);
        $this->assertEquals($employee->id, $transaction->employee->id);
    }

    /** @test */
    public function it_casts_date_to_date_instance()
    {
        $transaction = Transaction::factory()->create(['date' => '2024-01-15']);

        $this->assertInstanceOf(\Carbon\Carbon::class, $transaction->date);
        $this->assertEquals('2024-01-15', $transaction->date->format('Y-m-d'));
    }

    /** @test */
    public function it_casts_debit_amount_to_decimal()
    {
        $transaction = Transaction::factory()->create(['debit_amount' => 1234.56]);

        // Laravel's decimal cast returns a string
        $this->assertIsString($transaction->debit_amount);
        $this->assertEquals('1234.56', $transaction->debit_amount);
    }

    /** @test */
    public function it_casts_credit_amount_to_decimal()
    {
        $transaction = Transaction::factory()->create(['credit_amount' => 987.65]);

        // Laravel's decimal cast returns a string
        $this->assertIsString($transaction->credit_amount);
        $this->assertEquals('987.65', $transaction->credit_amount);
    }

    /** @test */
    public function it_casts_running_balance_to_decimal()
    {
        $transaction = Transaction::factory()->create(['running_balance' => 5000.00]);

        // Laravel's decimal cast returns a string
        $this->assertIsString($transaction->running_balance);
        $this->assertEquals('5000.00', $transaction->running_balance);
    }

    /** @test */
    public function it_can_create_a_debit_transaction()
    {
        $transaction = Transaction::factory()->debit(250.00)->create();

        $this->assertEquals('250.00', $transaction->debit_amount);
        $this->assertEquals('0.00', $transaction->credit_amount);
    }

    /** @test */
    public function it_can_create_a_credit_transaction()
    {
        $transaction = Transaction::factory()->credit(150.00)->create();

        $this->assertEquals('0.00', $transaction->debit_amount);
        $this->assertEquals('150.00', $transaction->credit_amount);
    }

    /** @test */
    public function it_affects_account_balance_when_debit_is_added()
    {
        $account = Account::factory()->asset()->create(['opening_balance' => 1000.00]);

        Transaction::factory()->debit(500.00)->create(['account_id' => $account->id]);

        // Asset account: opening + debits - credits = 1000 + 500 - 0 = 1500
        $this->assertEquals(1500.00, $account->fresh()->current_balance);
    }

    /** @test */
    public function it_affects_account_balance_when_credit_is_added()
    {
        $account = Account::factory()->asset()->create(['opening_balance' => 1000.00]);

        Transaction::factory()->credit(200.00)->create(['account_id' => $account->id]);

        // Asset account: opening + debits - credits = 1000 + 0 - 200 = 800
        $this->assertEquals(800.00, $account->fresh()->current_balance);
    }

    /** @test */
    public function it_affects_liability_account_balance_correctly()
    {
        $account = Account::factory()->liability()->create(['opening_balance' => 1000.00]);

        Transaction::factory()->credit(300.00)->create(['account_id' => $account->id]);
        Transaction::factory()->debit(100.00)->create(['account_id' => $account->id]);

        // Liability account: opening + credits - debits = 1000 + 300 - 100 = 1200
        $this->assertEquals(1200.00, $account->fresh()->current_balance);
    }

    /** @test */
    public function it_calculates_account_total_debits()
    {
        $account = Account::factory()->create();

        Transaction::factory()->debit(100.00)->create(['account_id' => $account->id]);
        Transaction::factory()->debit(200.00)->create(['account_id' => $account->id]);
        Transaction::factory()->debit(50.00)->create(['account_id' => $account->id]);

        $this->assertEquals(350.00, $account->fresh()->total_debits);
    }

    /** @test */
    public function it_calculates_account_total_credits()
    {
        $account = Account::factory()->create();

        Transaction::factory()->credit(150.00)->create(['account_id' => $account->id]);
        Transaction::factory()->credit(250.00)->create(['account_id' => $account->id]);
        Transaction::factory()->credit(100.00)->create(['account_id' => $account->id]);

        $this->assertEquals(500.00, $account->fresh()->total_credits);
    }

    /** @test */
    public function it_can_have_multiple_transactions_on_same_account()
    {
        $account = Account::factory()->asset()->create(['opening_balance' => 0]);

        Transaction::factory()->debit(100.00)->create(['account_id' => $account->id]);
        Transaction::factory()->debit(200.00)->create(['account_id' => $account->id]);
        Transaction::factory()->credit(50.00)->create(['account_id' => $account->id]);

        $this->assertCount(3, $account->fresh()->transactions);
        $this->assertEquals(250.00, $account->fresh()->current_balance);
    }

    /** @test */
    public function it_can_have_optional_reference_number()
    {
        $transactionWithRef = Transaction::factory()->create([
            'reference_number' => 'REF1234',
        ]);

        $transactionWithoutRef = Transaction::factory()->create([
            'reference_number' => null,
        ]);

        $this->assertEquals('REF1234', $transactionWithRef->reference_number);
        $this->assertNull($transactionWithoutRef->reference_number);
    }

    /** @test */
    public function it_can_have_optional_transaction_type()
    {
        $transaction = Transaction::factory()->create([
            'transaction_type' => 'payment',
        ]);

        $this->assertEquals('payment', $transaction->transaction_type);
    }

    /** @test */
    public function it_can_have_optional_customer()
    {
        $transactionWithCustomer = Transaction::factory()->create([
            'customer_id' => Customer::factory()->create()->id,
        ]);

        $transactionWithoutCustomer = Transaction::factory()->create([
            'customer_id' => null,
        ]);

        $this->assertNotNull($transactionWithCustomer->customer);
        $this->assertNull($transactionWithoutCustomer->customer_id);
    }

    /** @test */
    public function it_can_have_optional_employee()
    {
        $transactionWithEmployee = Transaction::factory()->create([
            'employee_id' => Employee::factory()->create()->id,
        ]);

        $transactionWithoutEmployee = Transaction::factory()->create([
            'employee_id' => null,
        ]);

        $this->assertNotNull($transactionWithEmployee->employee);
        $this->assertNull($transactionWithoutEmployee->employee_id);
    }

    /** @test */
    public function it_can_have_running_balance()
    {
        $transaction = Transaction::factory()->create([
            'running_balance' => 5000.00,
        ]);

        $this->assertEquals('5000.00', $transaction->running_balance);
    }

    /** @test */
    public function it_can_be_created_without_creator()
    {
        $transaction = Transaction::factory()->create([
            'created_by' => null,
        ]);

        $this->assertNull($transaction->created_by);
        $this->assertNull($transaction->creator);
    }

    /** @test */
    public function it_requires_account_id()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Transaction::factory()->create([
            'account_id' => null,
        ]);
    }

    /** @test */
    public function it_requires_date()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Transaction::factory()->create([
            'date' => null,
        ]);
    }

    /** @test */
    public function it_requires_description()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Transaction::factory()->create([
            'description' => null,
        ]);
    }
}
