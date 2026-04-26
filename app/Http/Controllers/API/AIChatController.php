<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AIChatService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AIChatController extends Controller
{
    private $aiChatService;

    public function __construct(AIChatService $aiChatService)
    {
        $this->aiChatService = $aiChatService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Send message to AI chat
     * POST /api/ai-chat/message
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'conversation_id' => 'nullable|string',
            'history' => 'nullable|array',
        ]);

        $message = $validated['message'];
        $history = $validated['history'] ?? [];

        $response = $this->aiChatService->chat($message, $history);

        return response()->json($response);
    }

    /**
     * Stream chat response with Server-Sent Events
     * GET /api/ai-chat/stream
     */
    public function streamMessage(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'history' => 'nullable|array',
        ]);

        return response()->stream(function () use ($validated) {
            $this->aiChatService->streamChat(
                $validated['message'],
                $validated['history'] ?? [],
                function ($response) {
                    echo 'data: ' . json_encode($response) . "\n\n";
                    ob_flush();
                    flush();
                }
            );
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * Get available tools/functions
     * GET /api/ai-chat/tools
     */
    public function getTools(): JsonResponse
    {
        $toolService = app('App\Services\AIToolService');
        $tools = $toolService->getToolDefinitions();

        return response()->json([
            'success' => true,
            'tools' => $tools,
            'count' => count($tools),
        ]);
    }

    /**
     * Get conversation history
     * GET /api/ai-chat/history/{conversation_id}
     */
    public function getHistory($conversationId): JsonResponse
    {
        // In production, store and retrieve from database
        // For now, client side handles history
        
        return response()->json([
            'success' => true,
            'message' => 'History retrieval not yet implemented',
        ]);
    }

    /**
     * Clear conversation
     * POST /api/ai-chat/clear
     */
    public function clearConversation(Request $request): JsonResponse
    {
        // Clear conversation on client side or in database
        return response()->json([
            'success' => true,
            'message' => 'Conversation cleared',
        ]);
    }
}
