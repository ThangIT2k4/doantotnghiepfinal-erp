<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\Lease;
use App\Models\Unit;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReviewController extends Controller
{
    protected $imageService;
    
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }
    /**
     * Display a listing of the tenant's reviews
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get all reviews for the authenticated tenant with optimized query
        $query = Review::select([
            'reviews.*',
            'units.code as unit_name',
            'properties.name as property_name',
            DB::raw("CONCAT_WS(', ', locations.street, locations.ward, locations.district, locations.city) as location_address"),
            DB::raw("CONCAT_WS(', ', locations2025.street, locations2025.ward, locations2025.city) as location2025_address"),
            'leases.contract_no as lease_contract_number'
        ])
        ->leftJoin('units', 'reviews.unit_id', '=', 'units.id')
        ->leftJoin('properties', 'units.property_id', '=', 'properties.id')
        ->leftJoin('locations', 'properties.location_id', '=', 'locations.id')
        ->leftJoin('locations as locations2025', 'properties.location_id_2025', '=', 'locations2025.id')
        ->leftJoin('leases', 'reviews.lease_id', '=', 'leases.id')
        ->with('documents')
        ->where('reviews.tenant_id', $user->id)
        ->whereNull('reviews.deleted_at');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('properties.name', 'like', "%{$search}%")
                  ->orWhere('units.code', 'like', "%{$search}%")
                  ->orWhere('reviews.title', 'like', "%{$search}%");
            });
        }

        // Apply rating filter
        if ($request->filled('rating') && $request->rating !== '') {
            $rating = $request->rating;
            $query->where('reviews.overall_rating', $rating);
        }

        // Apply status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $status = $request->status;
            if ($status === 'pending') {
                // Show leases that haven't been reviewed yet
                $query->whereNull('reviews.id');
            } elseif ($status === 'published') {
                $query->where('reviews.status', 'published');
            } elseif ($status === 'replied') {
                $query->whereHas('allReplies');
            }
        }

        $reviews = $query->latest('reviews.created_at')->paginate(10);

        // Calculate statistics
        $stats = $this->calculateReviewStats($user->id);

        // Handle HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        if ($isHtmx) {
            try {
                // Render reviews list partial
                $reviewsListHtml = view('tenant.reviews.partials.reviews-list', compact('reviews'))->render();
                
                // Prepare stats for hx-swap-oob
                $statsData = [
                    [
                        'icon' => 'fas fa-star',
                        'value' => $stats['total'] ?? 0,
                        'label' => 'Tổng đánh giá',
                        'active' => request('status', 'all') == 'all',
                        'data-filter' => 'all',
                        'hx-get' => route('tenant.reviews.index', ['status' => 'all', 'search' => request('search'), 'rating' => request('rating')]),
                        'hx-target' => '#reviews-list-container',
                        'hx-swap' => 'innerHTML',
                        'hx-push-url' => 'true',
                        'hx-indicator' => '#htmx-loading',
                        'title' => 'Click để xem tất cả đánh giá',
                        'amount' => ($stats['avg_rating'] ?? 0) . '/5 ⭐',
                        'statusClass' => 'total'
                    ],
                    [
                        'icon' => 'fas fa-clock',
                        'value' => $stats['pending'] ?? 0,
                        'label' => 'Chờ đánh giá',
                        'active' => request('status') == 'pending',
                        'data-filter' => 'pending',
                        'hx-get' => route('tenant.reviews.index', ['status' => 'pending', 'search' => request('search'), 'rating' => request('rating')]),
                        'hx-target' => '#reviews-list-container',
                        'hx-swap' => 'innerHTML',
                        'hx-push-url' => 'true',
                        'hx-indicator' => '#htmx-loading',
                        'title' => 'Click để xem phòng chờ đánh giá',
                        'statusClass' => 'pending'
                    ],
                    [
                        'icon' => 'fas fa-check-circle',
                        'value' => $stats['total'] - $stats['pending'] ?? 0,
                        'label' => 'Đã đăng',
                        'active' => request('status') == 'published',
                        'data-filter' => 'published',
                        'hx-get' => route('tenant.reviews.index', ['status' => 'published', 'search' => request('search'), 'rating' => request('rating')]),
                        'hx-target' => '#reviews-list-container',
                        'hx-swap' => 'innerHTML',
                        'hx-push-url' => 'true',
                        'hx-indicator' => '#htmx-loading',
                        'title' => 'Click để xem đánh giá đã đăng',
                        'statusClass' => 'published'
                    ],
                    [
                        'icon' => 'fas fa-reply',
                        'value' => $stats['replied'] ?? 0,
                        'label' => 'Có phản hồi',
                        'active' => request('status') == 'replied',
                        'data-filter' => 'replied',
                        'hx-get' => route('tenant.reviews.index', ['status' => 'replied', 'search' => request('search'), 'rating' => request('rating')]),
                        'hx-target' => '#reviews-list-container',
                        'hx-swap' => 'innerHTML',
                        'hx-push-url' => 'true',
                        'hx-indicator' => '#htmx-loading',
                        'title' => 'Click để xem đánh giá có phản hồi',
                        'statusClass' => 'replied'
                    ]
                ];
                
                $statsHtml = view('tenant.components.stats-cards', [
                    'stats' => $statsData,
                    'columns' => 4
                ])->render();
                
                // Prepare filter tabs for hx-swap-oob
                $filterTabs = [
                    [
                        'label' => 'Tất cả',
                        'value' => 'all',
                        'active' => request('status', 'all') == 'all',
                        'icon' => 'fas fa-folder',
                        'hx-get' => route('tenant.reviews.index', ['status' => 'all', 'search' => request('search'), 'rating' => request('rating')]),
                        'hx-target' => '#reviews-list-container',
                        'hx-swap' => 'innerHTML',
                        'hx-push-url' => 'true',
                        'hx-indicator' => '#htmx-loading'
                    ],
                    [
                        'label' => 'Chờ đánh giá',
                        'value' => 'pending',
                        'active' => request('status') == 'pending',
                        'icon' => 'fas fa-clock',
                        'hx-get' => route('tenant.reviews.index', ['status' => 'pending', 'search' => request('search'), 'rating' => request('rating')]),
                        'hx-target' => '#reviews-list-container',
                        'hx-swap' => 'innerHTML',
                        'hx-push-url' => 'true',
                        'hx-indicator' => '#htmx-loading'
                    ],
                    [
                        'label' => 'Đã đăng',
                        'value' => 'published',
                        'active' => request('status') == 'published',
                        'icon' => 'fas fa-check-circle',
                        'hx-get' => route('tenant.reviews.index', ['status' => 'published', 'search' => request('search'), 'rating' => request('rating')]),
                        'hx-target' => '#reviews-list-container',
                        'hx-swap' => 'innerHTML',
                        'hx-push-url' => 'true',
                        'hx-indicator' => '#htmx-loading'
                    ],
                    [
                        'label' => 'Có phản hồi',
                        'value' => 'replied',
                        'active' => request('status') == 'replied',
                        'icon' => 'fas fa-reply',
                        'hx-get' => route('tenant.reviews.index', ['status' => 'replied', 'search' => request('search'), 'rating' => request('rating')]),
                        'hx-target' => '#reviews-list-container',
                        'hx-swap' => 'innerHTML',
                        'hx-push-url' => 'true',
                        'hx-indicator' => '#htmx-loading'
                    ]
                ];
                
                $filterHtml = view('tenant.components.filter-section', [
                    'searchPlaceholder' => 'Tìm kiếm theo tên phòng, địa chỉ...',
                    'searchValue' => request('search', ''),
                    'filters' => $filterTabs,
                    'formId' => 'filterForm',
                    'searchInputId' => 'searchInput',
                    'hxGet' => route('tenant.reviews.index'),
                    'hxTarget' => '#reviews-list-container',
                    'hxSwap' => 'innerHTML',
                    'hxPushUrl' => 'true',
                    'hxIndicator' => '#htmx-loading',
                    'hxTrigger' => 'input delay:500ms from:#searchInput',
                    'additionalFields' => view('tenant.reviews.partials.rating-filter', ['selectedRating' => request('rating')])->render()
                ])->render();
                
                // Return HTML with hx-swap-oob for stats and filters
                $html = $reviewsListHtml . "\n<div id='stats-cards-container' hx-swap-oob='true'>" . $statsHtml . "</div>\n<div id='filter-section-container' hx-swap-oob='true'>" . $filterHtml . "</div>";
                
                return response($html)
                    ->header('HX-Push-Url', $request->fullUrl());
            } catch (\Exception $e) {
                Log::error('ReviewController HTMX Error: ' . $e->getMessage());
                return response('<div class="alert alert-danger">Có lỗi xảy ra khi tải dữ liệu: ' . $e->getMessage() . '</div>', 500);
            }
        }

        return view('tenant.reviews.index', compact('reviews', 'stats'));
    }

    /**
     * Show the form for creating a new review
     */
    public function create()
    {
        $user = Auth::user();
        
        // Get accessible lease IDs (user as tenant or resident)
        $accessibleLeaseIds = Lease::getAccessibleLeaseIds($user->id);
        
        // Get active leases for the tenant (allow multiple reviews per lease)
        // Filter out leases with missing unit or property relationships
        $leases = Lease::with([
            'unit.property.location',
            'unit.property.location2025'
        ])
        ->whereIn('id', $accessibleLeaseIds)
        ->where('status', 'active')
        ->whereNull('deleted_at')
        ->whereHas('unit', function($query) {
            $query->whereNull('deleted_at')
                  ->whereHas('property', function($q) {
                      $q->whereNull('deleted_at');
                  });
        })
        ->get();

        return view('tenant.reviews.create', compact('leases'));
    }

    /**
     * Store a newly created review
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Debug logging
            Log::info('Review store request:', [
                'user_id' => $user->id,
                'request_data' => $request->all()
            ]);
            
            // Validate input
            $validated = $request->validate([
                'lease_id' => 'required|exists:leases,id',
                'title' => 'required|string|min:5|max:255',
                'content' => 'required|string|min:50',
                'overall_rating' => 'required|numeric|min:1|max:5',
                'location_rating' => 'nullable|numeric|min:1|max:5',
                'quality_rating' => 'nullable|numeric|min:1|max:5',
                'service_rating' => 'nullable|numeric|min:1|max:5',
                'price_rating' => 'nullable|numeric|min:1|max:5',
                'highlights' => 'nullable|array',
                'recommend' => 'nullable|in:yes,maybe,no',
                'images' => 'nullable|array|max:5',
                'images.*' => 'image|mimes:jpg,jpeg,png|max:2048'
            ], [
                'lease_id.required' => 'Vui lòng chọn hợp đồng thuê',
                'title.required' => 'Vui lòng nhập tiêu đề đánh giá',
                'title.min' => 'Tiêu đề phải có ít nhất 5 ký tự',
                'content.required' => 'Vui lòng nhập nội dung đánh giá',
                'content.min' => 'Nội dung phải có ít nhất 50 ký tự',
                'overall_rating.required' => 'Vui lòng đánh giá tổng thể',
                'overall_rating.min' => 'Đánh giá phải từ 1 đến 5 sao',
                'overall_rating.max' => 'Đánh giá phải từ 1 đến 5 sao',
            ]);

            // Get accessible lease IDs (user as tenant or resident)
            $accessibleLeaseIds = Lease::getAccessibleLeaseIds($user->id);
            
            // Verify that the lease belongs to the tenant (allow multiple reviews per lease)
            $lease = Lease::with(['unit.property'])
                ->where('id', $validated['lease_id'])
                ->whereIn('id', $accessibleLeaseIds)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->first();
                
            if (!$lease) {
                Log::error('Lease not found:', [
                    'lease_id' => $validated['lease_id'],
                    'tenant_id' => $user->id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy hợp đồng thuê hoặc bạn không có quyền đánh giá hợp đồng này'
                ], 422);
            }

            // Get property and organization
            $property = $lease->unit->property;
            $organizationId = $property && $property->organization_id 
                ? $property->organization_id 
                : $user->organization_id;

            // Create review
            $review = Review::create([
                'organization_id' => $organizationId,
                'unit_id' => $lease->unit_id,
                'lease_id' => $lease->id,
                'tenant_id' => $user->id,
                'overall_rating' => $validated['overall_rating'],
                'location_rating' => $validated['location_rating'] ?? null,
                'quality_rating' => $validated['quality_rating'] ?? null,
                'service_rating' => $validated['service_rating'] ?? null,
                'price_rating' => $validated['price_rating'] ?? null,
                'title' => $validated['title'],
                'content' => $validated['content'],
                'highlights' => $validated['highlights'] ?? [],
                'recommend' => $validated['recommend'] ?? null,
                'status' => 'published'
            ]);

            // Handle image uploads - save as document attachments
            if ($request->hasFile('images')) {
                $sortOrder = 0;
                $uploadedDocuments = [];
                
                foreach ($request->file('images') as $image) {
                    $document = null;
                    try {
                        // Sử dụng ImageService để upload
                        $uploadedFile = $this->imageService->uploadFile($image, 'reviews', 'review-images');

                        // Verify file was actually saved to storage (lưu trực tiếp vào public/storage)
                        $fullPath = public_path('storage/' . $uploadedFile['original']);
                        if (!file_exists($fullPath)) {
                            throw new \Exception('File không được lưu vào storage. Đường dẫn: ' . $fullPath);
                        }

                        Log::info('Creating document for review image', [
                            'review_id' => $review->id,
                            'filename' => $uploadedFile['filename'],
                            'path' => $uploadedFile['original'],
                            'full_path' => $fullPath,
                            'file_exists' => file_exists($fullPath),
                            'file_size' => file_exists($fullPath) ? filesize($fullPath) : 0
                        ]);

                        $document = \App\Models\Document::create([
                            'owner_type' => Review::class,
                            'owner_id' => $review->id,
                            'file_url' => $uploadedFile['original'],
                            'file_name' => $uploadedFile['original_name'],
                            'mime_type' => $uploadedFile['mime_type'],
                            'file_size' => $uploadedFile['size'],
                            'document_type' => 'image',
                            'uploaded_by' => $user->id,
                            'created_at' => now(),
                        ]);

                        // Refresh to ensure ID is available
                        $document->refresh();

                        if (!$document->id) {
                            throw new \Exception('Document was created but has no ID');
                        }

                        // Verify file still exists after document creation
                        if (!file_exists($fullPath)) {
                            throw new \Exception('File đã bị mất sau khi tạo document. Đường dẫn: ' . $fullPath);
                        }

                        Log::info('Review image document created successfully', [
                            'document_id' => $document->id,
                            'review_id' => $review->id,
                            'sort_order' => $sortOrder,
                            'file_url' => $uploadedFile['url'],
                            'storage_path' => $fullPath
                        ]);

                        $uploadedDocuments[] = $document;
                        $sortOrder++;
                    } catch (\Exception $e) {
                        Log::error('Error uploading review image: ' . $e->getMessage(), [
                            'review_id' => $review->id ?? null,
                            'sort_order' => $sortOrder,
                            'trace' => $e->getTraceAsString(),
                            'document_id' => $document->id ?? null,
                            'file_path' => $uploadedFile['original'] ?? null
                        ]);
                        
                        // Rollback: Delete any uploaded files and documents if error occurs
                        if ($document && $document->id) {
                            try {
                                $document->delete();
                            } catch (\Exception $deleteError) {
                                Log::error('Error deleting document after upload failure: ' . $deleteError->getMessage());
                            }
                        }
                        
                        // Delete uploaded file if document creation failed
                        if (isset($uploadedFile['original'])) {
                            try {
                                $fullPath = public_path('storage/' . $uploadedFile['original']);
                                if (file_exists($fullPath)) {
                                    @unlink($fullPath);
                                }
                            } catch (\Exception $deleteError) {
                                Log::error('Error deleting uploaded file after failure: ' . $deleteError->getMessage());
                            }
                        }
                        
                        // Re-throw to fail the entire request - attachment is critical
                        throw $e;
                    }
                }
                
                Log::info('All review images uploaded successfully', [
                    'review_id' => $review->id,
                    'total_images' => count($uploadedDocuments)
                ]);
            }

            Log::info('Review created:', [
                'review_id' => $review->id,
                'tenant_id' => $user->id,
                'unit_id' => $lease->unit_id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đánh giá đã được đăng thành công!',
                    'review_id' => $review->id
                ]);
            }

            return redirect()->route('tenant.reviews.index')
                ->with('success', 'Đánh giá đã được đăng thành công!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $e->errors()
                ], 422);
            }
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Review creation error:', [
                'message' => $e->getMessage(),
                'user_id' => $user->id ?? null
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified review
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $review = Review::with([
            'unit.property.location',
            'unit.property.location2025',
            'unit.property.masterLeases.landlord',
            'lease',
            'tenant',
            'replies.user',
            'replies.childReplies.user',
            'documents'
        ])
        ->where('id', $id)
        ->where('tenant_id', $user->id)
        ->whereNull('deleted_at')
        ->firstOrFail();

        // Increment view count
        $review->increment('view_count');

        return view('tenant.reviews.show', compact('review'));
    }



    /**
     * Remove the specified review (soft delete)
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            
            Log::info('Delete review request:', [
                'review_id' => $id,
                'user_id' => $user->id,
                'user_name' => $user->name ?? $user->full_name
            ]);
            
            $review = Review::where('id', $id)
                ->where('tenant_id', $user->id)
                ->whereNull('deleted_at')
                ->firstOrFail();

            // Check if review can be deleted
            if (!$review->canBeDeletedBy($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa đánh giá này!'
                ], 403);
            }

            // Soft delete the review using Laravel's built-in soft delete
            $review->delete();

            Log::info('Review deleted:', [
                'review_id' => $review->id,
                'deleted_by' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đánh giá đã được xóa thành công!'
            ]);

        } catch (\Exception $e) {
            Log::error('Review deletion error:', [
                'message' => $e->getMessage(),
                'review_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reviewable leases for AJAX
     */
    public function getReviewableLeases()
    {
        $user = Auth::user();
        
        // Get accessible lease IDs (user as tenant or resident)
        $accessibleLeaseIds = Lease::getAccessibleLeaseIds($user->id);
        
        $leases = Lease::with([
            'unit.property.location',
            'unit.property.location2025'
        ])
        ->whereIn('id', $accessibleLeaseIds)
        ->where('status', 'active')
        ->whereNull('deleted_at')
        ->whereDoesntHave('review')
        ->get()
        ->map(function($lease) {
            return [
                'id' => $lease->id,
                'unit_name' => $lease->unit->code,
                'property_name' => $lease->unit->property->name,
                'address' => $lease->unit->property->address,
                'rent_amount' => $lease->rent_amount,
                'start_date' => $lease->start_date->format('d/m/Y'),
                'end_date' => $lease->end_date->format('d/m/Y')
            ];
        });

        return response()->json($leases);
    }

    /**
     * Store a reply to a review
     */
    public function storeReply(Request $request, $id)
    {
        try {
            $user = Auth::user();
            
            // Get review
            $review = Review::where('id', $id)
                ->whereNull('deleted_at')
                ->firstOrFail();

            // Validate input
            $validated = $request->validate([
                'content' => 'required|string|min:10',
                'parent_reply_id' => 'nullable|exists:review_replies,id'
            ]);

            // Determine user type based on role (simplified for now)
            $userType = 'tenant';
            // TODO: Implement proper role checking when role system is available
            // For now, we'll assume all users are tenants

            // Create reply
            $reply = ReviewReply::create([
                'review_id' => $review->id,
                'user_id' => $user->id,
                'parent_reply_id' => $validated['parent_reply_id'] ?? null,
                'content' => $validated['content'],
                'user_type' => $userType
            ]);

            Log::info('Review reply created:', [
                'reply_id' => $reply->id,
                'review_id' => $review->id,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Phản hồi đã được gửi thành công!',
                'reply' => [
                    'id' => $reply->id,
                    'content' => $reply->content,
                    'user_name' => $user->full_name ?? $user->name,
                    'user_type' => $reply->user_type_label,
                    'created_at' => $reply->created_at->format('d/m/Y H:i')
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Review reply creation error:', [
                'message' => $e->getMessage(),
                'review_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate review statistics for the tenant
     */
    private function calculateReviewStats($tenantId)
    {
        $total = Review::where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->count();

        $avgRating = Review::where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->avg('overall_rating');

        // Lấy accessible leases (bao gồm cả resident) từ TẤT CẢ organizations
        $accessibleLeaseIds = Lease::getAccessibleLeaseIds($tenantId);
        $pending = Lease::whereIn('id', $accessibleLeaseIds)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->whereDoesntHave('review')
            ->count();

        $replied = Review::where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereHas('allReplies')
            ->count();

        $helpful = Review::where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->sum('helpful_count');

        return [
            'total' => $total,
            'avg_rating' => round($avgRating, 1),
            'pending' => $pending,
            'replied' => $replied,
            'helpful' => $helpful
        ];
    }
}
