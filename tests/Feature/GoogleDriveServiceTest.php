<?php

namespace Tests\Feature;

use App\DTOs\DriveFileDTO;
use App\Exceptions\GoogleDriveApiException;
use App\Services\GoogleDriveService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleDriveServiceTest extends TestCase
{
    private GoogleDriveService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(GoogleDriveService::class);
    }

    public function test_list_files_returns_dto_for_single_page(): void
    {
        config(['services.google_drive.api_key' => 'test-key-123']);

        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'files' => [['id' => 'f1', 'name' => 'a.jpg', 'mimeType' => 'image/jpeg']],
                'nextPageToken' => null,
            ], 200),
        ]);

        $files = $this->service->listFiles('FOLDER');

        $this->assertCount(1, $files);
        $this->assertInstanceOf(DriveFileDTO::class, $files[0]);
        $this->assertSame('f1', $files[0]->id);
        $this->assertSame('image', $files[0]->type);

        Http::assertSent(function ($request) {
            return str_contains($request['q'], "'FOLDER' in parents")
                && $request['key'] === 'test-key-123';
        });
    }

    public function test_list_files_merges_pagination_pages(): void
    {
        Http::fakeSequence()
            ->push(['files' => [['id' => 'f1', 'name' => 'a.jpg', 'mimeType' => 'image/jpeg']], 'nextPageToken' => 'tok2'], 200)
            ->push(['files' => [['id' => 'f2', 'name' => 'b.jpg', 'mimeType' => 'image/jpeg']], 'nextPageToken' => null], 200);

        $files = $this->service->listFiles('FOLDER');

        $this->assertCount(2, $files);
        $this->assertSame('f2', $files[1]->id);

        Http::assertSent(function ($request) {
            $token = $request['pageToken'] ?? null;

            return $token === 'tok2';
        });
    }

    public function test_list_files_maps_403_to_exception(): void
    {
        Http::fake(['www.googleapis.com/*' => Http::response([], 403)]);

        $this->expectException(GoogleDriveApiException::class);
        $this->expectExceptionMessage('tidak dapat diakses');

        $this->service->listFiles('FOLDER');
    }

    public function test_list_files_maps_429_to_rate_limit_exception(): void
    {
        Http::fake(['www.googleapis.com/*' => Http::response([], 429)]);

        $this->expectException(GoogleDriveApiException::class);
        $this->expectExceptionMessage('rate limit');

        $this->service->listFiles('FOLDER');
    }

    public function test_list_files_maps_5xx_to_exception(): void
    {
        Http::fake(['www.googleapis.com/*' => Http::response([], 503)]);

        $this->expectException(GoogleDriveApiException::class);
        $this->expectExceptionMessage('HTTP 503');

        $this->service->listFiles('FOLDER');
    }

    public function test_list_files_rejects_malformed_response(): void
    {
        Http::fake(['www.googleapis.com/*' => Http::response(['foo' => 'bar'], 200)]);

        $this->expectException(GoogleDriveApiException::class);
        $this->expectExceptionMessage('tidak valid');

        $this->service->listFiles('FOLDER');
    }

    public function test_list_files_maps_network_error_to_exception(): void
    {
        Http::fake(['www.googleapis.com/*' => fn ($request) => throw new ConnectionException('timeout')]);

        $this->expectException(GoogleDriveApiException::class);
        $this->expectExceptionMessage('tidak dapat dijangkau');

        $this->service->listFiles('FOLDER');
    }

    public function test_list_files_always_hits_googleapis_host(): void
    {
        Http::fake(['www.googleapis.com/*' => Http::response(['files' => [], 'nextPageToken' => null], 200)]);

        $files = $this->service->listFiles('FOLDER');

        $this->assertEmpty($files);

        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://www.googleapis.com/drive/v3/files'));
    }

    public function test_extract_folder_id(): void
    {
        $this->assertSame('1AbCdEfGhIjKlMnOpQrStUv', $this->service->extractFolderId('https://drive.google.com/drive/folders/1AbCdEfGhIjKlMnOpQrStUv'));
        $this->assertNull($this->service->extractFolderId('https://drive.google.com/file/d/XYZ/view'));
        $this->assertNull($this->service->extractFolderId('https://example.com/x'));
    }
}
