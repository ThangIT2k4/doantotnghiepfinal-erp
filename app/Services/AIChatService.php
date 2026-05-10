<?php

namespace App\Services;

use Google\Cloud\AIPlatform\V1\Client\PredictionServiceClient;
use Google\Cloud\AIPlatform\V1\GenerateContentRequest;
use Google\Cloud\AIPlatform\V1\Content;
use Google\Cloud\AIPlatform\V1\Part;
use Google\Cloud\AIPlatform\V1\Tool;
use Google\Cloud\AIPlatform\V1\FunctionDeclaration;
use Google\Cloud\AIPlatform\V1\Schema;
use Google\Cloud\AIPlatform\V1\Type;
use Illuminate\Support\Facades\Log;
use Exception;

class AIChatService
{
    private $projectId;
    private $location;
    private $modelId;
    private $client;
    private $toolService;

    public function __construct()
    {
        $this->projectId = config('services.vertex_ai.project_id');
        $this->location = config('services.vertex_ai.location', 'us-central1');
        $this->modelId = config('services.vertex_ai.model', 'gemini-2.0-flash-exp');
        $this->toolService = app(AIToolService::class);
        
        // Initialize Vertex AI client
        $this->client = new PredictionServiceClient();
    }

    /**
     * Send chat message and get response with tool calling
     */
    public function chat(string $message, array $conversationHistory = []): array
    {
        try {
            // Build conversation history with content format
            $contents = $this->buildConversationHistory($conversationHistory, $message);

            // Get tools available for AI
            $tools = $this->buildTools();

            // Create request
            $request = new GenerateContentRequest([
                'model' => $this->getModelPath(),
                'contents' => $contents,
                'tools' => [$tools],
                'generation_config' => [
                    'temperature' => 0.7,
                    'top_p' => 0.95,
                    'top_k' => 40,
                    'max_output_tokens' => 8096,
                ],
            ]);

            // Get response from Vertex AI
            $response = $this->client->generateContent($request);

            // Process response and handle tool calls
            return $this->processResponse($response, $conversationHistory, $message);

        } catch (Exception $e) {
            Log::error('AI Chat Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to process chat message',
                'message' => 'Sorry, I encountered an error. Please try again.',
            ];
        }
    }

    /**
     * Build conversation history
     */
    private function buildConversationHistory(array $history, string $newMessage): array
    {
        $contents = [];

        // Determine user context (tenant vs staff)
        $user = auth()->user();
        $isTenant = false;
        if ($user) {
            $tenantRoleId = \App\Models\Role::where('key_code', 'tenant')->value('id');
            if ($tenantRoleId) {
                $isTenant = $user->organizationUsers()->where('role_id', $tenantRoleId)->exists();
            }
        }
        
        $systemInstructionText = $isTenant 
            ? "Bạn là trợ lý ảo AI thân thiện hỗ trợ khách thuê phòng. Nhiệm vụ của bạn là giải đáp thắc mắc về tiền nhà, hợp đồng và hỗ trợ khách tạo yêu cầu bảo trì (Ticket) khi phòng có sự cố. Luôn xưng hô lịch sự và chuyên nghiệp."
            : "Bạn là trợ lý ảo AI cao cấp dành cho Quản lý tòa nhà/Nhân viên hệ thống ERP. Bạn có khả năng phân tích dữ liệu, truy xuất báo cáo doanh thu, đánh giá sức khỏe tòa nhà và tra cứu thông tin khách thuê. Hãy trả lời thông minh, súc tích và chuyên nghiệp.";

        // Inject System Context to guide AI behavior
        $contents[] = new Content([
            'role' => 'user',
            'parts' => [
                new Part(['text' => "SYSTEM INSTRUCTION (Do not reply to this directly, just follow it): " . $systemInstructionText])
            ],
        ]);
        $contents[] = new Content([
            'role' => 'model',
            'parts' => [
                new Part(['text' => "Đã rõ. Tôi sẽ tuân thủ chỉ dẫn này."])
            ],
        ]);

        // Add previous messages
        foreach ($history as $msg) {
            $role = $msg['role'] === 'user' ? 'user' : 'model';
            $contents[] = new Content([
                'role' => $role,
                'parts' => [
                    new Part(['text' => $msg['content']]),
                ],
            ]);
        }

        // Add new user message
        $contents[] = new Content([
            'role' => 'user',
            'parts' => [
                new Part(['text' => $newMessage]),
            ],
        ]);

        return $contents;
    }

