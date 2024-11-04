<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3Tests\FormCachingTests\Controller\FormCachingTestsController;

defined('TYPO3') or die();

call_user_func(static function () {
    ExtensionUtility::configurePlugin(
        'FormCachingTests',
        'AllActionsCached',
        [FormCachingTestsController::class => ['someRender', 'somePerform']],
        [FormCachingTestsController::class => ['']],
    );

    ExtensionUtility::configurePlugin(
        'FormCachingTests',
        'RenderActionIsCached',
        [FormCachingTestsController::class => ['someRender', 'somePerform']],
        [FormCachingTestsController::class => ['somePerform']],
    );

    ExtensionUtility::configurePlugin(
        'FormCachingTests',
        'AllActionsUncached',
        [FormCachingTestsController::class => ['someRender', 'somePerform']],
        [FormCachingTestsController::class => ['someRender', 'somePerform']],
    );

    ExtensionManagementUtility::addTypoScriptSetup('
        plugin.tx_form {
            settings {
                yamlConfigurations {
                    1628755200 = EXT:form_caching_tests/Configuration/Yaml/FormSetup.yaml
                }
            }
        }
    ');
});
