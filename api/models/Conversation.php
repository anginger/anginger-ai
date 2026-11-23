<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

namespace anginger\models;

use yii\db\ActiveRecord;

/**
 * One chat session owned by a user, with its own system prompt.
 *
 * @property string $uuid
 * @property string $user_uuid
 * @property string $title
 * @property string|null $system_prompt
 * @property int $created_time
 * @property int $updated_time
 */
class Conversation extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%conversations}}';
    }

    public function getMessages(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Message::class, ['conversation_uuid' => 'uuid'])
            ->orderBy(['id' => SORT_ASC]);
    }
}
