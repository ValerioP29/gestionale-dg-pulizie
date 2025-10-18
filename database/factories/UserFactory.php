<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        $first = $this->faker->firstName();
        $last  = $this->faker->lastName();

        return [
            'first_name' => $first,
            'last_name'  => $last,
            'name'       => "$first $last",
            'email'      => $this->faker->unique()->safeEmail(),
            'phone'      => $this->faker->numerify('3#########'),
            'active'     => true,
            'password'   => Hash::make('password'),
        ];
    }
}
