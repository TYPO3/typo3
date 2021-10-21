<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

(static function () {
    ExtensionUtility::registerPlugin(
        'blog_example',
        'Blogs',
        'Blog listing'
    );
})();
