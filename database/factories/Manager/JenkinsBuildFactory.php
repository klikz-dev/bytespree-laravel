<?php

namespace Database\Factories\Manager;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JenkinsBuild>
 */
class JenkinsBuildFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'job_path'         => $this->faker->word,
            'jenkins_home'     => $this->faker->word,
            'jenkins_build_id' => $this->faker->numberBetween(1, 100),
            'started_at'       => $this->faker->dateTime(),
            'finished_at'      => $this->faker->dateTime(),
            'parameters'       => [
                $this->faker->word,
                $this->faker->word,
                $this->faker->word,
                $this->faker->word,
                $this->faker->numberBetween(1, 10),
            ],
            'result' => $this->faker->randomElement(['SUCCESS', 'FAILURE', 'ABORTED', NULL]),
        ];
    }
}
