<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameApplicationToIalosEducation extends Migration
{
    public function up()
    {
        $this->db->table('application_settings')
            ->where('setting_group', 'general')
            ->where('setting_key', 'app_name')
            ->whereIn('setting_value', ['WMVAA Akademia', 'WMVAA Academia'])
            ->update(['setting_value'=>'IALOS Education','updated_at'=>date('Y-m-d H:i:s')]);
    }

    public function down()
    {
        $this->db->table('application_settings')
            ->where('setting_group', 'general')
            ->where('setting_key', 'app_name')
            ->where('setting_value', 'IALOS Education')
            ->update(['setting_value'=>'WMVAA Akademia','updated_at'=>date('Y-m-d H:i:s')]);
    }
}
