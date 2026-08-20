<?php

namespace App\Services;

use App\DTOs\DriveFileDTO;
use App\Exceptions\GoogleDriveApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

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
    public function listFiles(string $folderId): array
    {
        $pageToken = null;
        $files = [];

        try {
            do {
                $response = Http::timeout(15)
                    ->retry(2, 500, throw: false)
                    ->get(self::API_BASE_URL, array_filter([
                        'q' => "'{$folderId}' in parents",
                        'fields' => 'nextPageToken, files(id, name, mimeType)',
                        'pageSize' => 1000,
                        'pageToken' => $pageToken,
                        'key' => config('services.google_drive.api_key'),
                    ]));

                if ($response->failed()) {
                    throw $this->mapHttpError($response->status());
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

    private function mapHttpError(int $status): GoogleDriveApiException
    {
        return match (true) {
            $status === 403 => new GoogleDriveApiException("Folder Google Drive tidak dapat diakses. Pastikan folder dishare 'Anyone with the link can view'."),
            $status === 429 => new GoogleDriveApiException('Google Drive API rate limit (429). Coba lagi nanti.'),
            $status === 0 => new GoogleDriveApiException('Google Drive API tidak dapat dijangkau. Coba lagi nanti.'),
            $status >= 500 => new GoogleDriveApiException("Google Drive API sedang bermasalah (HTTP {$status}). Coba lagi nanti."),
            default => new GoogleDriveApiException("Google Drive API gagal (HTTP {$status}). Coba lagi nanti."),
        };
    }
}
