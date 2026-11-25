<?php

namespace App\Providers;

use App\Models\CustomerCategory;
use App\Models\User;
use App\Models\WhatsAppAttendance;
use App\Models\WhatsAppMessage;
use App\Policies\CrmPolicy;
use App\Policies\UserPolicy;
use App\Policies\WhatsAppAttendancePolicy;
use App\Policies\WhatsAppMessagePolicy;
use App\Policies\WhatsAppOperatorPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);

        $crmModels = [
            \App\Models\Activity::class,
            \App\Models\ActivityType::class,
            \App\Models\Company::class,
            \App\Models\Contact::class,
            \App\Models\LeadSource::class,
            \App\Models\LostReason::class,
            \App\Models\Opportunity::class,
            \App\Models\OpportunityStageHistory::class,
            \App\Models\Pipeline::class,
            \App\Models\PipelineStage::class,
            \App\Models\Proposal::class,
            \App\Models\ProposalItem::class,
            \App\Models\Segment::class,
            \App\Models\Service::class,
            \App\Models\ServiceCategory::class,
            CustomerCategory::class,
        ];

        foreach ($crmModels as $model) {
            Gate::policy($model, CrmPolicy::class);
        }
        Gate::policy(Contact::class, WhatsAppOperatorPolicy::class);
        Gate::policy(WhatsAppAttendance::class, WhatsAppAttendancePolicy::class);
        Gate::policy(WhatsAppMessage::class, WhatsAppMessagePolicy::class);
    }
}