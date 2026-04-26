<?php

namespace App\Services;

use Illuminate\Support\Env;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class VertexAiService
{
    protected ?string $accessToken = null;

    /**
     * Sau `php artisan config:cache`, giá trị từ env() có thể bị cố định sai trong bootstrap/cache.
     * Trên Docker production biến được inject qua Compose — phải đọc getenv() trước config().
     */
    public static function resolveCredentialsPath(): string
    {
        $path = (string) Env::get('GOOGLE_APPLICATION_CREDENTIALS', '');
        if ($path === '') {
            $path = (string) config('services.vertex_ai.credentials_path');
        }

        if ($path !== '' && is_readable($path)) {
            return $path;
        }

        $fallback = base_path('app/API_KEYS/key.json');
        if (is_readable($fallback)) {
            return $fallback;
        }

        return $path;
    }

    public static function resolveCredentialsJson(): string
    {
        $raw = Env::get('VERTEX_AI_SERVICE_ACCOUNT_JSON');

        return (is_string($raw) && $raw !== '')
            ? $raw
            : (string) config('services.vertex_ai.credentials_json');
    }

    public static function resolveProjectId(): string
    {
        foreach (['GOOGLE_CLOUD_PROJECT_ID', 'GOOGLE_CLOUD_PROJECT'] as $key) {
            $v = Env::get($key);
            if (is_string($v) && $v !== '') {
                return $v;
            }
        }

        return (string) config('services.vertex_ai.project_id');
    }

    /**
     * Gửi nội dung đến Vertex AI Gemini bằng service account OAuth.
     *
     * @param array $contents Payload contents theo format Gemini.
     * @param array $generationConfig Cấu hình sinh nội dung.
     * @return array Phản hồi JSON từ Vertex AI.
     */
    public function generateContent(
        array $contents,
        array $generationConfig = [],
        ?array $tools = null,
        ?array $systemInstruction = null,
        ?array $toolConfig = null,
    ): array {
        $projectId = self::resolveProjectId();
        $locationEnv = Env::get('GOOGLE_CLOUD_LOCATION');
        $location = (is_string($locationEnv) && $locationEnv !== '')
            ? $locationEnv
            : (string) config('services.vertex_ai.location', 'us-central1');
        $modelEnv = Env::get('VERTEX_AI_MODEL');
        $model = (is_string($modelEnv) && $modelEnv !== '')
            ? $modelEnv
            : (string) config('services.vertex_ai.model', 'gemini-2.5-flash');

        if ($projectId === '') {
            throw new RuntimeException('Thiếu GOOGLE_CLOUD_PROJECT_ID trong cấu hình Vertex AI.');
        }

        $accessToken = $this->getAccessToken();
        $endpoint = $this->buildEndpoint($projectId, $location, $model);

        $payload = [
            'contents' => $contents,
        ];

        if (!empty($generationConfig)) {
            $payload['generationConfig'] = $generationConfig;
        }

        if ($systemInstruction !== null) {
            $payload['systemInstruction'] = $systemInstruction;
        }

        if ($tools !== null) {
            $payload['tools'] = $tools;
        }

        if ($toolConfig !== null) {
            $payload['toolConfig'] = $toolConfig;
        }

        Log::debug('Vertex AI: Calling API', [
            'endpoint' => $endpoint,
            'model' => $model,
            'has_auth_token' => !empty($accessToken),
        ]);

        $response = Http::timeout(30)
            ->acceptJson()
            ->asJson()
            ->withToken($accessToken)
            ->post($endpoint, $payload);

        Log::debug('Vertex AI: API response status', [
            'status' => $response->status(),
            'successful' => $response->successful(),
        ]);

        if (! $response->successful()) {
            $error = $response->json();

            Log::error('Vertex AI API error', [
                'status' => $response->status(),
                'endpoint' => $endpoint,
                'model' => $model,
                'error' => $error,
            ]);

            $message = $error['error']['message'] ?? 'Lỗi khi kết nối với Vertex AI';

            throw new RuntimeException($message, $response->status());
        }

        $data = $response->json();

        if (!is_array($data)) {
            throw new RuntimeException('Vertex AI trả về dữ liệu không hợp lệ.');
        }

        return $data;
    }

    protected function buildEndpoint(string $projectId, string $location, string $model): string
    {
        return sprintf(
            'https://%s-aiplatform.googleapis.com/v1/projects/%s/locations/%s/publishers/google/models/%s:generateContent',
            $location,
            $projectId,
            $location,
            $model
        );
    }

    protected function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $credentials = $this->loadCredentials();
        $tokenUri = $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token';
        $clientEmail = $credentials['client_email'] ?? null;
        $privateKey = $credentials['private_key'] ?? null;

        if (!$clientEmail || !$privateKey) {
            throw new RuntimeException('Service account JSON phải chứa client_email và private_key.');
        }

        Log::debug('Vertex AI OAuth: Generating JWT', [
            'client_email' => $clientEmail,
            'token_uri' => $tokenUri,
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
        ]);

        $jwt = $this->createJwtAssertion($clientEmail, $privateKey, $tokenUri);

        $response = Http::asForm()
            ->timeout(30)
            ->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

        Log::debug('Vertex AI OAuth: Token exchange response', [
            'status' => $response->status(),
            'has_access_token' => $response->json() && isset($response->json()['access_token']),
        ]);

        if (! $response->successful()) {
            $error = $response->json();

            Log::error('Google OAuth token exchange failed', [
                'status' => $response->status(),
                'error' => $error,
                'request_grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'token_uri' => $tokenUri,
            ]);

            $message = $error['error_description'] ?? $error['error'] ?? 'Không thể lấy access token cho Vertex AI';

            throw new RuntimeException($message, $response->status());
        }

        $data = $response->json();
        $accessToken = $data['access_token'] ?? null;

        if (!$accessToken) {
            throw new RuntimeException('Thiếu access_token trong phản hồi OAuth của Google.');
        }

        $this->accessToken = $accessToken;

        return $this->accessToken;
    }

    protected function loadCredentials(): array
    {
        $credentialsPath = self::resolveCredentialsPath();
        $credentialsJson = self::resolveCredentialsJson();

        if ($credentialsPath !== '' && is_readable($credentialsPath)) {
            $contents = file_get_contents($credentialsPath);
            $credentials = json_decode($contents ?: '', true);
        } elseif ($credentialsJson !== '') {
            $credentials = json_decode($credentialsJson, true);
        } else {
            Log::error('Vertex AI: không có credential dùng được', [
                'path' => $credentialsPath !== '' ? $credentialsPath : null,
                'path_exists' => $credentialsPath !== '' && file_exists($credentialsPath),
                'path_readable' => $credentialsPath !== '' && is_readable($credentialsPath),
                'has_inline_json' => $credentialsJson !== '',
            ]);

            throw new RuntimeException('Thiếu GOOGLE_APPLICATION_CREDENTIALS hoặc VERTEX_AI_SERVICE_ACCOUNT_JSON.');
        }

        if (!is_array($credentials)) {
            throw new RuntimeException('Service account JSON không hợp lệ.');
        }

        return $credentials;
    }

    protected function createJwtAssertion(string $clientEmail, string $privateKey, string $tokenUri): string
    {
        $now = time();

        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_UNESCAPED_SLASHES));

        $payloadData = [
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            'aud' => $tokenUri,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        Log::debug('Vertex AI OAuth: JWT payload', [
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            'aud' => $tokenUri,
            'iat' => $now,
            'exp' => $now + 3600,
        ]);

        $payload = $this->base64UrlEncode(json_encode($payloadData, JSON_UNESCAPED_SLASHES));

        $signingInput = $header . '.' . $payload;
        $signature = '';
        $key = openssl_pkey_get_private($privateKey);

        if ($key === false) {
            throw new RuntimeException('Không thể đọc private_key của service account.');
        }

        $signed = openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256);

        if (! $signed) {
            throw new RuntimeException('Không thể ký JWT cho Google OAuth.');
        }

        return $signingInput . '.' . $this->base64UrlEncode($signature);
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}