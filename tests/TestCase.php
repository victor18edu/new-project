<?php

namespace Tests;

use App\Models\Activity;
use App\Models\Company;
use App\Models\LeadSource;
use App\Models\Opportunity;
use App\Models\Proposal;
use App\Models\Segment;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createCompany(array $attributes = []): Company
    {
        return Company::create(array_merge([
            'corporate_name' => fake()->company(),
            'trade_name' => fake()->company(),
            'tax_id' => fake()->unique()->numerify('################'),
            'segment_id' => Segment::factory()->create()->id,
            'owner_id' => User::factory()->create()->id,
            'city' => 'São Paulo',
            'state' => 'SP',
            'status' => 'prospecting',
            'temperature' => 'cold',
        ], $attributes));
    }

    protected function createLeadSource(array $attributes = []): LeadSource
    {
        return LeadSource::create(array_merge([
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'channel_type' => 'platform',
            'is_active' => true,
        ], $attributes));
    }

    protected function createActivity(array $attributes = []): Activity
    {
        return Activity::create(array_merge([
            'title' => fake()->sentence(3),
            'status' => 'pending',
            'assigned_to' => User::factory()->create()->id,
            'created_by' => User::factory()->create()->id,
            'due_date' => now()->addDay(),
        ], $attributes));
    }

    protected function createOpportunity(array $attributes = []): Opportunity
    {
        return Opportunity::create(array_merge([
            'title' => fake()->sentence(3),
            'owner_id' => User::factory()->create()->id,
            'status' => 'open',
        ], $attributes));
    }

    protected function createProposal(array $attributes = []): Proposal
    {
        return Proposal::create(array_merge([
            'owner_id' => User::factory()->create()->id,
            'status' => 'draft',
            'valid_until' => now()->addDays(30),
        ], $attributes));
    }
}
