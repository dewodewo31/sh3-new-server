<?php

namespace App\Services;

use App\DTOs\DriveFileDTO;
use App\Exceptions\GoogleDriveApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    private const API_BASE_URL = 'https://www.googleapis.com/drive/v3/files';

    /**
     * List all files inside a folder (paginated, merged) as DriveFileDTO objects.
     *
     * The base URL is hardcoded; the folder id is only used inside the `q`
     * query parameter — user input can never become the outbound host (anti-SSRF).
     *
     * @return DriveFileDTO[]
     *
     * @throws GoogleDriveApiException
     */
    public function listFiles(string $folderId, ?string $resourceKey = null): array
    {
        $apiKey = config('services.google_drive.api_key');

        if (empty($apiKey)) {
            throw new GoogleDriveApiException(
                'GOOGLE_DRIVE_API_KEY belum terisi. Set di .env lalu jalankan ulang container.'
            );
        }

        $pageToken = null;
        $files = [];

        try {
            do {
                $request = Http::timeout(15)
                    ->retry(2, 500, throw: false);

                // Link-shared resources may require a resource key (Google Drive security feature).
                if ($resourceKey !== null) {
                    $request = $request->withHeaders([
                        'X-Goog-Drive-Resource-Keys' => "{$folderId}/{$resourceKey}",
                    ]);
                }

                $response = $request->get(self::API_BASE_URL, array_filter([
                    'q' => "'{$folderId}' in parents",
                    'fields' => 'nextPageToken, files(id, name, mimeType)',
                    'pageSize' => 1000,
                    'pageToken' => $pageToken,
                    // Required for Shared Drive folders; no effect on My Drive folders.
                    'supportsAllDrives' => 'true',
                    'includeItemsFromAllDrives' => 'true',
                    'key' => $apiKey,
                ]));

                if ($response->failed()) {
                    throw $this->mapHttpError($response->status(), $response->json(), $folderId, $resourceKey !== null);
                }

                $body = $response->json();

                if (! is_array($body) || ! isset($body['files']) || ! is_array($body['files'])) {
                    throw new GoogleDriveApiException('Respons Google Drive tidak valid.');
                }

                foreach ($body['files'] as $file) {
                    $dto = DriveFileDTO::fromDriveApi($file);

                    if ($dto !== null) {
                        $files[] = $dto;
                    }
                }

                $pageToken = $body['nextPageToken'] ?? null;
            } while ($pageToken);
        } catch (ConnectionException $e) {
            throw new GoogleDriveApiException('Google Drive API tidak dapat dijangkau. Coba lagi nanti.');
        }

        return $files;
    }

    public function extractFolderId(string $url): ?string
    {
        if (preg_match('/drive\.google\.com\/drive\/folders\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract the resource key from a Drive share URL, e.g.
     * https://drive.google.com/drive/folders/ID?usp=sharing&resourcekey=KEY
     */
    public function extractResourceKey(string $url): ?string
    {
        if (preg_match('/[?&]resourcekey=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Map a failed Drive API response to a sanitized exception.
     * Diagnostics (status, reason, google message, folder id, resource key presence)
     * are logged — never the API key or any credential material.
     */
    private function mapHttpError(int $status, ?array $body, string $folderId, bool $hasResourceKey): GoogleDriveApiException
    {
        $reason = $body['error']['details'][0]['reason']
            ?? $body['error']['errors'][0]['reason']
            ?? null;
        $googleMessage = $body['error']['message'] ?? null;

        Log::warning('Google Drive API request gagal', [
            'operation' => 'files.list',
            'status' => $status,
            'reason' => $reason,
            'google_message' => $googleMessage,
            'folder_id' => $folderId,
            'resource_key_present' => $hasResourceKey,
        ]);

        $suffix = $reason ? " [{$reason}]" : '';

        return match (true) {
            $status === 403 && $reason === 'API_KEY_SERVICE_BLOCKED' => new GoogleDriveApiException(
                "API key memblokir Google Drive API. Periksa API restrictions pada API key di Google Cloud Console dan izinkan Google Drive API.{$suffix}"
            ),
            $status === 403 && $reason === 'SERVICE_DISABLED' => new GoogleDriveApiException(
                "Google Drive API belum diaktifkan untuk project API key ini. Aktifkan via Google Cloud Console → Library → Google Drive API.{$suffix}"
            ),
            $status === 403 => new GoogleDriveApiException(
                "Folder Google Drive tidak dapat diakses. Pastikan folder dishare 'Anyone with the link can view'.{$suffix}"
            ),
            $status === 404 => new GoogleDriveApiException(
                "Folder Google Drive tidak ditemukan. Periksa link folder dan pastikan folder publik.{$suffix}"
            ),
            $status === 429 => new GoogleDriveApiException("Google Drive API rate limit (429). Coba lagi nanti.{$suffix}"),
            $status >= 500 => new GoogleDriveApiException("Google Drive API sedang bermasalah (HTTP {$status}). Coba lagi nanti."),
            default => new GoogleDriveApiException("Google Drive API gagal (HTTP {$status}). Coba lagi nanti.{$suffix}"),
        };
    }
}
