<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Location2025;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PropertyController extends Controller
{
    /**
     * Display a listing of properties for public access
     */
    public function index(Request $request)
    {
        try {
            // Base query for properties (bypass organization scope for public access)
            $query = Property::withoutGlobalScope('organization')
                ->whereNull('deleted_at') // Ensure not soft deleted
                ->with(['location2025', 'propertyType', 'units' => function($query) {
                    $query->where('status', 'available'); // Only load available units
                }])
                ->active() // Only active properties
                ->whereHas('units', function($query) {
                    $query->where('status', 'available'); // Only properties that have available units
                });

            // Apply filters
            if ($request->filled('location')) {
                $query->whereHas('location2025', function($q) use ($request) {
                    $q->where('city', 'like', '%' . $request->location . '%')
                      ->orWhere('ward', 'like', '%' . $request->location . '%')
                      ->orWhere('street', 'like', '%' . $request->location . '%');
                });
            }

            if ($request->filled('property_type')) {
                $query->where('property_type_id', $request->property_type);
            }

            if ($request->filled('min_price')) {
                $query->whereHas('units', function($q) use ($request) {
                    $q->where('rental_price', '>=', $request->min_price);
                });
            }

            if ($request->filled('max_price')) {
                $query->whereHas('units', function($q) use ($request) {
                    $q->where('rental_price', '<=', $request->max_price);
                });
            }

            if ($request->filled('min_area')) {
                $query->whereHas('units', function($q) use ($request) {
                    $q->where('area', '>=', $request->min_area);
                });
            }

            if ($request->filled('max_area')) {
                $query->whereHas('units', function($q) use ($request) {
                    $q->where('area', '<=', $request->max_area);
                });
            }

            // Order by creation date (newest first)
            $properties = $query->orderBy('created_at', 'desc')->paginate(12);

            // Get filter options for the form
            $locations = $this->getLocations();
            $propertyTypes = $this->getPropertyTypes();
            $priceRanges = $this->getPriceRanges();
            $areaRanges = $this->getAreaRanges();

            return view('property.index', compact(
                'properties', 
                'locations', 
                'propertyTypes', 
                'priceRanges', 
                'areaRanges'
            ));

        } catch (\Exception $e) {
            Log::error('Error in PropertyController@index: ' . $e->getMessage());
            
            // Create empty paginator for error case
            $emptyProperties = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]), // items
                0, // total
                12, // perPage
                1, // currentPage
                ['path' => request()->url(), 'pageName' => 'page']
            );
            
            return view('property.index', [
                'properties' => $emptyProperties,
                'locations' => collect([]),
                'propertyTypes' => [],
                'priceRanges' => [],
                'areaRanges' => []
            ]);
        }
    }

    /**
     * Show property detail with comprehensive information
     */
    public function show($id)
    {
        try {
            // Get property with all related data
            $property = Property::withoutGlobalScope('organization')
                ->whereNull('deleted_at')
                ->with([
                    'location2025', 
                    'propertyType', 
                    'masterLeases.landlord',
                    'units' => function($query) {
                        $query->with('amenities');
                    }
                ])
                ->find($id);

            if (!$property) {
                abort(404, 'Property not found');
            }

            // Get available units
            $availableUnits = $property->units->where('status', 'available');
            $allUnits = $property->units;

            // Get aggregated amenities from all units
            $allAmenities = $allUnits->flatMap(function($unit) {
                return $unit->amenities;
            })->unique('id');

            // Calculate property stats
            $stats = [
                'total_units' => $allUnits->count(),
                'available_units' => $availableUnits->count(),
                'occupied_units' => $allUnits->where('status', 'occupied')->count(),
                'min_price' => $availableUnits->min('base_rent'),
                'max_price' => $availableUnits->max('base_rent'),
                'min_area' => $availableUnits->min('area_m2'),
                'max_area' => $availableUnits->max('area_m2'),
            ];

            // Get similar properties (same type)
            $similarProperties = Property::withoutGlobalScope('organization')
                ->whereNull('deleted_at')
                ->where('id', '!=', $id)
                ->where('property_type_id', $property->property_type_id)
                ->with(['location2025', 'propertyType', 'units' => function($query) {
                    $query->where('status', 'available');
                }])
                ->active()
                ->whereHas('units', function($query) {
                    $query->where('status', 'available');
                })
                ->limit(6)
                ->get();

            // Get agent information from properties_user table
            $agent = null;
            $assignedUser = $property->assignedUsers()->first();
            if ($assignedUser) {
                $agent = $assignedUser;
            } else {
                // Fallback to landlord from master lease if no assigned user
                $landlord = $property->getCurrentLandlord();
                if ($landlord) {
                    $agent = $landlord;
                }
            }

            return view('property.show', compact(
                'property', 
                'allAmenities', 
                'availableUnits', 
                'allUnits',
                'stats',
                'similarProperties',
                'agent'
            ));

        } catch (\Exception $e) {
            Log::error('Error in PropertyController@show: ' . $e->getMessage());
            abort(404, 'Property not found');
        }
    }

    /**
     * Get locations for filter dropdown
     */
    private function getLocations()
    {
        return Location2025::whereHas('properties', function($query) {
            $query->withoutGlobalScope('organization')
                  ->whereNull('deleted_at')
                  ->active()
                  ->whereHas('units', function($subQuery) {
                      $subQuery->where('status', 'available'); // Only properties with available units
                  });
        })
        ->select('city', 'ward', 'street')
        ->distinct()
        ->orderBy('city')
        ->orderBy('ward')
        ->get();
    }

    /**
     * Get property types for filter dropdown
     */
    private function getPropertyTypes()
    {
        return PropertyType::where('status', 1)
            ->whereNull('deleted_at')
            ->whereHas('properties', function($query) {
                $query->withoutGlobalScope('organization')
                      ->whereNull('deleted_at')
                      ->active()
                      ->whereHas('units', function($subQuery) {
                          $subQuery->where('status', 'available'); // Only properties with available units
                      });
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Get price ranges for filter dropdown
     */
    private function getPriceRanges()
    {
        try {
            $minPrice = Unit::whereHas('property', function($query) {
                $query->withoutGlobalScope('organization')
                      ->whereNull('deleted_at')
                      ->active()
                      ->whereHas('units'); // Only from properties with units
            })->where('status', 'available')->min('base_rent');

            $maxPrice = Unit::whereHas('property', function($query) {
                $query->withoutGlobalScope('organization')
                      ->whereNull('deleted_at')
                      ->active()
                      ->whereHas('units'); // Only from properties with units
            })->where('status', 'available')->max('base_rent');

            $ranges = [];
            if ($minPrice && $maxPrice && $minPrice > 0 && $maxPrice > 0) {
                $step = ($maxPrice - $minPrice) / 5;
                for ($i = 0; $i < 5; $i++) {
                    $start = $minPrice + ($i * $step);
                    $end = $minPrice + (($i + 1) * $step);
                    $ranges[] = [
                        'min' => round($start),
                        'max' => round($end),
                        'label' => number_format($start) . ' - ' . number_format($end) . ' VNĐ'
                    ];
                }
            } else {
                // Default ranges if no data
                $ranges = [
                    ['min' => 0, 'max' => 2000000, 'label' => 'Dưới 2 triệu'],
                    ['min' => 2000000, 'max' => 5000000, 'label' => '2 - 5 triệu'],
                    ['min' => 5000000, 'max' => 10000000, 'label' => '5 - 10 triệu'],
                    ['min' => 10000000, 'max' => 20000000, 'label' => '10 - 20 triệu'],
                    ['min' => 20000000, 'max' => 999999999, 'label' => 'Trên 20 triệu']
                ];
            }

            return $ranges;
        } catch (\Exception $e) {
            Log::error('Error in getPriceRanges: ' . $e->getMessage());
            return [
                ['min' => 0, 'max' => 2000000, 'label' => 'Dưới 2 triệu'],
                ['min' => 2000000, 'max' => 5000000, 'label' => '2 - 5 triệu'],
                ['min' => 5000000, 'max' => 10000000, 'label' => '5 - 10 triệu'],
                ['min' => 10000000, 'max' => 20000000, 'label' => '10 - 20 triệu'],
                ['min' => 20000000, 'max' => 999999999, 'label' => 'Trên 20 triệu']
            ];
        }
    }

    /**
     * Get area ranges for filter dropdown
     */
    private function getAreaRanges()
    {
        try {
            $minArea = Unit::whereHas('property', function($query) {
                $query->withoutGlobalScope('organization')
                      ->whereNull('deleted_at')
                      ->active()
                      ->whereHas('units'); // Only from properties with units
            })->where('status', 'available')->min('area_m2');

            $maxArea = Unit::whereHas('property', function($query) {
                $query->withoutGlobalScope('organization')
                      ->whereNull('deleted_at')
                      ->active()
                      ->whereHas('units'); // Only from properties with units
            })->where('status', 'available')->max('area_m2');

            $ranges = [];
            if ($minArea && $maxArea && $minArea > 0 && $maxArea > 0) {
                $step = ($maxArea - $minArea) / 5;
                for ($i = 0; $i < 5; $i++) {
                    $start = $minArea + ($i * $step);
                    $end = $minArea + (($i + 1) * $step);
                    $ranges[] = [
                        'min' => round($start),
                        'max' => round($end),
                        'label' => round($start) . ' - ' . round($end) . ' m²'
                    ];
                }
            } else {
                // Default ranges if no data
                $ranges = [
                    ['min' => 0, 'max' => 20, 'label' => 'Dưới 20m²'],
                    ['min' => 20, 'max' => 30, 'label' => '20 - 30m²'],
                    ['min' => 30, 'max' => 50, 'label' => '30 - 50m²'],
                    ['min' => 50, 'max' => 100, 'label' => '50 - 100m²'],
                    ['min' => 100, 'max' => 999999, 'label' => 'Trên 100m²']
                ];
            }

            return $ranges;
        } catch (\Exception $e) {
            Log::error('Error in getAreaRanges: ' . $e->getMessage());
            return [
                ['min' => 0, 'max' => 20, 'label' => 'Dưới 20m²'],
                ['min' => 20, 'max' => 30, 'label' => '20 - 30m²'],
                ['min' => 30, 'max' => 50, 'label' => '30 - 50m²'],
                ['min' => 50, 'max' => 100, 'label' => '50 - 100m²'],
                ['min' => 100, 'max' => 999999, 'label' => 'Trên 100m²']
            ];
        }
    }

    /**
     * Test method for debugging
     */
    public function test()
    {
        try {
            // Test basic query
            $properties = Property::withoutGlobalScope('organization')
                ->whereNull('deleted_at')
                ->with(['location2025', 'propertyType', 'units'])
                ->active()
                ->whereHas('units')
                ->orderBy('created_at', 'desc')
                ->paginate(12);

            return response()->json([
                'success' => true,
                'properties_count' => $properties->count(),
                'properties_total' => $properties->total(),
                'properties_data' => $properties->toArray(),
                'locations' => $this->getLocations()->toArray(),
                'property_types' => $this->getPropertyTypes()->toArray(),
                'price_ranges' => $this->getPriceRanges(),
                'area_ranges' => $this->getAreaRanges()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
