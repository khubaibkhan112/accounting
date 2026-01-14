<?php

namespace Database\Factories;

use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = JournalEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entry_date' => $this->faker->date(),
            'description' => $this->faker->sentence(),
            'reference_number' => $this->faker->optional()->numerify('JE####'),
            'total_debit' => 0,
            'total_credit' => 0,
            'created_by' => User::factory(),
        ];
    }
}
