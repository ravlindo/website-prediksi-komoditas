<?php

namespace Tests\Unit;

use App\Services\HolidayCalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Tests\TestCase;

class HolidayCalendarServiceTest extends TestCase
{
    public function test_it_recognizes_national_holiday_and_collective_leave(): void
    {
        $calendar = app(HolidayCalendarService::class);

        $this->assertSame('Hari Proklamasi Kemerdekaan', $calendar->find('2026-08-17')['name']);
        $this->assertSame('national', $calendar->find('2026-08-17')['type']);
        $this->assertSame('collective_leave', $calendar->find('2026-12-24')['type']);
        $this->assertNull($calendar->find('2026-08-18'));
    }

    public function test_it_summarizes_workdays_weekends_and_holidays_without_double_counting(): void
    {
        $calendar = app(HolidayCalendarService::class);
        $coordinates = new Collection([
            $this->point('2026-08-15', $calendar),
            $this->point('2026-08-16', $calendar),
            $this->point('2026-08-17', $calendar),
            $this->point('2026-08-18', $calendar),
        ]);

        $summary = $calendar->summarize($coordinates);

        $this->assertSame(1, $summary['working_days']);
        $this->assertSame(2, $summary['weekends']);
        $this->assertSame(1, $summary['national']);
        $this->assertSame(0, $summary['collective_leave']);
        $this->assertSame(2, $summary['holidays']->first()['point_index']);
    }

    public function test_it_returns_the_next_official_holiday(): void
    {
        $next = app(HolidayCalendarService::class)->nextAfter('2026-08-12');

        $this->assertSame('2026-08-17', $next['date']->toDateString());
        $this->assertSame('Hari Proklamasi Kemerdekaan', $next['name']);
    }

    private function point(string $date, HolidayCalendarService $calendar): array
    {
        $carbon = CarbonImmutable::parse($date);

        return ['date' => $carbon, 'holiday' => $calendar->find($carbon)];
    }
}
