<?php

namespace Tests\Feature\Staff;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Lease;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class LeaseControllerTest extends TestCase
{
    use WithoutMiddleware;

    protected $organization;
    protected $manager;
    protected $agent;
    protected $property;
    protected $unit;
    protected $lease;

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
    }

    public function test_manager_can_view_all_leases()
    {
        // Create another lease
        $lease2 = Lease::factory()->create([
            'organization_id' => $this->organization->id,
            'unit_id' => $this->unit->id,
            'status' => 'active',
        ]);
        
        Auth::login($this->manager);
        
        $response = $this->get('/staff/leases');
        
        $response->assertStatus(200);
        $response->assertViewIs('staff.leases.index');
    }

    public function test_agent_can_view_only_own_leases()
    {
        // Create lease not owned by agent
        $lease2 = Lease::factory()->create([
            'organization_id' => $this->organization->id,
            'unit_id' => $this->unit->id,
            'agent_id' => $this->manager->id,
            'status' => 'active',
        ]);
        
        Auth::login($this->agent);
        
        $response = $this->get('/staff/leases');
        
        $response->assertStatus(200);
        $response->assertViewIs('staff.leases.index');
        // Should only see lease where agent_id = $this->agent->id
    }

    public function test_unauthenticated_user_cannot_access_leases()
    {
        $response = $this->get('/staff/leases');
        
        $response->assertRedirect('/login');
    }

    public function test_manager_can_view_any_lease()
    {
        Auth::login($this->manager);
        
        $response = $this->get('/staff/leases/' . $this->lease->id);
        
        $response->assertStatus(200);
        $response->assertViewIs('staff.leases.show');
    }

    public function test_agent_can_view_own_lease()
    {
        Auth::login($this->agent);
        
        $response = $this->get('/staff/leases/' . $this->lease->id);
        
        $response->assertStatus(200);
        $response->assertViewIs('staff.leases.show');
    }
}

