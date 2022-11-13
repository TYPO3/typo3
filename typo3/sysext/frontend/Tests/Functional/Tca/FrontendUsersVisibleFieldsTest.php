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

namespace TYPO3\CMS\Frontend\Tests\Functional\Tca;

use TYPO3\CMS\Backend\Tests\Functional\Form\FormTestService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FrontendUsersVisibleFieldsTest extends FunctionalTestCase
{
    protected static array $frontendUsersFields = [
        'disable',
        'username',
        'password',
        'usergroup',
        'company',
        'title',
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'address',
        'zip',
        'city',
        'country',
        'telephone',
        'fax',
        'email',
        'www',
        'image',
        'starttime',
        'endtime',
        'tx_extbase_type',
    ];

    /**
     * @test
     */
    public function frontendUsersFormContainsExpectedFields(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
        $formEngineTestService = GeneralUtility::makeInstance(FormTestService::class);
        $formResult = $formEngineTestService->createNewRecordForm('fe_users');
        foreach (static::$frontendUsersFields as $expectedField) {
            self::assertNotFalse(
                $formEngineTestService->formHtmlContainsField($expectedField, $formResult['html']),
                'The field ' . $expectedField . ' is not in the form HTML'
            );
        }
        self::assertNotFalse(
            strpos($formResult['html'], 'Last login'),
            'The field Last login is not in the HTML'
        );
    }
}
