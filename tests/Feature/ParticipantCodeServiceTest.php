<?php

namespace Tests\Feature;

use App\Services\ParticipantCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use OverflowException;
use Tests\TestCase;

class ParticipantCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): ParticipantCodeService
    {
        return app(ParticipantCodeService::class);
    }

    public function test_member_prefix_starts_at_0001(): void
    {
        $this->assertSame('0001', $this->service()->next(''));
    }

    public function test_non_member_prefix_starts_at_nm0001(): void
    {
        $this->assertSame('NM0001', $this->service()->next('NM'));
    }

    public function test_sequences_increment_sequentially(): void
    {
        $service = $this->service();

        $this->assertSame('0001', $service->next(''));
        $this->assertSame('0002', $service->next(''));
        $this->assertSame('0003', $service->next(''));
    }

    public function test_prefixes_are_independent(): void
    {
        $service = $this->service();

        $this->assertSame('0001', $service->next(''));
        $this->assertSame('NM0001', $service->next('NM'));
        $this->assertSame('0002', $service->next(''));
        $this->assertSame('NM0002', $service->next('NM'));
    }

    public function test_overflow_throws_and_rolls_back(): void
    {
        DB::table('participant_code_sequences')->insert([
            'prefix' => 'NM',
            'last_value' => 9999,
        ]);

        try {
            $this->service()->next('NM');
            $this->fail('OverflowException was not thrown at 9999.');
        } catch (OverflowException $e) {
            $this->assertStringContainsString('9999', $e->getMessage());
        }

        $this->assertSame(
            9999,
            DB::table('participant_code_sequences')->where('prefix', 'NM')->value('last_value'),
        );
    }

    public function test_codes_never_duplicate_across_many_calls(): void
    {
        $service = $this->service();
        $codes = [];

        for ($i = 0; $i < 100; $i++) {
            $codes[] = $service->next('');
            $codes[] = $service->next('NM');
        }

        $this->assertSame(200, count($codes));
        $this->assertSame(200, count(array_unique($codes)));
    }
}
