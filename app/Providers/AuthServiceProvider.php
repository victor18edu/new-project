<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Company;
use App\Models\Contact;
use App\Models\CustomerCategory;
use App\Models\LeadSource;
use App\Models\LostReason;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\Segment;
use App\Models\Service;
use App\Models\ServiceCategory;
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
            Activity::class,
            ActivityType::class,
            Company::class,
            Contact::class,
            CustomerCategory::class,
            LeadSource::class,
            LostReason::class,
            Opportunity::class,
            OpportunityStageHistory::class,
            Pipeline::class,
            PipelineStage::class,
            Proposal::class,
            ProposalItem::class,
            Segment::class,
            Service::class,
            ServiceCategory::class,
        ];

        foreach ($crmModels as $model) {
            Gate::policy($model, CrmPolicy::class);
        }

        Gate::policy(Contact::class, WhatsAppOperatorPolicy::class);
        Gate::policy(WhatsAppAttendance::class, WhatsAppAttendancePolicy::class);
        Gate::policy(WhatsAppMessage::class, WhatsAppMessagePolicy::class);
    }
}
