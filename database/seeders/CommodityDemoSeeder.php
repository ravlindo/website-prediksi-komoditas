<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Commodity;
use App\Models\CommodityPrice;
use App\Models\Market;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CommodityDemoSeeder extends Seeder
{
    public function run(): void
    {
        $catalogue = [
            'Beras' => [['Beras Premium', 'kg', 15100], ['Beras Medium', 'kg', 13200]],
            'Gula' => [['Gula Kristal Putih', 'kg', 17500]],
            'Minyak Goreng' => [['Minyak Goreng Curah', 'liter', 18000], ['Minyak Goreng Kemasan Premium', 'liter', 22000], ['Minyakita', 'liter', 16500]],
            'Daging' => [['Daging Sapi', 'kg', 128000], ['Daging Ayam Ras', 'kg', 34500], ['Daging Ayam Kampung', 'ekor', 72000]],
            'Telur' => [['Telur Ayam Ras', 'kg', 29500], ['Telur Ayam Kampung', 'kg', 47000]],
            'Cabai' => [['Cabai Rawit Merah', 'kg', 68500], ['Cabai Merah Besar', 'kg', 51000], ['Cabai Merah Keriting', 'kg', 56000]],
            'Bawang' => [['Bawang Merah', 'kg', 42000], ['Bawang Putih', 'kg', 39000]],
            'Tepung' => [['Tepung Terigu Protein Sedang', 'kg', 12500], ['Tepung Terigu Protein Tinggi', 'kg', 14500]],
        ];

        $markets = collect([
            ['name' => 'Pasar Tanjung Anyar', 'district' => 'Mojosari'],
            ['name' => 'Pasar Sawahan', 'district' => 'Mojosari'],
            ['name' => 'Pasar Bagusan', 'district' => 'Gedeg'],
            ['name' => 'Pasar Pohjejer', 'district' => 'Gondang'],
            ['name' => 'Pasar Raya Mojosari', 'district' => 'Mojosari'],
            ['name' => 'Pasar Pacet', 'district' => 'Pacet'],
            ['name' => 'Pasar Trawas', 'district' => 'Trawas'],
        ])->map(fn (array $market) => Market::updateOrCreate(['name' => $market['name']], $market));

        $commodities = collect();
        foreach ($catalogue as $categoryName => $items) {
            $category = Category::updateOrCreate(['slug' => Str::slug($categoryName)], ['name' => $categoryName]);
            foreach ($items as [$name, $unit, $basePrice]) {
                $commodity = Commodity::updateOrCreate(
                    ['slug' => Str::slug($name)],
                    ['category_id' => $category->id, 'name' => $name, 'unit' => $unit, 'is_active' => true],
                );
                $commodities->push([$commodity, $basePrice]);
            }
        }

        $endDate = CarbonImmutable::create(2026, 7, 23);
        foreach ($commodities as $commodityIndex => [$commodity, $basePrice]) {
            foreach ($markets as $marketIndex => $market) {
                for ($day = 34; $day >= 0; $day--) {
                    $date = $endDate->subDays($day);
                    $wave = (($commodityIndex * 7 + $marketIndex * 3 + $day * 5) % 11) - 5;
                    $marketAdjustment = ($marketIndex - 3) * max(100, (int) ($basePrice * 0.003));
                    $price = max(1000, $basePrice + $marketAdjustment + ($wave * max(50, (int) ($basePrice * 0.002))));
                    CommodityPrice::updateOrCreate(
                        ['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price_date' => $date->toDateString()],
                        ['price' => $price, 'source' => 'Seeder pengembangan'],
                    );
                }
            }
        }
    }
}
