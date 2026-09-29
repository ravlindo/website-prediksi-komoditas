<?php

namespace Tests\Unit;

use App\Services\PredictionImportService;
use PHPUnit\Framework\TestCase;

class PredictionImportServiceTest extends TestCase
{
    public function test_high_mape_is_not_published_as_layak(): void
    {
        $result = (new PredictionImportService)->normalize('LAYAK', 'FLUKTUATIF', 17.2);
        $this->assertSame('BELUM LAYAK', $result['status']);
    }

    public function test_constant_series_is_labeled_stabil(): void
    {
        $result = (new PredictionImportService)->normalize('LAYAK', 'KONSTAN', 0.0);
        $this->assertSame('STABIL', $result['status']);
    }

    public function test_prediction_is_always_inside_normalized_interval(): void
    {
        [$lower, $upper, $adjusted] = (new PredictionImportService)->normalizeInterval(36000, 35662.5, 35957.5);
        $this->assertSame(36000.0, $upper);
        $this->assertLessThanOrEqual(36000, $lower);
        $this->assertTrue($adjusted);
    }

    public function test_accuracy_is_derived_from_mape_and_bounded(): void
    {
        $service = new PredictionImportService;
        $this->assertSame(97.21, $service->accuracy(2.79));
        $this->assertSame(0.0, $service->accuracy(140));
    }

    public function test_low_mape_is_still_held_when_model_does_not_beat_naive(): void
    {
        $result = (new PredictionImportService)->normalize('BELUM LAYAK', 'VARIATIF', 9.17, 2907, 1518, false);
        $this->assertSame('BELUM LAYAK', $result['status']);
        $this->assertStringContainsString('Naive', $result['reason']);
        $this->assertStringContainsString('9.17', $result['reason']);
    }

    public function test_claimed_layak_result_is_still_held_when_naive_wins(): void
    {
        $result = (new PredictionImportService)->normalize('LAYAK', 'FLUKTUATIF', 7.5, 2100, 1600, false);
        $this->assertSame('BELUM LAYAK', $result['status']);
        $this->assertStringContainsString('belum mengungguli Naive', $result['reason']);
    }
}
