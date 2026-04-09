<?php

declare(strict_types=1);

namespace App\Helpers;

use DateTimeImmutable;
use DateTimeInterface;

class DateHelper
{
    public static function format(?string $datetime, string $format = 'd M Y'): string
    {
        if ($datetime === null || $datetime === '') {
            return '—';
        }
        return (new DateTimeImmutable($datetime))->format($format);
    }

    public static function formatDateTime(?string $datetime): string
    {
        return self::format($datetime, 'd M Y H:i');
    }

    public static function isOverdue(?string $reviewDate): bool
    {
        if ($reviewDate === null || $reviewDate === '') {
            return false;
        }
        return (new DateTimeImmutable($reviewDate)) < new DateTimeImmutable('today');
    }

    public static function isDueSoon(?string $reviewDate, int $daysAhead = 14): bool
    {
        if ($reviewDate === null || $reviewDate === '') {
            return false;
        }
        $date     = new DateTimeImmutable($reviewDate);
        $today    = new DateTimeImmutable('today');
        $threshold = $today->modify("+$daysAhead days");
        return $date >= $today && $date <= $threshold;
    }

    public static function diffForHumans(?string $datetime): string
    {
        if ($datetime === null || $datetime === '') {
            return 'never';
        }
        $then = new DateTimeImmutable($datetime);
        $now  = new DateTimeImmutable();
        $diff = $now->diff($then);

        if ($diff->days === 0) {
            return 'today';
        }
        if ($diff->days === 1) {
            return $diff->invert ? 'yesterday' : 'tomorrow';
        }
        if ($diff->days < 7) {
            return ($diff->invert ? '' : 'in ') . $diff->days . ' days' . ($diff->invert ? ' ago' : '');
        }
        return self::format($datetime);
    }
}
