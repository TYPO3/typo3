<?php

declare(strict_types=1);

use TYPO3\CMS\Backend\LoginProvider\UsernamePasswordLoginProvider;

defined('TYPO3') or die();

// Second login provider with a lower priority than the default one, so the
// login screen offers a provider switch while the default stays primary.
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders']['playwright_secondary'] = [
    'provider' => UsernamePasswordLoginProvider::class,
    'sorting' => 10,
    'iconIdentifier' => 'actions-key',
    'label' => 'Secondary login',
];
