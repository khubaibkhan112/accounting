<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => $this->faker->unique()->numerify('EMP####'),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'date_of_birth' => $this->faker->optional()->date('Y-m-d', '-18 years'),
            'gender' => $this->faker->optional()->randomElement(['male', 'female', 'other']),
            'address' => $this->faker->optional()->streetAddress(),
            'city' => $this->faker->optional()->city(),
            'state' => $this->faker->optional()->state(),
            'postal_code' => $this->faker->optional()->postcode(),
            'country' => $this->faker->optional()->country(),
            'position' => $this->faker->optional()->jobTitle(),
            'department' => $this->faker->optional()->randomElement(['Sales', 'IT', 'HR', 'Finance', 'Operations']),
            'hire_date' => $this->faker->optional()->date('Y-m-d', '-5 years'),
            'termination_date' => null,
            'employment_type' => $this->faker->randomElement(['full-time', 'part-time', 'contract', 'intern']),
            'salary' => $this->faker->optional()->randomFloat(2, 30000, 150000),
            'emergency_contact_name' => $this->faker->optional()->name(),
            'emergency_contact_phone' => $this->faker->optional()->phoneNumber(),
            'notes' => $this->faker->optional()->sentence(),
            'user_id' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the employee is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the employee has a user account.
     */
    public function withUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory(),
        ]);
    }
}