    /**
     * Build tools/functions available to AI
     */
    private function buildTools(): Tool
    {
        $toolDefinitions = $this->toolService->getToolDefinitions();
        
        $functions = array_map(function ($tool) {
            return new FunctionDeclaration([
                'name' => $tool['name'],
                'description' => $tool['description'],
                'parameters' => $this->buildSchemaFromProperties($tool['parameters']),
            ]);
        }, $toolDefinitions);

        return new Tool([
            'function_declarations' => $functions,
        ]);
    }

    /**
     * Convert parameter schema to Google Cloud Schema
     */
    private function buildSchemaFromProperties(array $parameters): Schema
    {
        $properties = [];
        $required = [];

        foreach ($parameters as $name => $param) {
            $schema = new Schema([
                'type' => Type::STRING, // Simplified, can be extended
            ]);

            if (isset($param['description'])) {
                $schema->setDescription($param['description']);
            }

            $properties[$name] = $schema;

            if ($param['required'] ?? false) {
                $required[] = $name;
            }
        }

        $schemaData = [
            'type' => Type::OBJECT,
        ];

        if (!empty($properties)) {
            $schemaData['properties'] = $properties;
        }
        
        if (!empty($required)) {
            $schemaData['required'] = $required;
        }

        return new Schema($schemaData);
    }

    /**
     * Process AI response and handle tool calls
     */
    private function processResponse($response, array $history, string $message): array
    {
        $responseContent = $response->getCandidates()[0] ?? null;
        
        if (!$responseContent) {
            return [
                'success' => false,
                'error' => 'No response from AI',
                'message' => 'I apologize, but I could not generate a response.',
            ];
        }

        $parts = $responseContent->getContent()->getParts();
        $textResponse = '';
        $toolCalls = [];
        $toolResults = [];

        // Process response parts
        foreach ($parts as $part) {
            if ($part->getText()) {
                $textResponse .= $part->getText();
            }

            // Handle function calls
            if ($part->getFunctionCall()) {
                $functionCall = $part->getFunctionCall();
                $toolCall = [
                    'name' => $functionCall->getName(),
                    'args' => $functionCall->getArgs()->serializeToJsonString(),
                ];
                $toolCalls[] = $toolCall;

                // Execute the tool
                $result = $this->executeTool($toolCall['name'], json_decode($toolCall['args'], true));
                $toolResults[] = [
                    'tool_name' => $toolCall['name'],
                    'result' => $result,
                ];
            }
        }

        // If there were tool calls, recursively continue conversation
        if (!empty($toolCalls)) {
            $updatedHistory = $history;
            $updatedHistory[] = ['role' => 'user', 'content' => $message];
            $updatedHistory[] = ['role' => 'assistant', 'content' => $textResponse];

            // Add tool results to history
            foreach ($toolResults as $result) {
                $updatedHistory[] = [
                    'role' => 'tool',
                    'content' => json_encode($result),
                    'tool_name' => $result['tool_name'],
                ];
            }

            // Continue conversation with tool results
            return $this->chat('', $updatedHistory);
        }

        return [
            'success' => true,
            'message' => $textResponse,
            'tool_calls' => $toolCalls,
            'tool_results' => $toolResults,
            'history' => [
                ...$history,
                ['role' => 'user', 'content' => $message],
                ['role' => 'assistant', 'content' => $textResponse],
            ],
        ];
    }

    /**
     * Execute a tool/function
     */
    private function executeTool(string $toolName, array $args): array
    {
        return $this->toolService->executeTool($toolName, $args);
    }

    /**
     * Get full model path for Vertex AI
     */
    private function getModelPath(): string
    {
        return "projects/{$this->projectId}/locations/{$this->location}/publishers/google/models/{$this->modelId}";
    }

    /**
     * Stream chat response (for real-time updates)
     */
    public function streamChat(string $message, array $history, callable $callback)
    {
        // Implementation for streaming responses
        // This would use Server-Sent Events (SSE)
        try {
            $response = $this->chat($message, $history);
            $callback($response);
        } catch (Exception $e) {
            Log::error('Stream Chat Error: ' . $e->getMessage());
            $callback([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
