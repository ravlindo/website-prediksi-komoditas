<?php

namespace Tests\Feature;

use App\Services\MarketPredictionEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class MarketPredictionEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_source_is_normalized_to_one_winning_model_per_pair_and_date(): void
    {
        $data = app(MarketPredictionEvaluationService::class)->build('Bawang Merah', 'Kedungmaling', 74);

        $this->assertSame(15, $data['summary']['commodities']);
        $this->assertSame(2, $data['summary']['markets']);
        $this->assertSame(30, $data['summary']['pairs']);
        $this->assertSame(2220, $data['summary']['points']);
        $this->assertCount(74, $data['chart']['points']);
        $this->assertSame('SARIMA', $data['result']['model']);
        $this->assertSame(4.04, $data['result']['mape']);
    }

    public function test_public_market_comparison_can_filter_commodity_and_period(): void
    {
        $this->get(route('predictions.market-evaluation', [
            'commodity' => 'Bawang Putih Sinco/Honan',
            'period' => 30,
        ]))
            ->assertOk()
            ->assertSee('Perbandingan Harga 2 Pasar Utama')
            ->assertSee('Perbandingan & Prediksi Harga', false)
            ->assertSee('Bawang Putih Sinco/Honan')
            ->assertSee('Mojosari')
            ->assertSee('Kedungmaling')
            ->assertSee('Gap harga antar pasar')
            ->assertSee('data-two-market-chart', false);
    }

    public function test_technical_tab_shows_metrics_selected_chart_and_model_screening(): void
    {
        $this->get(route('predictions.market-evaluation', [
            'section' => 'model',
            'commodity' => 'Bawang Putih Sinco/Honan',
            'market' => 'Mojosari',
            'period' => 30,
        ]))
            ->assertOk()
            ->assertSee('Detail Model & Akurasi', false)
            ->assertSee('Skor akurasi')
            ->assertSee('98,06/100')
            ->assertSee('2.220')
            ->assertSee('SCREENING 3 MODEL')
            ->assertSee('Tidak diekspor')
            ->assertSee('data-market-evaluation-chart', false);
    }

    public function test_main_prediction_page_exposes_the_new_tab_without_replacing_existing_content(): void
    {
        $this->get(route('predictions.index'))
            ->assertOk()
            ->assertSee('Prediksi Harga')
            ->assertSee('Perbandingan Harga 2 Pasar Utama');
    }

    public function test_comparison_and_model_tables_are_paginated_ten_rows_per_page(): void
    {
        $this->get(route('predictions.market-evaluation', [
            'commodity' => 'Bawang Merah',
            'period' => 30,
        ]))
            ->assertOk()
            ->assertViewHas('evaluation', function (array $evaluation): bool {
                $paginator = $evaluation['comparison']['table'];

                return $paginator instanceof LengthAwarePaginator
                    && $paginator->perPage() === 10
                    && $paginator->count() === 10
                    && $paginator->total() === 30;
            })
            ->assertSee('Menampilkan <strong>1&ndash;10</strong> dari <strong>30</strong> data', false);

        $this->get(route('predictions.market-evaluation', [
            'section' => 'model',
            'commodity' => 'Bawang Merah',
            'market' => 'Mojosari',
            'period' => 30,
            'page' => 2,
        ]))
            ->assertOk()
            ->assertViewHas('evaluation', function (array $evaluation): bool {
                $paginator = $evaluation['modelComparison'];

                return $paginator instanceof LengthAwarePaginator
                    && $paginator->perPage() === 10
                    && $paginator->count() === 10
                    && $paginator->total() === 30
                    && $paginator->currentPage() === 2;
            })
            ->assertSee('Menampilkan <strong>11&ndash;20</strong> dari <strong>30</strong> data', false);
    }
}
