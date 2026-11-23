<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

namespace anginger\modules\chat\assets;

use anginger\assets\AppAsset;
use yii\web\AssetBundle;

class ChatAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = ['assets/module-chat.js'];
    public $depends = [AppAsset::class];
}
