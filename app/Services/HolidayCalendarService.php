<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class HolidayCalendarService
{
    public function find(CarbonInterface|string $date): ?array
    {
        $dateKey = $date instanceof CarbonInterface ? $date->toDateString() : CarbonImmutable::parse($date)->toDateString();
        $holiday = config("holidays.dates.{$dateKey}");

        if (! is_array($holiday)) {
            return null;
        }

        return [
            'date' => CarbonImmutable::parse($dateKey),
            'name' => $holiday['name'],
            'type' => $holiday['type'],
            'type_label' => $holiday['type'] === 'collective_leave' ? 'Cuti bersama' : 'Libur nasional',
        ];
    }

    public function nextAfter(CarbonInterface|string|null $date): ?array
    {
        if (! $date) {
            return null;
        }

        $dateKey = $date instanceof CarbonInterface ? $date->toDateString() : CarbonImmutable::parse($date)->toDateString();
        $nextDate = collect(config('holidays.dates', []))->keys()->sort()->first(fn (string $holidayDate) => $holidayDate > $dateKey);

        return $nextDate ? $this->find($nextDate) : null;
    }

    public function summarize(Collection $coordinates): array
    {
        $holidays = $coordinates->values()->map(function (array $point, int $index) {
            return $point['holiday'] ? [...$point['holiday'], 'point_index' => $index] : null;
        })->filter()->values();
        $holidayDates = $holidays->pluck('date')->map->toDateString()->all();
        $weekends = $coordinates->filter(fn (array $point) => $point['date']->isWeekend() && ! in_array($point['date']->toDateString(), $holidayDates, true))->count();

        return [
            'holidays' => $holidays,
            'national' => $holidays->where('type', 'national')->count(),
            'collective_leave' => $holidays->where('type', 'collective_leave')->count(),
            'weekends' => $weekends,
            'working_days' => max(0, $coordinates->count() - $holidays->count() - $weekends),
        ];
    }
}
