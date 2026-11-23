<?php

use yii\db\Migration;

/**
 * Adds conversation management to the chat module and a role column to
 * users for personnel management:
 *  - conversations: one chat session per user, with its own system prompt
 *  - messages:      persisted chat history (context) of each conversation
 *  - users.role:    'admin' can manage personnel, 'user' cannot
 */
class m260223_000003_conversations_messages_and_user_role extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%users}}', 'role', 'varchar(16) NOT NULL DEFAULT \'user\'');
        $this->update('{{%users}}', ['role' => 'admin'], ['username' => 'admin']);

        $this->createTable('{{%conversations}}', [
            'uuid' => 'varchar(36) PRIMARY KEY',
            'user_uuid' => 'varchar(36) NOT NULL',
            'title' => 'varchar(200) NOT NULL',
            'system_prompt' => 'text NULL',
            'created_time' => 'bigint NOT NULL',
            'updated_time' => 'bigint NOT NULL',
        ]);
        $this->createIndex('idx_conversations_user', '{{%conversations}}', 'user_uuid');

        $this->createTable('{{%messages}}', [
            'id' => $this->primaryKey(),
            'conversation_uuid' => 'varchar(36) NOT NULL',
            'role' => 'varchar(16) NOT NULL',
            'content' => 'text NOT NULL',
            'created_time' => 'bigint NOT NULL',
        ]);
        $this->createIndex('idx_messages_conversation', '{{%messages}}', 'conversation_uuid');
    }

    public function safeDown()
    {
        $this->dropTable('{{%messages}}');
        $this->dropTable('{{%conversations}}');
        $this->dropColumn('{{%users}}', 'role');
    }
}
