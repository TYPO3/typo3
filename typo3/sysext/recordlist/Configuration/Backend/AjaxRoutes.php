<?php

/**
 * Definitions for routes provided by EXT:recordlist
 */
return [

    'link_browser_encodeTypoLink' => [
        'path' => '/linkBrowser/encodeTypoLink',
        'target' => \TYPO3\CMS\Recordlist\Controller\LinkBrowserController::class . '::encodeTypoLink',
        'access' => 'public'
    ],

];
