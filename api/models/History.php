<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

namespace anginger\models;

use yii\db\ActiveRecord;

/**
 * Audit log: one row per handled request (see BaseController::logHistory).
 *
 * @property int $id
 * @property int $created_time
 * @property string $user user uuid, or the client IP for guests
 * @property string $method
 * @property string $resource
 */
class History extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%histories}}';
    }

    public function rules(): array
    {
        return [
            [['created_time', 'user', 'method', 'resource'], 'required'],
            [['created_time'], 'integer'],
            [['user'], 'string', 'max' => 64],
            [['method'], 'string', 'max' => 10],
            [['resource'], 'string'],
        ];
    }
}
