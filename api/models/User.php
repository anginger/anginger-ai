<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

namespace anginger\models;

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * Login account. Passwords are stored as sha256 hashes (the seeded
 * admin/admin account keeps working).
 *
 * @property string $uuid
 * @property string $username
 * @property string|null $password
 * @property string $display_name
 * @property string|null $email
 * @property string $role admin|user
 * @property string $auth_source local|ldap
 * @property string|null $ldap_dn
 * @property int $archived
 * @property int $created_time
 * @property int|null $last_login_time
 */
class User extends ActiveRecord implements IdentityInterface
{
    public static function tableName(): string
    {
        return '{{%users}}';
    }

    public static function findIdentity($id): ?IdentityInterface
    {
        $user = static::findOne($id);
        // archived accounts lose their session immediately
        if ($user !== null && $user->archived) {
            return null;
        }
        return $user;
    }

    public static function findIdentityByAccessToken($token, $type = null): ?IdentityInterface
    {
        // Session-based authentication only, no bearer tokens.
        return null;
    }

    public function getId(): string
    {
        return $this->uuid;
    }

    public function getAuthKey(): string
    {
        return hash('sha256', $this->uuid . $this->password);
    }

    public function validateAuthKey($authKey): bool
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Finds a user by username and plain-text password.
     */
    public static function findByUsernameAndPassword(string $username, string $password): ?User
    {
        return static::findOne([
            'username' => $username,
            'password' => \anginger\components\AuthService::hashPassword($password),
        ]);
    }

    /**
     * The password hash must never leak into API responses.
     */
        public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        foreach (Conversation::findAll(['user_uuid' => $this->uuid]) as $conv) {
            foreach (Message::findAll(['conversation_uuid' => $conv->uuid]) as $msg) {
                $msg->delete();
            }
            $conv->delete();
        }
        return true;
    }

public function fields(): array
    {
        $fields = parent::fields();
        unset($fields['password']);
        unset($fields['ldap_dn']);
        return $fields;
    }

    public function beforeSave($insert): bool
    {
        if ($insert && empty($this->created_time)) {
            $this->created_time = time();
        }
        return parent::beforeSave($insert);
    }
}
