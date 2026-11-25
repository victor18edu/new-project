<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\User;
use App\Models\WhatsAppAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WhatsAppAttendance> */
class WhatsAppAttendanceFactory extends Factory
{
    protected $model = WhatsAppAttendance::class;

    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'operator_id' => User::factory(),
            'assigned_by_id' => null,
            'status' => WhatsAppAttendance::STATUS_OPEN,
            'started_at' => fake()->dateTimeBetween('-2 days'),
            'ended_at' => null,
            'outcome_notes' => null,
        ];
    }
}
