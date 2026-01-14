<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $accountTypes = ['asset', 'liability', 'equity', 'revenue', 'expense'];
        
        return [
            'account_code' => $this->faker->unique()->numerify('ACC####'),
            'account_name' => $this->faker->words(3, true),
            'account_type' => $this->faker->randomElement($accountTypes),
            'parent_account_id' => null,
            'opening_balance' => $this->faker->randomFloat(2, -10000, 10000),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the account is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the account is an asset.
     */
    public function asset(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'asset',
        ]);
    }

    /**
     * Indicate that the account is a liability.
     */
    public function liability(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'liability',
        ]);
    }

    /**
     * Indicate that the account is equity.
     */
    public function equity(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'equity',
        ]);
    }

    /**
     * Indicate that the account is revenue.
     */
    public function revenue(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'revenue',
        ]);
    }

    /**
     * Indicate that the account is an expense.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'expense',
        ]);
    }

    /**
     * Set a parent account.
     */
    public function withParent(Account $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_account_id' => $parent->id,
        ]);
    }
}
