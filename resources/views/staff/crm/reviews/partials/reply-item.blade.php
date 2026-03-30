<div class="reply-item {{ $reply->user_type === 'manager' ? 'agent-reply' : 'tenant-reply' }}" data-reply-id="{{ $reply->id }}">
    <div class="d-flex justify-content-between align-items-start mb-2">
        <div class="d-flex align-items-center">
            <div class="avatar-sm me-2">
                <div class="avatar-title rounded-circle bg-{{ $reply->user_type === 'manager' ? 'primary' : 'success' }} text-white">
                    {{ substr($reply->user->full_name ?? $reply->user->name ?? 'U', 0, 1) }}
                </div>
            </div>
            <div>
                <h6 class="mb-0">{{ $reply->user->full_name ?? $reply->user->name ?? 'Người dùng' }}</h6>
                <small class="text-muted">{{ $reply->user_type_label }}</small>
            </div>
        </div>
        <div class="dropdown">
            <button class="btn btn-sm btn-link text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="mdi mdi-dots-vertical"></i>
            </button>
            <ul class="dropdown-menu">
                @if($reply->user_id === auth()->id() && $reply->canBeEditedBy(auth()->user()))
                    <li><a class="dropdown-item" href="#" onclick="editReply({{ $reply->id }}, '{{ addslashes($reply->content) }}')">
                        <i class="mdi mdi-pencil me-1"></i>Chỉnh sửa
                    </a></li>
                @endif
                @if($reply->canBeDeletedBy(auth()->user()))
                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteReply({{ $reply->id }})">
                        <i class="mdi mdi-delete me-1"></i>Xóa
                    </a></li>
                @endif
            </ul>
        </div>
    </div>
    
    <div class="reply-content mb-2">
        <p class="mb-0">{{ $reply->content }}</p>
    </div>
    
    <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted">
            <i class="mdi mdi-clock-outline me-1"></i>
            {{ $reply->created_at->format('d/m/Y H:i') }}
            @if($reply->updated_at != $reply->created_at)
                <span class="ms-2">
                    <i class="mdi mdi-pencil me-1"></i>
                    Đã chỉnh sửa {{ $reply->updated_at->format('d/m/Y H:i') }}
                </span>
            @endif
        </small>
        
        @if($reply->user_id === auth()->id() && $reply->canBeEditedBy(auth()->user()))
            <small class="text-info">
                <i class="mdi mdi-clock-alert me-1"></i>
                Có thể chỉnh sửa trong {{ 24 - $reply->created_at->diffInHours(now()) }}h
            </small>
        @endif
    </div>
    
    <!-- Child Replies -->
    @if($reply->childReplies->count() > 0)
        <div class="child-replies mt-3 ms-3">
            @foreach($reply->childReplies as $childReply)
                @include('staff.crm.reviews.partials.reply-item', ['reply' => $childReply])
            @endforeach
        </div>
    @endif
</div>

<style>
.avatar-sm {
    width: 2rem;
    height: 2rem;
}

.avatar-title {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
}

.reply-item {
    border-left: 3px solid #e3e6f0;
    padding-left: 1rem;
    margin-bottom: 1rem;
    position: relative;
}

.reply-item.agent-reply {
    border-left-color: #007bff;
}

.reply-item.tenant-reply {
    border-left-color: #28a745;
}

.child-replies {
    border-left: 2px solid #f8f9fa;
    padding-left: 1rem;
}

.reply-content {
    background-color: #f8f9fa;
    padding: 0.75rem;
    border-radius: 0.375rem;
    margin: 0.5rem 0;
}

.dropdown-toggle::after {
    display: none;
}
</style>
