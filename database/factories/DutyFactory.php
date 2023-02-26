<?php

namespace Database\Factories;

use App\Models\Duty;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class DutyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Duty::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->jobTitle(),
            // generate html description
            'description' => '<p>'.$this->faker->paragraph(1).'</p><p>'.$this->faker->paragraph(1).'</p>',
            'institution_id' => Institution::inRandomOrder()->select('id')->first()->id,
            'email' => $this->faker->safeEmail(),
        ];
    }
}
