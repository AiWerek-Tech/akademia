<?php

namespace App\Services;

class BlockPatternService
{
    /**
     * Validate block pattern structure and constraints against effective hours, min days, and max daily hours
     */
    public static function validateBlockPattern($patternInput, float $effectiveHours, ?int $minimumDays = null, ?float $maxDailyHours = null): array
    {
        $errors = [];
        $data = null;

        if (is_string($patternInput)) {
            $decoded = json_decode($patternInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['valid' => false, 'errors' => ['Format JSON block pattern tidak valid: ' . json_last_error_msg()]];
            }
            $data = $decoded;
        } elseif (is_array($patternInput)) {
            $data = $patternInput;
        } else {
            return ['valid' => false, 'errors' => ['Format block pattern harus berupa string JSON atau array.']];
        }

        if (!isset($data['blocks']) || !is_array($data['blocks'])) {
            return ['valid' => false, 'errors' => ['Properti "blocks" wajib berupa array.']];
        }

        $blocks = $data['blocks'];
        if (empty($blocks)) {
            return ['valid' => false, 'errors' => ['Array "blocks" tidak boleh kosong.']];
        }

        $total = 0.0;
        foreach ($blocks as $idx => $blockVal) {
            if (!is_numeric($blockVal) || is_nan((float)$blockVal)) {
                $errors[] = 'Blok ke-' . ($idx + 1) . ' harus berupa angka valid.';
                continue;
            }
            $val = (float)$blockVal;
            if ($val <= 0) {
                $errors[] = 'Nilai setiap blok harus lebih besar dari 0 (Blok ke-' . ($idx + 1) . ' = ' . $val . ').';
            }
            if ($maxDailyHours !== null && $maxDailyHours > 0 && $val > $maxDailyHours) {
                $errors[] = 'Blok ke-' . ($idx + 1) . ' (' . $val . ' JP) melebihi batas maksimum per hari (' . $maxDailyHours . ' JP).';
            }
            $total += $val;
        }

        // Compare float with small tolerance
        if (abs($total - $effectiveHours) > 0.01) {
            $errors[] = 'Total jam dalam blok (' . $total . ' JP) harus sama dengan jam efektif minggu (' . $effectiveHours . ' JP).';
        }

        $numBlocks = count($blocks);
        if ($minimumDays !== null && $minimumDays > 0 && $numBlocks < $minimumDays) {
            $errors[] = 'Jumlah blok (' . $numBlocks . ') lebih kecil dari minimum hari mengajar (' . $minimumDays . ' hari).';
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
            'blocks' => $blocks,
            'total'  => $total,
        ];
    }

    /**
     * Convert array or raw JSON to canonical JSON format
     */
    public static function canonicalize($patternInput): string
    {
        if (is_string($patternInput)) {
            $data = json_decode($patternInput, true);
        } elseif (is_array($patternInput)) {
            $data = $patternInput;
        } else {
            $data = ['blocks' => [], 'preferred' => true];
        }

        $blocks = isset($data['blocks']) && is_array($data['blocks']) ? array_values($data['blocks']) : [];
        $preferred = isset($data['preferred']) ? (bool)$data['preferred'] : true;

        // Ensure numbers are numeric
        $cleanBlocks = [];
        foreach ($blocks as $b) {
            if (is_numeric($b)) {
                $num = (float)$b;
                $cleanBlocks[] = ($num == (int)$num) ? (int)$num : $num;
            }
        }

        return json_encode([
            'blocks'    => $cleanBlocks,
            'preferred' => $preferred,
        ]);
    }
}
