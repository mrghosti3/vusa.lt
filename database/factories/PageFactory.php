<?php

namespace Database\Factories;

use App\Models\Padalinys;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

class PageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Page::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public $inc = 0;

    public function incrementAndReturn()
    {
        global $inc;

        $inc = $inc + 1;

        return strval($inc);
    }

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(),
            'permalink' => 'page'.$this->incrementAndReturn(),
            'category_id' => $this->faker->numberBetween(1, 3),
            'padalinys_id' => Padalinys::factory(),
        ];
    }
}
