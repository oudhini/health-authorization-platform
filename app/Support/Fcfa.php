<?php

declare(strict_types=1);

namespace App\Support;

/** Display helper: 12000 becomes "12 000 FCFA". Amounts are always integers. */
final class Fcfa
{
    public static function format(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }
}
