<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

namespace anginger\modules\user\assets;

use anginger\assets\AppAsset;
use yii\web\AssetBundle;

class UserAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = ['assets/module-user.js'];
    public $jsOptions = ['position' => \yii\web\View::POS_END]; // classic end-of-body: runs before deferred Alpine start
    public $depends = [AppAsset::class];
}
