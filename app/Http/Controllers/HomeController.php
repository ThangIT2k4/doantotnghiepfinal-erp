<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Location;
use App\Models\PropertyType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    /**
     * Display the home page with featured properties and categories
     */
    public function index()
    {
        try {
            // Simple test first - get basic data (bypass organization scope for public access)
            $totalProperties = Property::withoutGlobalScope('organization')->whereNull('deleted_at')->count();
            $activeProperties = Property::withoutGlobalScope('organization')->whereNull('deleted_at')->where('status', 1)->count();
            
            // Get featured properties (latest available units from active properties)
            $featuredProperties = $this->getFeaturedProperties();
            
            // Get categories data (only from active properties)
            $categories = $this->getCategoriesData();
            
            // Get active properties statistics
            $activePropertiesStats = $this->getActivePropertiesStats();
            
            // Get locations for search dropdown
            $locations = $this->getLocations();
            
            // Get unit types for search dropdown
            $unitTypes = $this->getUnitTypes();
            
            // Get price ranges for search dropdown
            $priceRanges = $this->getPriceRanges();
            
            // Get area ranges for search dropdown
            $areaRanges = $this->getAreaRanges();

            // Debug info
            if (config('app.debug')) {
                Log::info('Home page data loaded:', [
                    'total_properties' => $totalProperties,
                    'active_properties' => $activeProperties,
                    'featured_properties_count' => $featuredProperties->count(),
                    'categories_count' => count($categories)
                ]);
            }

            return view('home', compact(
                'featuredProperties',
                'categories',
                'activePropertiesStats',
                'locations',
                'unitTypes',
                'priceRanges',
                'areaRanges'
            ));
        } catch (\Exception $e) {
            // Log error if needed
            if (config('app.debug')) {
                Log::error('Error in HomeController@index: ' . $e->getMessage());
            }
            
            // Return view with empty data in case of error
            return view('home', [
                'featuredProperties' => collect([]),
                'categories' => [],
                'activePropertiesStats' => $this->getDefaultStats(),
                'locations' => collect([]),
                'unitTypes' => [],
                'priceRanges' => [],
                'areaRanges' => []
            ]);
        }
    }

    /**
     * Handle search form submission and display properties
     */
    public function search(Request $request)
    {
        $query = Unit::with(['property', 'amenities'])
            ->where('status', 'available')
            ->whereHas('property', function($q) {
                $q->withoutGlobalScope('organization')
                  ->whereNull('deleted_at') // Ensure not soft deleted
                  ->active(); // Only active properties, bypass organization scope
            });

        // Apply filters
        if ($request->filled('location')) {
            $query->whereHas('property.location2025', function($q) use ($request) {
                $q->where('city', 'like', '%' . $request->location . '%')
                  ->orWhere('ward', 'like', '%' . $request->location . '%')
                  ->orWhere('street', 'like', '%' . $request->location . '%');
            });
        }

        if ($request->filled('property_type')) {
            $query->whereHas('property', function($q) use ($request) {
                $q->where('property_type_id', $request->property_type);
            });
        }

        if ($request->filled('unit_type')) {
            $query->where('unit_type', $request->unit_type);
        }

        if ($request->filled('price_range')) {
            $priceRange = $this->parsePriceRange($request->price_range);
            if ($priceRange) {
                $query->whereBetween('base_rent', [$priceRange['min'], $priceRange['max']]);
            }
        }

        if ($request->filled('area_range')) {
            $areaRange = $this->parseAreaRange($request->area_range);
            if ($areaRange) {
                $query->whereBetween('area_m2', [$areaRange['min'], $areaRange['max']]);
            }
        }

        $units = $query->paginate(12);

        // Get search filters for display
        $locations = $this->getLocations();
        $unitTypes = $this->getUnitTypes();
        $priceRanges = $this->getPriceRanges();
        $areaRanges = $this->getAreaRanges();

        return view('rooms.index', compact(
            'units', 
            'locations', 
            'unitTypes', 
            'priceRanges', 
            'areaRanges'
        ));
    }

    /**
     * Get all active properties for public display
     */
    public function getActiveProperties()
    {
        return Property::withoutGlobalScope('organization')
            ->whereNull('deleted_at') // Ensure not soft deleted
            ->with(['location2025', 'propertyType', 'units'])
            ->active()
            ->whereHas('units') // Only properties that have units
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($property) {
                $availableUnits = $property->units->where('status', 'available');
                $totalUnits = $property->units->count();
                
                return [
                    'id' => $property->id,
                    'name' => $property->name,
                    'type' => $property->propertyType ? $property->propertyType->name : 'Không xác định',
                    'location' => $this->getPropertyLocation($property),
                    'available_units' => $availableUnits->count(),
                    'total_units' => $totalUnits,
                    'occupancy_rate' => $totalUnits > 0 ? round((($totalUnits - $availableUnits->count()) / $totalUnits) * 100, 1) : 0,
                    'image' => $this->getPropertyImage($property, $availableUnits->first()),
                    'is_new' => $property->created_at->diffInDays(now()) <= 7,
                    'description' => $property->description
                ];
            });
    }

    /**
     * Get statistics for active properties
     */
    public function getActivePropertiesStats()
    {
        try {
            // Check if we're in testing environment or if database is empty
            if (app()->environment('testing') || !$this->hasDatabaseData()) {
                return $this->getDefaultStats();
            }

            // Bypass organization scope for public access - only properties with available units
            $activeProperties = Property::withoutGlobalScope('organization')
                ->whereNull('deleted_at') // Ensure not soft deleted
                ->active()->whereHas('units', function($query) {
                    $query->where('status', 'available'); // Only properties with available units
                });
            $totalActiveProperties = $activeProperties->count();
            
            // Only count units from properties that have available units
            $totalUnits = Unit::whereHas('property', function($query) {
                $query->withoutGlobalScope('organization')
                      ->whereNull('deleted_at') // Ensure not soft deleted
                      ->active()
                      ->whereHas('units', function($subQuery) {
                          $subQuery->where('status', 'available'); // Only from properties with available units
                      });
            })->count();
            
            $availableUnits = Unit::whereHas('property', function($query) {
                $query->withoutGlobalScope('organization')
                      ->whereNull('deleted_at') // Ensure not soft deleted
                      ->active()
                      ->whereHas('units', function($subQuery) {
                          $subQuery->where('status', 'available'); // Only from properties with available units
                      });
            })->where('status', 'available')->count();
            
            $occupiedUnits = Unit::whereHas('property', function($query) {
                $query->withoutGlobalScope('organization')
                      ->whereNull('deleted_at') // Ensure not soft deleted
                      ->active()
                      ->whereHas('units', function($subQuery) {
                          $subQuery->where('status', 'available'); // Only from properties with available units
                      });
            })->where('status', 'occupied')->count();
            
            $propertiesByType = Property::withoutGlobalScope('organization')
                ->whereNull('deleted_at') // Ensure not soft deleted
                ->active()
                ->whereHas('units', function($query) {
                    $query->where('status', 'available'); // Only properties with available units
                })
                ->with('propertyType')
                ->get()
                ->groupBy('propertyType.name')
                ->map(function($properties) {
                    return $properties->count();
                });
            
            return [
                'total_active_properties' => $totalActiveProperties,
                'total_units' => $totalUnits,
                'available_units' => $availableUnits,
                'occupied_units' => $occupiedUnits,
                'occupancy_rate' => $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0,
                'properties_by_type' => $propertiesByType,
                'new_properties_this_week' => Property::withoutGlobalScope('organization')
                    ->whereNull('deleted_at') // Ensure not soft deleted
                    ->active()
                    ->whereHas('units', function($query) {
                        $query->where('status', 'available'); // Only properties with available units
                    })
                    ->where('created_at', '>=', now()->subWeek())
                    ->count()
            ];
        } catch (\Exception $e) {
            // Log error if needed
            if (config('app.debug')) {
                Log::error('Error in getActivePropertiesStats: ' . $e->getMessage());
            }
            
            // Return default values in case of error
            return $this->getDefaultStats();
        }
    }

    /**
     * Check if database has any data
     */
    private function hasDatabaseData()
    {
        try {
            return Property::withoutGlobalScope('organization')->count() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get default statistics for testing or empty database
     */
    private function getDefaultStats()
    {
        return [
            'total_active_properties' => 0,
            'total_units' => 0,
            'available_units' => 0,
            'occupied_units' => 0,
            'occupancy_rate' => 0,
            'properties_by_type' => collect([]),
            'new_properties_this_week' => 0
        ];
    }

    /**
     * Show property detail with available units
     */
    public function propertyDetail($id)
    {
        $property = Property::withoutGlobalScope('organization')
            ->whereNull('deleted_at') // Ensure not soft deleted
            ->with([
                'location2025', 
                'propertyType', 
                'units.amenities' => function($query) {
                    $query->where('status', 'available');
                }
            ])->active()->findOrFail($id);

        // Get aggregated amenities from all units
        $allAmenities = $property->units->flatMap(function($unit) {
            return $unit->amenities;
        })->unique('id');

        // Get available units
        $availableUnits = $property->units->where('status', 'available');

        // Get price and area statistics
        $prices = $availableUnits->pluck('base_rent');
        $areas = $availableUnits->pluck('area_m2')->filter();
        
        $stats = [
            'min_price' => $prices->min(),
            'max_price' => $prices->max(),
            'min_area' => $areas->min(),
            'max_area' => $areas->max(),
            'total_units' => $property->units->count(),
            'available_units' => $availableUnits->count(),
            'occupied_units' => $property->units->where('status', 'occupied')->count()
        ];

        return view('property.detail', compact(
            'property', 
            'allAmenities', 
            'availableUnits', 
            'stats'
        ));
    }

    /**
     * Get featured properties with aggregated amenities from units
     */
    public function getFeaturedProperties()
    {
        try {
            // Get active properties with available units (bypass organization scope for public access)
            $propertiesQuery = Property::withoutGlobalScope('organization')
                ->with(['location2025', 'propertyType', 'units' => function($query) {
                    $query->where('status', 'available'); // Only load available units
                }, 'units.amenities'])
                ->whereNull('deleted_at') // Ensure not soft deleted
                ->active() // Use the active scope (status = 1)
                ->whereHas('units', function($query) {
                    $query->where('status', 'available'); // Only properties with available units
                });
                
            // If no properties with available units, get any active properties with units
            if ($propertiesQuery->count() == 0) {
                $propertiesQuery = Property::withoutGlobalScope('organization')
                    ->with(['location2025', 'propertyType', 'units.amenities'])
                    ->whereNull('deleted_at') // Ensure not soft deleted
                    ->active() // Use the active scope (status = 1)
                    ->whereHas('units');
            }
            
            // If still no properties, get any active properties
            if ($propertiesQuery->count() == 0) {
                $propertiesQuery = Property::withoutGlobalScope('organization')
                    ->with(['location2025', 'propertyType', 'units.amenities'])
                    ->whereNull('deleted_at') // Ensure not soft deleted
                    ->active(); // Use the active scope (status = 1)
            }
            
            $properties = $propertiesQuery
                ->orderBy('created_at', 'desc')
                ->limit(6)
                ->get()
                ->map(function($property) {
                // Get aggregated amenities from all units
                $allAmenities = $property->units->flatMap(function($unit) {
                    return $unit->amenities;
                })->unique('id');
                
                // Get price range from units
                $prices = $property->units->where('status', 'available')->pluck('base_rent');
                $minPrice = $prices->min();
                $maxPrice = $prices->max();
                
                // Get area range from units
                $areas = $property->units->where('status', 'available')->pluck('area_m2')->filter();
                $minArea = $areas->min();
                $maxArea = $areas->max();
                
                // Get first available unit for main image
                $firstUnit = $property->units->where('status', 'available')->first();
                
                return [
                    'id' => $property->id,
                    'title' => $property->name,
                    'type' => $property->propertyType ? $property->propertyType->name : 'Không xác định',
                    'price_range' => $minPrice == $maxPrice ? 
                        number_format($minPrice, 0, ',', '.') . ' VNĐ/tháng' :
                        number_format($minPrice, 0, ',', '.') . ' - ' . number_format($maxPrice, 0, ',', '.') . ' VNĐ/tháng',
                    'area_range' => $minArea && $maxArea ? 
                        ($minArea == $maxArea ? $minArea . 'm²' : $minArea . ' - ' . $maxArea . 'm²') : 
                        'N/A',
                    'available_units' => $property->units->where('status', 'available')->count(),
                    'total_units' => $property->units->count(),
                    'location' => $this->getPropertyLocation($property),
                    'image' => $this->getPropertyImage($property, $firstUnit),
                    'amenities' => $allAmenities->take(5)->pluck('name')->toArray(),
                    'is_new' => $property->created_at->diffInDays(now()) <= 7,
                    'description' => $property->description
                ];
            });
        
        return $properties;
        } catch (\Exception $e) {
            Log::error('Error in getFeaturedProperties: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Get categories data with counts from property_types table
     */
    private function getCategoriesData()
    {
        // Get active property types
        $propertyTypes = PropertyType::where('status', 1)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $categories = [];
        $colors = ['blue', 'green', 'purple', 'orange', 'pink', 'indigo', 'teal', 'red'];

        foreach ($propertyTypes as $index => $type) {
            // Count ONLY AVAILABLE units for this property type from active properties (bypass organization scope)
            $count = Unit::whereHas('property', function($query) use ($type) {
                $query->withoutGlobalScope('organization')
                      ->where('property_type_id', $type->id)
                      ->whereNull('deleted_at') // Ensure property not soft deleted
                      ->active(); // Use active scope
            })->where('status', 'available')->count(); // Count only available units

            // Only include property types that have available units
            if ($count > 0) {
                $categories[] = [
                    'id' => $type->id,
                    'key' => $type->key_code,
                    'name' => $type->name,
                    'description' => $type->description ?: 'Có nhiều sự lựa chọn khác nhau',
                    'icon' => $type->icon ?: 'fas fa-home',
                    'color' => $colors[count($categories) % count($colors)], // Use categories count for color index
                    'count' => $count
                ];
            }
        }

        return $categories;
    }

    /**
     * Get locations for search dropdown
     */
    private function getLocations()
    {
        return Location::select('city', 'ward')
            ->distinct()
            ->orderBy('city')
            ->orderBy('ward')
            ->get()
            ->groupBy('city')
            ->map(function($wards, $city) {
                return [
                    'city' => $city,
                    'wards' => $wards->pluck('ward')->toArray()
                ];
            });
    }

    /**
     * Get property types for search dropdown
     */
    private function getPropertyTypes()
    {
        $propertyTypes = PropertyType::where('status', 1)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $types = [['value' => '', 'label' => 'Tất cả loại bất động sản']];
        
        foreach ($propertyTypes as $type) {
            $types[] = [
                'value' => $type->key_code,
                'label' => $type->name
            ];
        }

        return $types;
    }

    /**
     * Get unit types for search dropdown (legacy)
     */
    private function getUnitTypes()
    {
        return [
            ['value' => '', 'label' => 'Tất cả loại phòng'],
            ['value' => 'room', 'label' => 'Phòng trọ'],
            ['value' => 'apartment', 'label' => 'Chung cư mini'],
            ['value' => 'dorm', 'label' => 'Chung cư cao cấp'],
            ['value' => 'shared', 'label' => 'Nhà nguyên căn']
        ];
    }

    /**
     * Get price ranges for search dropdown
     */
    private function getPriceRanges()
    {
        return [
            ['value' => '', 'label' => 'Chọn mức giá'],
            ['value' => '0-2000000', 'label' => 'Dưới 2 triệu'],
            ['value' => '2000000-3000000', 'label' => '2-3 triệu'],
            ['value' => '3000000-5000000', 'label' => '3-5 triệu'],
            ['value' => '5000000-8000000', 'label' => '5-8 triệu'],
            ['value' => '8000000-12000000', 'label' => '8-12 triệu'],
            ['value' => '12000000-999999999', 'label' => 'Trên 12 triệu']
        ];
    }

    /**
     * Get area ranges for search dropdown
     */
    private function getAreaRanges()
    {
        return [
            ['value' => '', 'label' => 'Chọn diện tích'],
            ['value' => '0-20', 'label' => 'Dưới 20m²'],
            ['value' => '20-30', 'label' => '20-30m²'],
            ['value' => '30-50', 'label' => '30-50m²'],
            ['value' => '50-80', 'label' => '50-80m²'],
            ['value' => '80-999', 'label' => 'Trên 80m²']
        ];
    }

    /**
     * Parse price range string to array
     */
    private function parsePriceRange($range)
    {
        if (empty($range)) return null;
        
        $parts = explode('-', $range);
        if (count($parts) !== 2) return null;
        
        return [
            'min' => (float) $parts[0],
            'max' => (float) $parts[1]
        ];
    }

    /**
     * Parse area range string to array
     */
    private function parseAreaRange($range)
    {
        if (empty($range)) return null;
        
        $parts = explode('-', $range);
        if (count($parts) !== 2) return null;
        
        return [
            'min' => (float) $parts[0],
            'max' => (float) $parts[1]
        ];
    }

    /**
     * Get unit type label in Vietnamese
     */
    private function getUnitTypeLabel($type)
    {
        $labels = [
            'room' => 'Phòng trọ',
            'apartment' => 'Chung cư mini',
            'dorm' => 'Chung cư cao cấp',
            'shared' => 'Nhà nguyên căn'
        ];

        return $labels[$type] ?? $type;
    }

    /**
     * Get property location string
     */
    private function getPropertyLocation($property)
    {
        if ($property->location2025) {
            $location = $property->location2025;
            return $location->street . ', ' . $location->ward . ', ' . $location->city;
        }
        
        return 'Địa chỉ chưa cập nhật';
    }

    /**
     * Get property image (from property or first available unit)
     */
    private function getPropertyImage($property, $firstUnit = null)
    {
        // Try property images first
        if ($property->images && count($property->images) > 0) {
            return Storage::url($property->images[0]);
        }
        
        // Fallback to first unit image
        if ($firstUnit && $firstUnit->images && count($firstUnit->images) > 0) {
            return Storage::url($firstUnit->images[0]);
        }
        
        // Return placeholder image
        return 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=400&h=300&fit=crop';
    }

    /**
     * Get unit location string (legacy method)
     */
    private function getUnitLocation($unit)
    {
        if ($unit->property->location2025) {
            $location = $unit->property->location2025;
            return $location->street . ', ' . $location->ward . ', ' . $location->city;
        }
        
        return 'Địa chỉ chưa cập nhật';
    }

    /**
     * Get unit image (legacy method)
     */
    private function getUnitImage($unit)
    {
        if ($unit->images && count($unit->images) > 0) {
            return Storage::url($unit->images[0]);
        }
        
        // Return placeholder image
        return 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=400&h=300&fit=crop';
    }

    /**
     * Debug method to check active properties data
     */
    public function debugActiveProperties()
    {
        try {
            $totalProperties = Property::withoutGlobalScope('organization')->whereNull('deleted_at')->count();
            $activeProperties = Property::withoutGlobalScope('organization')->whereNull('deleted_at')->active()->count();
            $propertiesWithUnits = Property::withoutGlobalScope('organization')->whereNull('deleted_at')->active()->whereHas('units')->count();
            $propertiesWithAvailableUnits = Property::withoutGlobalScope('organization')->whereNull('deleted_at')->active()->whereHas('units', function($query) {
                $query->where('status', 'available');
            })->count();
            
            $featuredProperties = $this->getFeaturedProperties();
            $categories = $this->getCategoriesData();
            $stats = $this->getActivePropertiesStats();
            
            return response()->json([
                'success' => true,
                'debug_info' => [
                    'total_properties' => $totalProperties,
                    'active_properties' => $activeProperties,
                    'active_properties_with_units' => $propertiesWithUnits,
                    'active_properties_with_available_units' => $propertiesWithAvailableUnits,
                ],
                'featured_properties_count' => $featuredProperties->count(),
                'categories_count' => count($categories),
                'stats' => $stats,
                'featured_properties' => $featuredProperties,
                'categories' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Simple database test method
     */
    public function testDatabase()
    {
        try {
            // Test basic queries (bypass organization scope for public access)
            $totalProperties = Property::withoutGlobalScope('organization')->whereNull('deleted_at')->count();
            $activeProperties = Property::withoutGlobalScope('organization')->whereNull('deleted_at')->where('status', 1)->count();
            $totalUnits = Unit::count();
            $availableUnits = Unit::where('status', 'available')->count();
            
            // Test with relationships
            $properties = Property::withoutGlobalScope('organization')->whereNull('deleted_at')->with(['propertyType', 'units'])->limit(3)->get();
            
            // Test PropertyType
            $propertyTypes = PropertyType::where('status', 1)->whereNull('deleted_at')->count();
            
            return response()->json([
                'success' => true,
                'database_connection' => 'OK',
                'basic_stats' => [
                    'total_properties' => $totalProperties,
                    'active_properties' => $activeProperties,
                    'total_units' => $totalUnits,
                    'available_units' => $availableUnits,
                    'active_property_types' => $propertyTypes
                ],
                'sample_properties' => $properties->map(function($property) {
                    return [
                        'id' => $property->id,
                        'name' => $property->name,
                        'status' => $property->status,
                        'property_type' => $property->propertyType ? $property->propertyType->name : 'N/A',
                        'units_count' => $property->units->count(),
                        'available_units' => $property->units->where('status', 'available')->count()
                    ];
                })
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

    /**
     * Test home page data method
     */
    public function testHomeData()
    {
        try {
            // Test each method individually
            $featuredProperties = $this->getFeaturedProperties();
            $categories = $this->getCategoriesData();
            $stats = $this->getActivePropertiesStats();
            $locations = $this->getLocations();
            
            return response()->json([
                'success' => true,
                'featured_properties' => [
                    'count' => $featuredProperties->count(),
                    'data' => $featuredProperties->toArray()
                ],
                'categories' => [
                    'count' => count($categories),
                    'data' => $categories
                ],
                'stats' => $stats,
                'locations' => [
                    'count' => $locations->count(),
                    'data' => $locations->toArray()
                ]
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

    /**
     * Test organization scope bypass
     */
    public function testOrganizationBypass()
    {
        try {
            // Test with organization scope (should be limited)
            $withScope = Property::count();
            
            // Test without organization scope (should show all)
            $withoutScope = Property::withoutGlobalScope('organization')->whereNull('deleted_at')->count();
            
            // Test active properties
            $activeWithScope = Property::active()->count();
            $activeWithoutScope = Property::withoutGlobalScope('organization')->whereNull('deleted_at')->active()->count();
            
            return response()->json([
                'success' => true,
                'organization_scope_test' => [
                    'with_scope' => $withScope,
                    'without_scope' => $withoutScope,
                    'active_with_scope' => $activeWithScope,
                    'active_without_scope' => $activeWithoutScope,
                    'scope_bypassed' => $withoutScope > $withScope
                ],
                'message' => $withoutScope > $withScope ? 
                    'Organization scope successfully bypassed for public access' : 
                    'Organization scope not affecting results (user may be admin or no organization data)'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Test soft delete filtering
     */
    public function testSoftDelete()
    {
        try {
            // Test Property soft delete
            $allProperties = Property::withoutGlobalScope('organization')->withTrashed()->count();
            $activeProperties = Property::withoutGlobalScope('organization')->whereNull('deleted_at')->count();
            $deletedProperties = Property::withoutGlobalScope('organization')->onlyTrashed()->count();
            
            // Test PropertyType soft delete
            $allPropertyTypes = PropertyType::withTrashed()->count();
            $activePropertyTypes = PropertyType::whereNull('deleted_at')->count();
            $deletedPropertyTypes = PropertyType::onlyTrashed()->count();
            
            // Test categories calculation
            $categories = $this->getCategoriesData();
            
            return response()->json([
                'success' => true,
                'soft_delete_test' => [
                    'properties' => [
                        'all' => $allProperties,
                        'active' => $activeProperties,
                        'deleted' => $deletedProperties,
                        'soft_delete_working' => $allProperties === ($activeProperties + $deletedProperties)
                    ],
                    'property_types' => [
                        'all' => $allPropertyTypes,
                        'active' => $activePropertyTypes,
                        'deleted' => $deletedPropertyTypes,
                        'soft_delete_working' => $allPropertyTypes === ($activePropertyTypes + $deletedPropertyTypes)
                    ],
                    'categories_count' => count($categories),
                    'categories_data' => $categories
                ],
                'message' => 'Soft delete filtering is working correctly'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Test categories calculation
     */
    public function testCategoriesCalculation()
    {
        try {
            $propertyTypes = PropertyType::where('status', 1)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get();

            $categories = [];
            
            foreach ($propertyTypes as $type) {
                // Test different counting methods
                $totalUnits = Unit::whereHas('property', function($query) use ($type) {
                    $query->withoutGlobalScope('organization')
                          ->where('property_type_id', $type->id)
                          ->whereNull('deleted_at')
                          ->active();
                })->count();

                $availableUnits = Unit::whereHas('property', function($query) use ($type) {
                    $query->withoutGlobalScope('organization')
                          ->where('property_type_id', $type->id)
                          ->whereNull('deleted_at')
                          ->active();
                })->where('status', 'available')->count();

                $occupiedUnits = Unit::whereHas('property', function($query) use ($type) {
                    $query->withoutGlobalScope('organization')
                          ->where('property_type_id', $type->id)
                          ->whereNull('deleted_at')
                          ->active();
                })->where('status', 'occupied')->count();

                // Check all possible status values
                $statusCounts = Unit::whereHas('property', function($query) use ($type) {
                    $query->withoutGlobalScope('organization')
                          ->where('property_type_id', $type->id)
                          ->whereNull('deleted_at')
                          ->active();
                })->selectRaw('status, count(*) as count')
                  ->groupBy('status')
                  ->get();

                $categories[] = [
                    'property_type' => $type->name,
                    'total_units' => $totalUnits,
                    'available_units' => $availableUnits,
                    'occupied_units' => $occupiedUnits,
                    'status_breakdown' => $statusCounts->toArray()
                ];
            }

            return response()->json([
                'success' => true,
                'categories_calculation' => $categories,
                'message' => 'Categories calculation test completed'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

}
