<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnforceAttendanceSessionUniqueness extends Migration
{
    public function up()
    {
        $duplicates = $this->db->table('attendance_sessions')->select('source_key,COUNT(*) total')->where('source_key IS NOT NULL')->groupBy('source_key')->having('total >', 1)->get()->getResultArray();
        foreach ($duplicates as $duplicate) {
            $rows = $this->db->table('attendance_sessions')->select('id')->where('source_key', $duplicate['source_key'])->orderBy('id', 'DESC')->get()->getResultArray();
            array_shift($rows);
            foreach ($rows as $row) {
                $this->db->table('attendance_sessions')->where('id', (int) $row['id'])->update(['source_key'=>'DEDUP:' . (int) $row['id'] . ':' . $duplicate['source_key']]);
            }
        }
        $indexes = $this->db->query("SHOW INDEX FROM attendance_sessions WHERE Key_name='attendance_sessions_source_key_unique'")->getResultArray();
        if ($indexes === []) {
            $this->db->query('ALTER TABLE attendance_sessions ADD UNIQUE KEY attendance_sessions_source_key_unique (source_key)');
        }
    }

    public function down()
    {
        $indexes = $this->db->query("SHOW INDEX FROM attendance_sessions WHERE Key_name='attendance_sessions_source_key_unique'")->getResultArray();
        if ($indexes !== []) $this->db->query('ALTER TABLE attendance_sessions DROP INDEX attendance_sessions_source_key_unique');
    }
}
