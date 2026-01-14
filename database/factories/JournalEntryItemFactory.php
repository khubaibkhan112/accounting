<?php

namespace Database\Factories;

use App\Models\JournalEntryItem;
use App\Models\JournalEntry;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JournalEntryItem>
 */
class JournalEntryItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = JournalEntryItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'journal_entry_id' => JournalEntry::factory(),
            'account_id' => Account::factory(),
            'debit_amount' => 0,
            'credit_amount' => 0,
            'description' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the item is a debit.
     */
    public function debit(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'debit_amount' => $amount ?? $this->faker->randomFloat(2, 10, 1000),
            'credit_amount' => 0,
        ]);
    }

    /**
     * Indicate that the item is a credit.
     */
    public function credit(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'debit_amount' => 0,
            'credit_amount' => $amount ?? $this->faker->randomFloat(2, 10, 1000),
        ]);
    }
}
