<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Chạy vòng lặp Gemini + function calling (REST Vertex AI) và thực thi qua AIToolService.
 */
class VertexAiChatToolsService
{
    public function __construct(
        protected VertexAiService $vertex,
        protected AIToolService $toolService,
    ) {}

    /**
     * @param  array<int, array{role: string, parts: array}>  $contents  Gemini REST contents (đã là user/model)
     * @return array{success: bool, message?: string, error?: string}
     */
    public function chat(array $contents, string $systemInstructionText): array
    {
        $tools = $this->toolService->getGeminiToolsPayload();
        $systemInstruction = [
            'parts' => [['text' => $systemInstructionText]],
        ];
        $generationConfig = [
            'temperature' => 0.45,
            'maxOutputTokens' => 4096,
            'topP' => 0.95,
            'topK' => 40,
        ];
        $toolConfig = [
            'functionCallingConfig' => [
                'mode' => 'AUTO',
            ],
        ];

        $maxIterations = 10;
        for ($i = 0; $i < $maxIterations; $i++) {
            $response = $this->vertex->generateContent(
                $contents,
                $generationConfig,
                $tools,
                $systemInstruction,
                $toolConfig,
            );

            $candidate = $response['candidates'][0] ?? null;
            if (!$candidate) {
                Log::warning('VertexAiChatToolsService: no candidates', ['response' => $response]);

                return [
                    'success' => false,
                    'error' => 'Không có phản hồi từ AI.',
                ];
            }

            $content = $candidate['content'] ?? [];
            $parts = $content['parts'] ?? [];

            $functionCalls = [];
            $textParts = [];
            foreach ($parts as $part) {
                if (!empty($part['text'])) {
                    $textParts[] = $part['text'];
                }
                if (!empty($part['functionCall'])) {
                    $functionCalls[] = $part['functionCall'];
                }
            }

            if ($functionCalls === []) {
                return [
                    'success' => true,
                    'message' => implode('', $textParts),
                ];
            }

            $contents[] = $content;

            $responseParts = [];
            foreach ($functionCalls as $fc) {
                $name = $fc['name'] ?? '';
                $args = [];
                if (isset($fc['args'])) {
                    $args = is_array($fc['args']) ? $fc['args'] : [];
                }
                $result = $this->toolService->executeTool($name, $args);
                $responseParts[] = [
                    'functionResponse' => [
                        'name' => $name,
                        'response' => $result,
                    ],
                ];
            }

            $contents[] = [
                'role' => 'user',
                'parts' => $responseParts,
            ];
        }

        return [
            'success' => false,
            'error' => 'AI thực hiện quá nhiều bước công cụ. Vui lòng thử lại với yêu cầu ngắn hơn.',
        ];
    }
}
