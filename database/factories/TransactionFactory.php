<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => $this->faker->date(),
            'account_id' => Account::factory(),
            'description' => $this->faker->sentence(),
            'debit_amount' => 0,
            'credit_amount' => 0,
            'reference_number' => $this->faker->optional()->numerify('REF####'),
            'transaction_type' => $this->faker->optional()->randomElement(['payment', 'receipt', 'journal', 'adjustment']),
            'running_balance' => 0,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the transaction is a debit.
     */
    public function debit(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'debit_amount' => $amount ?? $this->faker->randomFloat(2, 10, 1000),
            'credit_amount' => 0,
        ]);
    }

    /**
     * Indicate that the transaction is a credit.
     */
    public function credit(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'debit_amount' => 0,
            'credit_amount' => $amount ?? $this->faker->randomFloat(2, 10, 1000),
        ]);
    }
}
