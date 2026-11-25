<?php

use yii\db\Migration;

/**
 * Adds real FOREIGN KEY ... ON DELETE CASCADE constraints to conversations
 * and messages. SQLite can neither ADD CONSTRAINT nor (in Yii's sqlite
 * QueryBuilder) addForeignKey, so both tables are rebuilt with raw CREATE
 * TABLE statements that declare the FK inline.
 *
 * User::beforeDelete() already cascades in PHP; this is the DB-level
 * belt-and-braces so orphaned rows are impossible even for raw SQL.
 *
 * Table rebuilds must run with foreign_keys=OFF and outside a transaction
 * (PRAGMA foreign_keys is a no-op inside one), hence the custom up().
 */
class m260302_000001_conversations_messages_fk_cascade extends Migration
{
    public function up()
    {
        $this->db->createCommand('PRAGMA foreign_keys=OFF')->execute();
        try {
            $this->safeUp();
        } catch (\Throwable $e) {
            $this->db->createCommand('PRAGMA foreign_keys=ON')->execute();
            throw $e;
        }
        $this->db->createCommand('PRAGMA foreign_keys=ON')->execute();
        return true;
    }

    public function safeUp()
    {
        // conversations: user_uuid -> users.uuid ON DELETE CASCADE
        $this->execute('CREATE TABLE {{%conversations_new}} (
            uuid varchar(36) PRIMARY KEY,
            user_uuid varchar(36) NOT NULL,
            title varchar(200) NOT NULL,
            system_prompt text NULL,
            created_time bigint NOT NULL,
            updated_time bigint NOT NULL,
            FOREIGN KEY (user_uuid) REFERENCES {{%users}} (uuid) ON DELETE CASCADE
        )');
        $this->execute('INSERT INTO {{%conversations_new}} (uuid, user_uuid, title, system_prompt, created_time, updated_time) SELECT uuid, user_uuid, title, system_prompt, created_time, updated_time FROM {{%conversations}}');
        $this->dropTable('{{%conversations}}');
        $this->renameTable('{{%conversations_new}}', '{{%conversations}}');
        $this->createIndex('idx_conversations_user', '{{%conversations}}', 'user_uuid');

        // messages: conversation_uuid -> conversations.uuid ON DELETE CASCADE
        $this->execute('CREATE TABLE {{%messages_new}} (
            id integer PRIMARY KEY AUTOINCREMENT NOT NULL,
            conversation_uuid varchar(36) NOT NULL,
            role varchar(16) NOT NULL,
            content text NOT NULL,
            created_time bigint NOT NULL,
            FOREIGN KEY (conversation_uuid) REFERENCES {{%conversations}} (uuid) ON DELETE CASCADE
        )');
        $this->execute('INSERT INTO {{%messages_new}} (id, conversation_uuid, role, content, created_time) SELECT id, conversation_uuid, role, content, created_time FROM {{%messages}}');
        $this->dropTable('{{%messages}}');
        $this->renameTable('{{%messages_new}}', '{{%messages}}');
        $this->createIndex('idx_messages_conversation', '{{%messages}}', 'conversation_uuid');
    }

    public function safeDown()
    {
        // SQLite cannot drop a constraint; the FK simply disappears with the
        // next table rebuild. Nothing to do.
    }
}
