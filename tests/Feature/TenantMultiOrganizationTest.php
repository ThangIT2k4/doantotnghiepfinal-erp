<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Lease;
use App\Models\Invoice;
use App\Models\Unit;
use App\Models\Property;
use App\Models\LeaseResident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

/**
 * Test suite cho tenant multi-organization logic
 * 
 * Test case: Tenant thuộc nhiều organizations và phải xem được TẤT CẢ dữ liệu
 */
class TenantMultiOrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $org1;
    protected $org2;
    protected $tenantRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant role
        $this->tenantRole = Role::create([
            'name' => 'Tenant',
            'key_code' => 'tenant',
            'description' => 'Tenant role'
        ]);

        // Create 2 organizations
        $this->org1 = Organization::create([
            'name' => 'Organization 1',
            'status' => 'active'
        ]);

        $this->org2 = Organization::create([
            'name' => 'Organization 2', 
            'status' => 'active'
        ]);

        // Create tenant user
        $this->tenant = User::create([
            'email' => 'tenant@test.com',
            'phone' => '0123456789',
            'password_hash' => bcrypt('password'),
            'status' => 1
        ]);

        // Attach tenant to both organizations
        $this->tenant->organizations()->attach($this->org1->id, [
            'role_id' => $this->tenantRole->id,
            'status' => 'active'
        ]);

        $this->tenant->organizations()->attach($this->org2->id, [
            'role_id' => $this->tenantRole->id,
            'status' => 'active'
        ]);
    }

    /**
     * Test: Tenant xem được leases từ TẤT CẢ organizations
     */
    public function test_tenant_can_see_leases_from_all_organizations()
    {
        // Create properties and units for both orgs
        $property1 = Property::create([
            'organization_id' => $this->org1->id,
            'name' => 'Property 1',
            'status' => 'available'
        ]);

        $unit1 = Unit::create([
            'property_id' => $property1->id,
            'code' => 'UNIT-001',
            'status' => 'occupied'
        ]);

        $property2 = Property::create([
            'organization_id' => $this->org2->id,
            'name' => 'Property 2',
            'status' => 'available'
        ]);

        $unit2 = Unit::create([
            'property_id' => $property2->id,
            'code' => 'UNIT-002',
            'status' => 'occupied'
        ]);

        // Create lease in Org 1
        $lease1 = Lease::create([
            'organization_id' => $this->org1->id,
            'unit_id' => $unit1->id,
            'tenant_id' => $this->tenant->id,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'rent_amount' => 5000000,
            'deposit_amount' => 10000000,
            'status' => 'active'
        ]);

        // Create lease in Org 2
        $lease2 = Lease::create([
            'organization_id' => $this->org2->id,
            'unit_id' => $unit2->id,
            'tenant_id' => $this->tenant->id,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'rent_amount' => 6000000,
            'deposit_amount' => 12000000,
            'status' => 'active'
        ]);

        // Login as tenant
        Auth::login($this->tenant);

        // Test: getAccessibleLeaseIds should return both leases
        $accessibleIds = Lease::getAccessibleLeaseIds($this->tenant->id);
        
        $this->assertCount(2, $accessibleIds);
        $this->assertTrue($accessibleIds->contains($lease1->id));
        $this->assertTrue($accessibleIds->contains($lease2->id));

        // Test: Lease query should return both leases
        $leases = Lease::all();
        
        $this->assertCount(2, $leases);
    }

    /**
     * Test: Tenant xem được invoices từ TẤT CẢ organizations
     */
    public function test_tenant_can_see_invoices_from_all_organizations()
    {
        // Setup leases (same as above test)
        $property1 = Property::create([
            'organization_id' => $this->org1->id,
            'name' => 'Property 1',
            'status' => 'available'
        ]);

        $unit1 = Unit::create([
            'property_id' => $property1->id,
            'code' => 'UNIT-001',
            'status' => 'occupied'
        ]);

        $lease1 = Lease::create([
            'organization_id' => $this->org1->id,
            'unit_id' => $unit1->id,
            'tenant_id' => $this->tenant->id,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'rent_amount' => 5000000,
            'deposit_amount' => 10000000,
            'status' => 'active'
        ]);

        $property2 = Property::create([
            'organization_id' => $this->org2->id,
            'name' => 'Property 2',
            'status' => 'available'
        ]);

        $unit2 = Unit::create([
            'property_id' => $property2->id,
            'code' => 'UNIT-002',
            'status' => 'occupied'
        ]);

        $lease2 = Lease::create([
            'organization_id' => $this->org2->id,
            'unit_id' => $unit2->id,
            'tenant_id' => $this->tenant->id,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'rent_amount' => 6000000,
            'deposit_amount' => 12000000,
            'status' => 'active'
        ]);

        // Create invoices for both leases
        $invoice1 = Invoice::create([
            'organization_id' => $this->org1->id,
            'lease_id' => $lease1->id,
            'invoice_no' => 'INV-001',
            'issue_date' => now(),
            'due_date' => now()->addDays(7),
            'status' => 'issued',
            'total_amount' => 5000000
        ]);

        $invoice2 = Invoice::create([
            'organization_id' => $this->org2->id,
            'lease_id' => $lease2->id,
            'invoice_no' => 'INV-002',
            'issue_date' => now(),
            'due_date' => now()->addDays(7),
            'status' => 'issued',
            'total_amount' => 6000000
        ]);

        // Login as tenant
        Auth::login($this->tenant);

        // Test: Invoice query should return both invoices
        $invoices = Invoice::all();
        
        $this->assertCount(2, $invoices);
    }

    /**
     * Test: Tenant as resident cũng xem được lease
     */
    public function test_tenant_as_resident_can_see_lease()
    {
        // Create another user as main tenant
        $mainTenant = User::create([
            'email' => 'maintenant@test.com',
            'phone' => '0987654321',
            'password_hash' => bcrypt('password'),
            'status' => 1
        ]);

        // Setup lease with main tenant
        $property = Property::create([
            'organization_id' => $this->org1->id,
            'name' => 'Property 1',
            'status' => 'available'
        ]);

        $unit = Unit::create([
            'property_id' => $property->id,
            'code' => 'UNIT-001',
            'status' => 'occupied'
        ]);

        $lease = Lease::create([
            'organization_id' => $this->org1->id,
            'unit_id' => $unit->id,
            'tenant_id' => $mainTenant->id, // Main tenant
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'rent_amount' => 5000000,
            'deposit_amount' => 10000000,
            'status' => 'active'
        ]);

        // Add our tenant as resident
        LeaseResident::create([
            'lease_id' => $lease->id,
            'user_id' => $this->tenant->id,
            'full_name' => 'Tenant Resident',
            'relationship' => 'Bạn bè'
        ]);

        // Login as tenant (resident)
        Auth::login($this->tenant);

        // Test: Tenant as resident should see the lease
        $accessibleIds = Lease::getAccessibleLeaseIds($this->tenant->id);
        
        $this->assertCount(1, $accessibleIds);
        $this->assertTrue($accessibleIds->contains($lease->id));
    }

    /**
     * Test: getAllOrganizationIds() method
     */
    public function test_get_all_organization_ids()
    {
        $orgIds = $this->tenant->getAllOrganizationIds();

        $this->assertCount(2, $orgIds);
        $this->assertContains($this->org1->id, $orgIds);
        $this->assertContains($this->org2->id, $orgIds);
    }
}

