<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InsightFaceService
{
    protected string $serviceUrl;

    public function __construct()
    {
        $this->serviceUrl = config('services.insightface.url', 'http://127.0.0.1:5000');
    }

    /**
     * Extract 512-d embedding vector from base64 image string.
     */
    public function extractEmbedding(string $base64Image): array
    {
        try {
            $response = Http::timeout(15)->post("{$this->serviceUrl}/extract-embedding-base64", [
                'image' => $base64Image,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            $errorJson = $response->json();

            return [
                'success' => false,
                'message' => $errorJson['message'] ?? $errorJson['detail'] ?? 'Gagal mengekstrak fitur wajah dengan Python Service.',
            ];
        } catch (\Exception $e) {
            Log::error('InsightFace extraction error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Python InsightFace Service tidak aktif (Port 5000). Silakan jalankan `python python_service/app.py`.',
            ];
        }
    }

    /**
     * Verify live base64 snapshot against target student face embedding.
     */
    public function verifyFace(string $base64Image, array $targetEmbedding, float $threshold = 0.40): array
    {
        try {
            $response = Http::timeout(15)->post("{$this->serviceUrl}/verify-face", [
                'image' => $base64Image,
                'target_embedding' => $targetEmbedding,
                'threshold' => $threshold,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            $errorJson = $response->json();

            return [
                'matched' => false,
                'similarity' => 0.0,
                'message' => $errorJson['message'] ?? $errorJson['detail'] ?? 'Gagal memverifikasi dengan service InsightFace.',
            ];
        } catch (\Exception $e) {
            Log::error('InsightFace verification error: '.$e->getMessage());

            return [
                'matched' => false,
                'similarity' => 0.0,
                'message' => 'Python InsightFace Service tidak aktif (Port 5000). Silakan jalankan `python python_service/app.py`.',
            ];
        }
    }

    /**
     * Automatic 1-to-N face identification against all registered students.
     */
    public function identifyFace(string $base64Image, array $students, float $threshold = 0.38): array
    {
        try {
            $response = Http::timeout(15)->post("{$this->serviceUrl}/identify-face", [
                'image' => $base64Image,
                'students' => $students,
                'threshold' => $threshold,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            $errorJson = $response->json();

            return [
                'matched' => false,
                'similarity' => 0.0,
                'message' => $errorJson['message'] ?? $errorJson['detail'] ?? 'Gagal mengidentifikasi dengan service InsightFace.',
            ];
        } catch (\Exception $e) {
            Log::error('InsightFace identification error: '.$e->getMessage());

            return [
                'matched' => false,
                'similarity' => 0.0,
                'message' => 'Python InsightFace Service tidak aktif (Port 5000). Silakan jalankan `python python_service/app.py`.',
            ];
        }
    }
}
