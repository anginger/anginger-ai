<?php

use yii\db\Migration;

/**
 * Creates the `{{%users}}` table and seeds the default admin/admin account.
 */
class m260223_000001_create_users_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%users}}', [
            'uuid' => 'varchar(36) PRIMARY KEY',
            'username' => 'varchar(128) NOT NULL UNIQUE',
            'password' => 'varchar(64) NOT NULL',
            'display_name' => 'varchar(50) NOT NULL',
            'created_time' => 'bigint NOT NULL',
        ]);

        $this->insert('{{%users}}', [
            'uuid' => '00000000-0000-0000-0000-000000000000',
            'username' => 'admin',
            'password' => hash('sha256', 'admin'),
            'display_name' => 'Admin',
            'created_time' => 1637505598,
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%users}}');
    }
}
