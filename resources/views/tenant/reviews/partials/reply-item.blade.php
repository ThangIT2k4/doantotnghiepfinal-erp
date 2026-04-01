{{-- Reply Item Partial --}}
<div class="reply-item">
    <div class="reply-header">
        <div class="reply-avatar">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($reply->user->full_name ?? $reply->user->name) }}&background=10b981&color=fff&size=40" alt="{{ $reply->user->full_name ?? $reply->user->name }}">
        </div>
        <div class="reply-info">
            <strong>{{ $reply->user->full_name ?? $reply->user->name }} ({{ $reply->user_type_label }})</strong>
            <span class="reply-date">{{ $reply->created_at->format('d/m/Y H:i') }}</span>
        </div>
    </div>
    <div class="reply-text">
        <p>{{ $reply->content }}</p>
    </div>
    
    @if($reply->childReplies->count() > 0)
        <div class="nested-replies">
            @foreach($reply->childReplies as $childReply)
                @include('tenant.reviews.partials.reply-item', ['reply' => $childReply])
            @endforeach
        </div>
    @endif
</div>
