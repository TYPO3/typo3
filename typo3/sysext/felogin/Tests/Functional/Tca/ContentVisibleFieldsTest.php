<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\FrontendLogin\Tests\Functional\Tca;

use TYPO3\CMS\Backend\Tests\Functional\Form\FormTestService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ContentVisibleFieldsTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['felogin'];

    protected static array $contentFields = [
        'CType',
        'colPos',
        'sys_language_uid',
        'header',
        'header_layout',
        'date',
        'header_link',
        'starttime',
        'endtime',
        'fe_group',
        'editlock',
        'rowDescription',
        'categories',
        'pi_flexform',
    ];

    /**
     * @test
     */
    public function loginFormContainsExpectedFields(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');

        $formEngineTestService = GeneralUtility::makeInstance(FormTestService::class);
        $formResult = $formEngineTestService->createNewRecordForm('tt_content', ['CType' => 'felogin_login']);

        foreach (static::$contentFields as $expectedField) {
            self::assertNotFalse(
                $formEngineTestService->formHtmlContainsField($expectedField, $formResult['html']),
                'The field ' . $expectedField . ' is not in the form HTML'
            );
        }
    }
}
