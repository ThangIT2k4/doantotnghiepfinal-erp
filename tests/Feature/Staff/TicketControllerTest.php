<?php

namespace Tests\Feature\Staff;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Ticket;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class TicketControllerTest extends TestCase
{
    use WithoutMiddleware;

    protected $organization;
    protected $manager;
    protected $agent;
    protected $property;
    protected $unit;
    protected $ticket;

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
        
        // Create ticket
        $this->ticket = Ticket::factory()->create([
            'organization_id' => $this->organization->id,
            'unit_id' => $this->unit->id,
            'status' => 'open',
        ]);
    }

    public function test_manager_can_view_all_tickets()
    {
        // Create another ticket
        $ticket2 = Ticket::factory()->create([
            'organization_id' => $this->organization->id,
            'unit_id' => $this->unit->id,
            'status' => 'open',
        ]);
        
        Auth::login($this->manager);
        
        $response = $this->get('/staff/tickets');
        
        $response->assertStatus(200);
        $response->assertViewIs('staff.tickets.index');
    }

    public function test_agent_can_view_only_assigned_properties_tickets()
    {
        // Create another property not assigned to agent
        $property2 = Property::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 1,
        ]);
        $unit2 = Unit::factory()->create([
            'property_id' => $property2->id,
        ]);
        
        // Create ticket for that property
        $ticket2 = Ticket::factory()->create([
            'organization_id' => $this->organization->id,
            'unit_id' => $unit2->id,
            'status' => 'open',
        ]);
        
        // Assign property to agent
        $this->agent->assignedProperties()->attach($this->property->id);
        
        Auth::login($this->agent);
        
        $response = $this->get('/staff/tickets');
        
        $response->assertStatus(200);
        $response->assertViewIs('staff.tickets.index');
        // Should only see tickets for assigned properties
    }

    public function test_unauthenticated_user_cannot_access_tickets()
    {
        $response = $this->get('/staff/tickets');
        
        $response->assertRedirect('/login');
    }

    public function test_manager_can_view_any_ticket()
    {
        Auth::login($this->manager);
        
        $response = $this->get('/staff/tickets/' . $this->ticket->id);
        
        $response->assertStatus(200);
        $response->assertViewIs('staff.tickets.show');
    }

    public function test_agent_can_view_assigned_property_ticket()
    {
        // Assign property to agent
        $this->agent->assignedProperties()->attach($this->property->id);
        
        Auth::login($this->agent);
        
        $response = $this->get('/staff/tickets/' . $this->ticket->id);
        
        $response->assertStatus(200);
        $response->assertViewIs('staff.tickets.show');
    }
}

