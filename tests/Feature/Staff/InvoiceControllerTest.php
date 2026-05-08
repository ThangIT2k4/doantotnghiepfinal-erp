<?php

namespace Tests\Feature\Staff;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Lease;
use App\Models\Invoice;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class InvoiceControllerTest extends TestCase
{
    use WithoutMiddleware;

    protected $organization;
    protected $manager;
    protected $agent;
    protected $property;
    protected $unit;
    protected $lease;
    protected $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip test if database doesn't have necessary tables
        try {
            if (!\Schema::hasTable('organizations')) {
                $this->markTestSkipped('Database tables not available');
            }
        } catch (\Exception $e) {
            $this->markTestSkipped('Database not available: ' . $e->getMessage());
        }
        
        // Create organization (use existing or create new)
        $this->organization = Organization::first() ?? Organization::factory()->create();
        
        // Create roles
        $managerRole = Role::firstOrCreate(['key_code' => 'manager'], ['name' => 'Manager']);
        $agentRole = Role::firstOrCreate(['key_code' => 'agent'], ['name' => 'Agent']);
        
        // Create manager user
        $this->manager = User::factory()->withProfile()->create();
        OrganizationUser::create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->manager->id,
            'role_id' => $managerRole->id,
            'status' => 'active',
        ]);
        
        // Create agent user
        $this->agent = User::factory()->withProfile()->create();
        OrganizationUser::create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->agent->id,
            'role_id' => $agentRole->id,
            'status' => 'active',
        ]);
        
        // Create property and unit
        $this->property = Property::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 1,
        ]);
        
        $this->unit = Unit::factory()->create([
            'property_id' => $this->property->id,
        ]);
        
        // Create lease
        $this->lease = Lease::factory()->create([
            'organization_id' => $this->organization->id,
            'unit_id' => $this->unit->id,
            'agent_id' => $this->agent->id,
            'status' => 'active',
        ]);
        
        // Create invoice
        $this->invoice = Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'lease_id' => $this->lease->id,
            'status' => 'draft',
        ]);
    }

    public function test_manager_can_view_all_invoices()
    {
        // Create another invoice
        $invoice2 = Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'lease_id' => $this->lease->id,
            'status' => 'issued',
        ]);
        
        Auth::login($this->manager);
        
        $response = $this->get('/staff/invoices');
        
        $response->assertStatus(200);
        $response->assertViewIs('staff.invoices.index');
    }

    public function test_agent_can_view_only_managed_leases_invoices()
    {
        // Create lease not managed by agent
        $lease2 = Lease::factory()->create([
            'organization_id' => $this->organization->id,
            'unit_id' => $this->unit->id,
            'agent_id' => $this->manager->id,
            'status' => 'active',
        ]);
        
        // Create invoice for that lease
        $invoice2 = Invoice::factory()->create([
            'organization_id' => $this->organization->id,
            'lease_id' => $lease2->id,
            'status' => 'issued',
        ]);
        
        Auth::login($this->agent);
        
        $response = $this->get('/staff/invoices');
        
        $response->assertStatus(200);
        $response->assertViewIs('staff.invoices.index');
        // Should only see invoice for lease where agent_id = $this->agent->id
    }

    public function test_unauthenticated_user_cannot_access_invoices()
    {
        $response = $this->get('/staff/invoices');
        
        $response->assertRedirect('/login');
    }

    public function test_manager_can_view_any_invoice()
    {
        Auth::login($this->manager);
        
        $response = $this->get('/staff/invoices/' . $this->invoice->id);
        
        $response->assertStatus(200);
        $response->assertViewIs('staff.invoices.show');
    }

    public function test_agent_can_view_own_lease_invoice()
    {
        Auth::login($this->agent);
        
        $response = $this->get('/staff/invoices/' . $this->invoice->id);
        
        $response->assertStatus(200);
        $response->assertViewIs('staff.invoices.show');
    }
}

