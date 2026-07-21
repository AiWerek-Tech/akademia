<?php

namespace App\Services;

class CurriculumEffectiveHoursService
{
    public const SOURCES = ['OFFICIAL', 'CUSTOM', 'MANUAL'];

    /**
     * Compute effective weekly hours and validate source requirements
     */
    public static function calculateEffectiveHours(array $data): array
    {
        $source = strtoupper(trim($data['effective_source'] ?? 'OFFICIAL'));
        if (!in_array($source, self::SOURCES, true)) {
            throw new \InvalidArgumentException('Sumber jam efektif (effective_source) tidak valid: ' . $source);
        }

        $effective = 0.0;
        $adjustmentReason = trim($data['adjustment_reason'] ?? '');

        switch ($source) {
            case 'OFFICIAL':
                if (!isset($data['official_weekly_hours']) || $data['official_weekly_hours'] === '' || $data['official_weekly_hours'] === null) {
                    throw new \InvalidArgumentException('Jam resmi mingguan (official_weekly_hours) wajib diisi untuk sumber OFFICIAL.');
                }
                $val = (float)$data['official_weekly_hours'];
                if ($val < 0) {
                    throw new \InvalidArgumentException('Jam resmi mingguan tidak boleh negatif.');
                }
                $effective = $val;
                break;

            case 'CUSTOM':
                if (!isset($data['custom_weekly_hours']) || $data['custom_weekly_hours'] === '' || $data['custom_weekly_hours'] === null) {
                    throw new \InvalidArgumentException('Jam custom mingguan (custom_weekly_hours) wajib diisi untuk sumber CUSTOM.');
                }
                $val = (float)$data['custom_weekly_hours'];
                if ($val < 0) {
                    throw new \InvalidArgumentException('Jam custom mingguan tidak boleh negatif.');
                }
                if (empty($adjustmentReason)) {
                    throw new \InvalidArgumentException('Alasan penyesuaian (adjustment_reason) wajib diisi jika menggunakan sumber CUSTOM.');
                }
                $effective = $val;
                break;

            case 'MANUAL':
                if (!isset($data['manual_weekly_hours']) || $data['manual_weekly_hours'] === '' || $data['manual_weekly_hours'] === null) {
                    throw new \InvalidArgumentException('Jam manual mingguan (manual_weekly_hours) wajib diisi untuk sumber MANUAL.');
                }
                $val = (float)$data['manual_weekly_hours'];
                if ($val < 0) {
                    throw new \InvalidArgumentException('Jam manual mingguan tidak boleh negatif.');
                }
                if (empty($adjustmentReason)) {
                    throw new \InvalidArgumentException('Alasan penyesuaian (adjustment_reason) wajib diisi jika menggunakan sumber MANUAL.');
                }
                $effective = $val;
                break;
        }

        return [
            'effective_weekly_hours' => $effective,
            'effective_source'       => $source,
            'adjustment_reason'      => $adjustmentReason ?: null,
        ];
    }
}
