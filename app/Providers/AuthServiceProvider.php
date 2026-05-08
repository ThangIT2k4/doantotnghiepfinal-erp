<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Payment;
use App\Policies\PaymentPolicy;
use App\Services\CapabilityService;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Payment::class => PaymentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Generic capability gate: Gate::allows('capability', ['unit.update', $orgId])
        Gate::define('capability', function ($user, string $capability, ?int $orgId = null) {
            return CapabilityService::userHas($user->getKey(), $orgId, $capability);
        });
    }
}
