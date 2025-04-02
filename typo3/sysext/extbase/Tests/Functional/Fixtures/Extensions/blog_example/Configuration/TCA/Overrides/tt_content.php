<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

(static function () {
    ExtensionUtility::registerPlugin(
        'blog_example',
        'Blogs',
        'Blog listing (JSON)'
    );
    ExtensionUtility::registerPlugin(
        'blog_example',
        'BlogPostEditing',
        'Blog listing and editing (Fluid forms)'
    );
})();
