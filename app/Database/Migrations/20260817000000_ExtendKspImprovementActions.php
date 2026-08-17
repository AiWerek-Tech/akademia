<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExtendKspImprovementActions extends Migration
{
    public function up()
    {
        $this->forge->addColumn('improvement_actions', [
            'owner_role_code' => ['type'=>'VARCHAR','constraint'=>80,'null'=>true,'after'=>'owner_user_id'],
            'success_indicator' => ['type'=>'TEXT','null'=>true,'after'=>'due_date'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('improvement_actions', ['owner_role_code','success_indicator']);
    }
}
