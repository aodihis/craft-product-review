<?php 

namespace aodihis\productreview\web\assets\reviewtable;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\vue\VueAsset;

class ReviewTableAsset extends AssetBundle
{
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = __DIR__ . '/src/';

        // define the dependencies
        $this->depends = [
            CpAsset::class,
            VueAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->js = [
            'js/app.js',
        ];

        $this->css = [
            'css/styles.css',
        ];

        $this->jsOptions = [
            'type' => 'module',
        ];

        parent::init();
    }
}