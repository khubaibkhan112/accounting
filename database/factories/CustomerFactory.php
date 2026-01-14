<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $customerType = $this->faker->randomElement(['individual', 'business']);
        $isBusiness = $customerType === 'business';
        
        return [
            'customer_code' => $this->faker->unique()->numerify('CUST####'),
            'customer_type' => $customerType,
            'company_name' => $isBusiness ? $this->faker->company() : null,
            'first_name' => !$isBusiness ? $this->faker->firstName() : null,
            'last_name' => !$isBusiness ? $this->faker->lastName() : null,
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'mobile' => $this->faker->optional()->phoneNumber(),
            'billing_address' => $this->faker->streetAddress(),
            'billing_city' => $this->faker->city(),
            'billing_state' => $this->faker->state(),
            'billing_postal_code' => $this->faker->postcode(),
            'billing_country' => $this->faker->country(),
            'shipping_address' => $this->faker->optional()->streetAddress(),
            'shipping_city' => $this->faker->optional()->city(),
            'shipping_state' => $this->faker->optional()->state(),
            'shipping_postal_code' => $this->faker->optional()->postcode(),
            'shipping_country' => $this->faker->optional()->country(),
            'tax_id' => $this->faker->optional()->numerify('TAX####'),
            'payment_terms' => $this->faker->randomElement(['cash', 'net_15', 'net_30', 'net_60', 'net_90', 'custom']),
            'credit_limit' => $this->faker->optional()->randomFloat(2, 1000, 50000),
            'opening_balance' => 0,
            'current_balance' => 0,
            'notes' => $this->faker->optional()->sentence(),
            'assigned_to' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the customer is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the customer is an individual.
     */
    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'individual',
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'company_name' => null,
        ]);
    }

    /**
     * Indicate that the customer is a business.
     */
    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'business',
            'company_name' => $this->faker->company(),
            'first_name' => null,
            'last_name' => null,
        ]);
    }
}
