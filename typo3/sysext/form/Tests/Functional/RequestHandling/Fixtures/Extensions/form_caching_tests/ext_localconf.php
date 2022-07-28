<?php

defined('TYPO3_MODE') or die();

call_user_func(static function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'FormCachingTests',
        'AllActionsCached',
        [\TYPO3\CMS\FormCachingTests\Controller\FormCachingTestsController::class => 'someRender,somePerform'],
        [\TYPO3\CMS\FormCachingTests\Controller\FormCachingTestsController::class => '']
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'FormCachingTests',
        'RenderActionIsCached',
        [\TYPO3\CMS\FormCachingTests\Controller\FormCachingTestsController::class => 'someRender,somePerform'],
        [\TYPO3\CMS\FormCachingTests\Controller\FormCachingTestsController::class => 'somePerform']
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'FormCachingTests',
        'AllActionsUncached',
        [\TYPO3\CMS\FormCachingTests\Controller\FormCachingTestsController::class => 'someRender,somePerform'],
        [\TYPO3\CMS\FormCachingTests\Controller\FormCachingTestsController::class => 'someRender,somePerform']
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('
        plugin.tx_form {
            settings {
                yamlConfigurations {
                    1628755200 = EXT:form_caching_tests/Configuration/Yaml/FormSetup.yaml
                }
            }
        }
    ');
});
