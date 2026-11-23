<?php
/**
 * Copyright 2026 Anginger AI
 *
 * Licensed under the BSD 3-Clause License (see LICENSE.md)
 */

namespace anginger\assets;

use yii\web\AssetBundle;

/**
 * Global assets: Alpine.js (vendored, no CDN dependency) + shared CSS.
 * Every module asset bundle depends on this one.
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = ['assets/app.css'];
    public $js = ['assets/alpine.min.js'];
}
