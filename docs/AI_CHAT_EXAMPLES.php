<?php

/**
 * Example: How to integrate AI Chat into your Laravel app
 * 
 * File: app/Http/Controllers/DashboardController.php
 */

namespace App\Http\Controllers;

use App\Services\AIChatService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private $aiChatService;

    public function __construct(AIChatService $aiChatService)
    {
        $this->aiChatService = $aiChatService;
    }

    /**
     * Show dashboard with AI Chat widget
     */
    public function index(): View
    {
        return view('dashboard', [
            'ai_chat_enabled' => config('ai.vertex_ai.project_id') !== null,
        ]);
    }

    /**
     * Example: Direct AI Chat from Controller
     * 
     * Usage: POST /dashboard/chat
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $response = $this->aiChatService->chat(
            $request->input('message'),
            $request->input('history', [])
        );

        return response()->json($response);
    }

    /**
     * Example: Use AI to generate insights
     */
    public function generateMonthlyReport()
    {
        $message = "Generate a comprehensive monthly report including total revenue, occupancy rate, and pending invoices";
        
        $response = $this->aiChatService->chat($message);

        if ($response['success']) {
            return view('reports.monthly', [
                'report' => $response['message'],
                'data' => $response['tool_results'] ?? [],
            ]);
        }

        return back()->with('error', 'Failed to generate report');
    }
}

/**
 * Example: Blade Template Integration
 * 
 * File: resources/views/dashboard.blade.php
 */
?>

@extends('layouts.app')

@section('content')
    <div class="dashboard-container">
        <div class="main-content">
            <!-- Your dashboard content -->
        </div>

        @if($ai_chat_enabled)
            <!-- AI Chat Widget -->
            <ai-chat-widget></ai-chat-widget>
        @endif
    </div>

    <style>
        .dashboard-container {
            display: flex;
            gap: 20px;
        }

        .main-content {
            flex: 1;
        }

        /* Position chat widget bottom-right */
        ai-chat-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 400px;
            z-index: 1000;
        }

        @media (max-width: 768px) {
            ai-chat-widget {
                width: 90%;
                left: 5%;
                right: 5%;
            }
        }
    </style>
@endsection


<?php

/**
 * Example: Custom AI Commands in Artisan
 * 
 * File: app/Console/Commands/RunAIChat.php
 */

namespace App\Console\Commands;

use App\Services\AIChatService;
use Illuminate\Console\Command;

class RunAIChat extends Command
{
    protected $signature = 'ai:chat {message : The message to send to AI}';
    protected $description = 'Chat with AI Assistant from command line';

    private $aiChatService;

    public function __construct(AIChatService $aiChatService)
    {
        parent::__construct();
        $this->aiChatService = $aiChatService;
    }

    public function handle()
    {
        $message = $this->argument('message');
        $history = [];

        while (true) {
            $response = $this->aiChatService->chat($message, $history);

            // Display response
            $this->info("\n🤖 AI: " . $response['message'] . "\n");

            // Display tool calls if any
            if (!empty($response['tool_calls'])) {
                $this->line("🔧 Tools executed:");
                foreach ($response['tool_calls'] as $tool) {
                    $this->line("  - {$tool['name']}");
                }
            }

            // Update history
            if (isset($response['history'])) {
                $history = $response['history'];
            }

            // Ask for next message
            $message = $this->ask("\n👤 You");
            
            if (strtolower($message) === 'quit' || strtolower($message) === 'exit') {
                $this->info("\nGoodbye!");
                break;
            }
        }
    }
}

// Usage: php artisan ai:chat "Create a property"

?>

<?php

/**
 * Example: Background Job for AI Processing
 * 
 * File: app/Jobs/ProcessAIChat.php
 */

namespace App\Jobs;

use App\Services\AIChatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAIChat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $message;
    private $userId;
    private $conversationId;

    public function __construct($message, $userId, $conversationId = null)
    {
        $this->message = $message;
        $this->userId = $userId;
        $this->conversationId = $conversationId;
    }

    public function handle()
    {
        $aiChatService = app(AIChatService::class);
        
        // Get conversation history
        $history = []; // Fetch from database if needed
        
        // Process AI chat
        $response = $aiChatService->chat($this->message, $history);
        
        // Store response in database
        // ChatMessage::create([
        //     'conversation_id' => $this->conversationId,
        //     'user_id' => $this->userId,
        //     'role' => 'assistant',
        //     'content' => $response['message'],
        //     'tool_calls' => json_encode($response['tool_calls'] ?? []),
        //     'tool_results' => json_encode($response['tool_results'] ?? []),
        // ]);
    }
}

// Usage in controller:
// ProcessAIChat::dispatch($message, auth()->id(), $conversationId);

?>
