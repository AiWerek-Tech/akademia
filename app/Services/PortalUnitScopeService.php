<?php

namespace App\Services;

use Config\Database;

/**
 * Read-only unit filter for dashboards and personal portals.
 *
 * This scope deliberately does not mutate active_unit_id. Administrative
 * writes therefore keep their existing single-unit authorization boundary,
 * while read-only role products can aggregate every accessible unit.
 */
class PortalUnitScopeService
{
    private const SESSION_KEY = 'portal_unit_scope';

    public static function resolve(?string $requested = null, ?int $teacherId = null): array
    {
        $units = UnitScopeService::accessibleUnits();

        if ($teacherId && $units !== []) {
            $assignedRows = Database::connect()->table('teacher_unit_assignments')
                ->select('unit_id')
                ->where('teacher_id', $teacherId)
                ->where('status', 'ACTIVE')
                ->groupStart()
                    ->where('academic_period_id IS NULL')
                    ->orWhere('academic_period_id', (int) (get_active_period()['id'] ?? 0))
                ->groupEnd()
                ->get()->getResultArray();
            $assignedIds = array_map('intval', array_column($assignedRows, 'unit_id'));
            if ($assignedIds !== []) {
                $units = array_values(array_filter(
                    $units,
                    static fn (array $unit): bool => in_array((int) $unit['id'], $assignedIds, true)
                ));
            }
        }

        $allowedIds = array_map('intval', array_column($units, 'id'));
        $candidate = strtolower(trim((string) ($requested ?? '')));
        if ($candidate === '') {
            $candidate = strtolower(trim((string) session()->get(self::SESSION_KEY)));
        }

        $selected = 'all';
        if ($candidate !== '' && $candidate !== 'all') {
            foreach ($units as $unit) {
                if ($candidate === (string) $unit['id'] || $candidate === strtolower((string) $unit['code'])) {
                    $selected = (string) (int) $unit['id'];
                    break;
                }
            }
        }
        if (count($allowedIds) === 1) {
            $selected = (string) $allowedIds[0];
        }

        session()->set(self::SESSION_KEY, $selected);
        $selectedIds = $selected === 'all' ? $allowedIds : [(int) $selected];
        $selectedUnits = array_values(array_filter(
            $units,
            static fn (array $unit): bool => in_array((int) $unit['id'], $selectedIds, true)
        ));

        return [
            'units' => $units,
            'selected' => $selected,
            'unitIds' => $selectedIds,
            'selectedUnits' => $selectedUnits,
            'isAll' => $selected === 'all',
            'label' => $selected === 'all'
                ? 'Semua Unit'
                : (string) ($selectedUnits[0]['name'] ?? 'Unit'),
        ];
    }
}
