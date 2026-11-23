<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

namespace anginger\models;

use yii\db\ActiveRecord;

/**
 * One persisted chat message — the conversation context sent back to the
 * model on every turn.
 *
 * @property int $id
 * @property string $conversation_uuid
 * @property string $role system|user|assistant
 * @property string $content
 * @property int $created_time
 */
class Message extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%messages}}';
    }
}
