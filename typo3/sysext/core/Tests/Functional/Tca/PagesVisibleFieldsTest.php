<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Tests\Functional\Tca;

use TYPO3\CMS\Backend\Tests\Functional\Form\FormTestService;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PagesVisibleFieldsTest extends FunctionalTestCase
{
    /**
     * These form fields are visible in the default page types.
     *
     * @var array
     */
    protected static $defaultPageFormFields = [
        'title',
        'nav_title',
        'subtitle',
        'hidden',
        'nav_hide',
        'starttime',
        'endtime',
        'extendToSubpages',
        'fe_group',
        'fe_login_mode',
        'abstract',
        'keywords',
        'description',
        'author',
        'author_email',
        'layout',
        'newUntil',
        'backend_layout',
        'backend_layout_next_level',
        'content_from_pid',
        'target',
        'cache_timeout',
        'cache_tags',
        'l18n_cfg',
        'is_siteroot',
        'no_search',
        'editlock',
        'php_tree_stop',
        'module',
        'media',
        'tsconfig_includes',
        'TSconfig',
        'categories',
    ];

    /**
     * Configuration of hidden / additional form fields per page type.
     *
     * @var array
     */
    protected static $pageFormFields = [
        PageRepository::DOKTYPE_BE_USER_SECTION => [],
        PageRepository::DOKTYPE_DEFAULT => [],
        PageRepository::DOKTYPE_SHORTCUT => [
            'additionalFields' => [
                'shortcut_mode',
                'shortcut',
            ],
            'hiddenFields' => [
                'keywords',
                'description',
                'content_from_pid',
                'cache_timeout',
                'cache_tags',
                'module',
            ],
        ],
        PageRepository::DOKTYPE_MOUNTPOINT => [
            'additionalFields' => [
                'mount_pid_ol',
                'mount_pid',
            ],
            'hiddenFields' => [
                'keywords',
                'description',
                'content_from_pid',
                'cache_timeout',
                'cache_tags',
                'module',
            ],
        ],
        PageRepository::DOKTYPE_LINK => [
            'additionalFields' => [
                'url',
            ],
            'hiddenFields' => [
                'keywords',
                'description',
                'content_from_pid',
                'cache_timeout',
                'cache_tags',
                'module',
            ],
        ],
        PageRepository::DOKTYPE_SYSFOLDER => [
            'hiddenFields' => [
                'nav_title',
                'subtitle',
                'nav_hide',
                'starttime',
                'endtime',
                'extendToSubpages',
                'fe_group',
                'fe_login_mode',
                'abstract',
                'keywords',
                'description',
                'author',
                'author_email',
                'layout',
                'newUntil',
                'content_from_pid',
                'cache_timeout',
                'cache_tags',
                'content_from_pid',
                'target',
                'cache_timeout',
                'cache_tags',
                'l18n_cfg',
                'is_siteroot',
                'no_search',
                'php_tree_stop',
            ],
        ],
        PageRepository::DOKTYPE_RECYCLER => [
            'hiddenFields' => [
                'nav_title',
                'subtitle',
                'nav_hide',
                'starttime',
                'endtime',
                'extendToSubpages',
                'fe_group',
                'fe_login_mode',
                'abstract',
                'keywords',
                'description',
                'author',
                'author_email',
                'layout',
                'newUntil',
                'backend_layout',
                'backend_layout_next_level',
                'content_from_pid',
                'cache_timeout',
                'cache_tags',
                'content_from_pid',
                'target',
                'cache_timeout',
                'cache_tags',
                'l18n_cfg',
                'is_siteroot',
                'no_search',
                'php_tree_stop',
                'module',
                'media',
                'tsconfig_includes',
                'TSconfig',
            ],
        ],
        PageRepository::DOKTYPE_SPACER => [
            'hiddenFields' => [
                'nav_title',
                'subtitle',
                'abstract',
                'keywords',
                'description',
                'author',
                'author_email',
                'layout',
                'newUntil',
                'module',
                'content_from_pid',
                'cache_timeout',
                'cache_tags',
                'content_from_pid',
                'target',
                'cache_timeout',
                'cache_tags',
                'l18n_cfg',
                'is_siteroot',
                'no_search',
                'php_tree_stop',
                'media',
            ],
        ],
    ];

    /**
     * @return array
     */
    public function pagesFormContainsExpectedFieldsDataProvider(): array
    {
        $pageTypes = [];

        foreach (static::$pageFormFields as $doktype => $fieldConfig) {
            $expectedFields = static::$defaultPageFormFields;
            $hiddenFields = [];
            if (array_key_exists('additionalFields', $fieldConfig)) {
                $expectedFields = array_merge($expectedFields, $fieldConfig['additionalFields']);
            }
            if (array_key_exists('hiddenFields', $fieldConfig)) {
                $hiddenFields = $fieldConfig['hiddenFields'];
                $expectedFields = array_diff($expectedFields, $hiddenFields);
            }
            $pageTypes['page doktype ' . $doktype] = [$doktype, $expectedFields, $hiddenFields];
        }

        return $pageTypes;
    }

    /**
     * @test
     * @dataProvider pagesFormContainsExpectedFieldsDataProvider
     * @param int $doktype
     * @param array $expectedFields
     * @param array $hiddenFields
     */
    public function pagesFormContainsExpectedFields(int $doktype, array $expectedFields, array $hiddenFields)
    {
        $this->setUpBackendUserFromFixture(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $formEngineTestService = GeneralUtility::makeInstance(FormTestService::class);
        $formResult = $formEngineTestService->createNewRecordForm('pages', ['doktype' => $doktype]);

        foreach ($expectedFields as $expectedField) {
            self::assertNotFalse(
                $formEngineTestService->formHtmlContainsField($expectedField, $formResult['html']),
                'The field ' . $expectedField . ' is not in the HTML'
            );
        }

        foreach ($hiddenFields as $hiddenField) {
            self::assertFalse(
                $formEngineTestService->formHtmlContainsField($hiddenField, $formResult['html']),
                'The field ' . $hiddenField . ' is in the HTML'
            );
        }
    }
}
