<?php

namespace App\Services;

class PasswordGeneratorService
{
    /**
     * Generates a cryptographically secure random password without dictionary words.
     * Produces random mixed characters such as: *#fj@fsf3d3mMN3nds.
     * Contains random mixture of uppercase, lowercase, numbers, and special symbols.
     */
    public static function generate(int $length = 16): string
    {
        $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lowercase = 'abcdefghjkmnopqrstuvwxyz';
        $numbers = '23456789';
        $symbols = '*#@!$%&?~+=^';

        // Guarantee at least one character from each set
        $chars = [
            $uppercase[random_int(0, strlen($uppercase) - 1)],
            $lowercase[random_int(0, strlen($lowercase) - 1)],
            $numbers[random_int(0, strlen($numbers) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        $combined = $uppercase.$lowercase.$numbers.$symbols;
        $combinedLen = strlen($combined);

        $targetLength = max(8, $length);
        for ($i = count($chars); $i < $targetLength; $i++) {
            $chars[] = $combined[random_int(0, $combinedLen - 1)];
        }

        // Cryptographically secure Fisher-Yates shuffle
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            $temp = $chars[$i];
            $chars[$i] = $chars[$j];
            $chars[$j] = $temp;
        }

        return implode('', $chars);
    }
}
