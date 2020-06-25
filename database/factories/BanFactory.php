<?php

/** @var Factory $factory */

use App\Ban;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Facades\Date;

$factory->define(Ban::class, function (Faker $faker) {
    return [
        'ban_hash'  => $faker->uuid,
        'reason'    => $faker->sentence,
        'timestamp' => Date::now(),
        'expire'    => Date::now()->addMonth(),
    ];
});
