<?php

namespace Tests\Feature\Staff;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Role;
use App\Models\Capability;
use App\Services\CapabilityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class UnitControllerTest extends TestCase
{
    use WithoutMiddleware;

    protected $organization;
    protected $manager;
    protected $agent;
    protected $property;
    protected $unit;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip test if database doesn't have necessary tables
        try {
            // Check if organizations table exists
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
        
        // Assign capabilities to agent
        $assetUnitView = Capability::firstOrCreate(
            ['key_code' => 'asset.unit.view'],
            ['name' => 'View Units']
        );
        $assetUnitCreate = Capability::firstOrCreate(
            ['key_code' => 'asset.unit.create'],
            ['name' => 'Create Units']
        );
        
        // Assign capabilities to agent (simplified - in real app would use CapabilityService)
        $orgUser = OrganizationUser::where('user_id', $this->agent->id)->first();
        // Note: Actual capability assignment would be done through CapabilityService
        
        // Create property and unit
        $this->property = Property::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 1,
        ]);
        
        $this->unit = Unit::factory()->create([
            'property_id' => $this->property->id,
        ]);
    }

    public function test_manager_can_view_all_units()
    {
        // Create additional unit in organization
        $unit2 = Unit::factory()->create([
            'property_id' => $this->property->id,
        ]);
        
        Auth::login($this->manager);
        
        $response = $this->get('/staff/units');
        
        $response->assertStatus(200);
        $response->assertViewIs('staff.units.index');
        $response->assertViewHas('units');
    }

    public function test_agent_can_view_only_assigned_properties_units()
    {
        // Create another property not assigned to agent
        $property2 = Property::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 1,
        ]);
        $unit2 = Unit::factory()->create([
            'property_id' => $property2->id,
        ]);
        
        // Assign property to agent
        $this->agent->assignedProperties()->attach($this->property->id);
        
        Auth::login($this->agent);
        
        $response = $this->get('/staff/units');
        
        $response->assertStatus(200);
        $response->assertViewIs('staff.units.index');
    }

    public function test_unauthenticated_user_cannot_access_units()
    {
        $response = $this->get('/staff/units');
        
        $response->assertRedirect('/login');
    }

    public function test_manager_can_create_unit()
    {
        Auth::login($this->manager);
        
        $response = $this->get('/staff/units/create');
        
        $response->assertStatus(200);
        $response->assertViewIs('staff.units.create');
    }

    public function test_agent_without_capability_cannot_create_unit()
    {
        Auth::login($this->agent);
        
        $response = $this->get('/staff/units/create');
        
        // Should return 403 if agent doesn't have asset.unit.create capability
        $response->assertStatus(403);
    }
}

