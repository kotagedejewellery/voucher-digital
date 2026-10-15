<?php

namespace Database\Seeders;

use App\Models\VoucherPolicy;
use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (!VoucherPolicy::exists()) {
            $policy=VoucherPolicy::create(['price'=>100000,'minimum_order'=>8000000,'valid_days'=>365,'tiers'=>[
                ['end'=>30,'discount'=>800000],['end'=>60,'discount'=>500000],['end'=>90,'discount'=>300000],['end'=>365,'discount'=>100000],
            ]]);
            Setting::put('policy_id',(string)$policy->id);
        }
    }
}
