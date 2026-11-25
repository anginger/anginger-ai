<?php

use yii\db\Migration;

/**
 * Adds messages.client_id so a retried POST /api/chat does not write the
 * same user message twice (the client sends a per-message uuid; the server
 * skips the insert when it already exists for the conversation).
 */
class m260303_000001_messages_client_id extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%messages}}', 'client_id', 'varchar(36) NULL');
        $this->createIndex('idx_messages_client_id', '{{%messages}}', ['conversation_uuid', 'client_id'], true);
    }

    public function safeDown()
    {
        $this->dropIndex('idx_messages_client_id', '{{%messages}}');
        $this->dropColumn('{{%messages}}', 'client_id');
    }
}
