<?php

namespace App\Support;

class NumberToWords
{
    private static array $ones = [
        0 => '',
        1 => 'One',
        2 => 'Two',
        3 => 'Three',
        4 => 'Four',
        5 => 'Five',
        6 => 'Six',
        7 => 'Seven',
        8 => 'Eight',
        9 => 'Nine',
        10 => 'Ten',
        11 => 'Eleven',
        12 => 'Twelve',
        13 => 'Thirteen',
        14 => 'Fourteen',
        15 => 'Fifteen',
        16 => 'Sixteen',
        17 => 'Seventeen',
        18 => 'Eighteen',
        19 => 'Nineteen',
    ];

    private static array $tens = [
        2 => 'Twenty',
        3 => 'Thirty',
        4 => 'Forty',
        5 => 'Fifty',
        6 => 'Sixty',
        7 => 'Seventy',
        8 => 'Eighty',
        9 => 'Ninety',
    ];

    /**
     * Converts a number to Indian currency words format.
     * e.g. 450400 -> "Four Lakh Fifty Thousand Four Hundred Only"
     */
    public static function indianCurrency(float|int|string $amount): string
    {
        $amount = (float) $amount;

        if ($amount == 0) {
            return 'Zero Only';
        }

        $whole = floor($amount);
        $fraction = round(($amount - $whole) * 100);

        $words = self::convertIndianWholeNumber((int) $whole);

        if ($fraction > 0) {
            $fractionWords = self::convertIndianWholeNumber((int) $fraction);
            return trim($words) . ' and ' . trim($fractionWords) . ' Paise Only';
        }

        return trim($words) . ' Only';
    }

    private static function convertIndianWholeNumber(int $num): string
    {
        if ($num == 0) {
            return '';
        }

        $crores = intdiv($num, 10000000);
        $remainder = $num % 10000000;

        $lakhs = intdiv($remainder, 100000);
        $remainder = $remainder % 100000;

        $thousands = intdiv($remainder, 1000);
        $remainder = $remainder % 1000;

        $hundreds = intdiv($remainder, 100);
        $remainder = $remainder % 100;

        $result = '';

        if ($crores > 0) {
            $result .= self::convertTwoDigits($crores) . ' Crore ';
        }

        if ($lakhs > 0) {
            $result .= self::convertTwoDigits($lakhs) . ' Lakh ';
        }

        if ($thousands > 0) {
            $result .= self::convertTwoDigits($thousands) . ' Thousand ';
        }

        if ($hundreds > 0) {
            $result .= self::$ones[$hundreds] . ' Hundred ';
        }

        if ($remainder > 0) {
            $result .= self::convertTwoDigits($remainder) . ' ';
        }

        return trim($result);
    }

    private static function convertTwoDigits(int $num): string
    {
        if ($num < 20) {
            return self::$ones[$num];
        }

        $ten = intdiv($num, 10);
        $one = $num % 10;

        return trim(self::$tens[$ten] . ' ' . self::$ones[$one]);
    }
}
