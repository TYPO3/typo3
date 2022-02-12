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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller;

use TYPO3\CMS\Backend\Controller\FormInlineAjaxController;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for TYPO3\CMS\Backend\Controller\FormInlineAjaxController
 */
class FormInlineAjaxControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected FormInlineAjaxController $subject;

    protected $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_csv',
    ];

    /**
     * @var array
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DK' => ['id' => 1, 'title' => 'Dansk', 'locale' => 'dk_DA.UTF8'],
    ];

    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tx_testirrecsv_hotel.csv');

        $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DK', '/dk/'),
            ]
        );

        $this->subject = new FormInlineAjaxController();
    }

    /**
     * @test
     */
    public function createActionWithNewParentReturnsResponseForInlineChildData(): void
    {
        $parsedBody = [
            'ajax' => [
                0 => 'data-1-tx_testirrecsv_hotel-NEW59c1062549e56282348897-offers-tx_testirrecsv_offer',
                'context' => json_encode($this->getContextForSysLanguageUid(0)),
            ],
        ];

        $request = new ServerRequest();
        $request = $request->withParsedBody($parsedBody);
        $response = new Response();

        $response = $this->subject->createAction($request, $response);
        $body = (string)$response->getBody();
        $jsonArray = json_decode($body, true);

        self::assertNotEmpty($jsonArray['data']);
    }

    /**
     * @test
     */
    public function createActionWithExistingParentReturnsResponseForInlineChildData(): void
    {
        $parsedBody = [
            'ajax' => [
                0 => 'data-1-tx_testirrecsv_hotel-NEW59c1062549e56282348897-offers-tx_testirrecsv_offer',
                'context' => json_encode($this->getContextForSysLanguageUid(0)),
            ],
        ];

        $request = new ServerRequest();
        $request = $request->withParsedBody($parsedBody);

        $response = new Response();

        $response = $this->subject->createAction($request, $response);
        $body = (string)$response->getBody();
        $jsonArray = json_decode($body, true);

        self::assertNotEmpty($jsonArray['data']);
    }

    /**
     * @test
     */
    public function createActionWithExistingLocalizedParentReturnsResponseWithLocalizedChildData(): void
    {
        $parsedBody = [
            'ajax' => [
                0 => 'data-1-tx_testirrecsv_hotel-NEW59c1062549e56282348897-offers-tx_testirrecsv_offer',
                'context' => json_encode($this->getContextForSysLanguageUid(1)),
            ],
        ];

        $request = new ServerRequest();
        $request = $request->withParsedBody($parsedBody);
        $response = new Response();

        $response = $this->subject->createAction($request, $response);
        $body = (string)$response->getBody();
        $jsonArray = json_decode($body, true);

        self::assertMatchesRegularExpression('/<option value="1"[^>]* selected="selected">Dansk<\/option>/', $jsonArray['data']);
    }

    /**
     * @test
     */
    public function createActionWithExistingLocalizedParentAndNotLocalizableChildReturnsResponseWithChildData(): void
    {
        unset($GLOBALS['TCA']['tx_testirrecsv_offer']['ctrl']['languageField']);
        unset($GLOBALS['TCA']['tx_testirrecsv_offer']['ctrl']['transOrigPointerField']);
        unset($GLOBALS['TCA']['tx_testirrecsv_offer']['ctrl']['transOrigDiffSourceField']);

        $parsedBody = [
            'ajax' => [
                0 => 'data-1-tx_testirrecsv_hotel-NEW59c1062549e56282348897-offers-tx_testirrecsv_offer',
                'context' => json_encode($this->getContextForSysLanguageUid(1)),
            ],
        ];

        $request = new ServerRequest();
        $request = $request->withParsedBody($parsedBody);
        $response = new Response();

        $response = $this->subject->createAction($request, $response);
        $body = (string)$response->getBody();
        $jsonArray = json_decode($body, true);

        self::assertDoesNotMatchRegularExpression('/<select[^>]* name="data\[tx_testirrecsv_offer\]\[NEW[1-9]+\]\[sys_language_uid\]"[^>]*>/', $jsonArray['data']);
    }

    /**
     * @param int $sysLanguageUid
     * @return array
     */
    protected function getContextForSysLanguageUid(int $sysLanguageUid): array
    {
        $config = [
            'type' => 'inline',
            'foreign_table' => 'tx_testirrecsv_offer',
            'maxitems' => 10,
            'appearance' => [
                'showSynchronizationLink' => 1,
                'showAllLocalizationLink' => 1,
                'showPossibleLocalizationRecords' => true,
                'levelLinksPosition' => 'top',
                'enabledControls' => [
                    'info' => true,
                    'new' => true,
                    'dragdrop' => true,
                    'sort' => true,
                    'hide' => true,
                    'delete' => true,
                    'localize' => true,
                ],
            ],
            'behaviour' => [
                'localizationMode' => 'none',
                'localizeChildrenAtParentLocalization' => true,
            ],
            'default' => '',
            'inline' => [
                'parentSysLanguageUid' => $sysLanguageUid,
                'first' => false,
                'last' => false,
            ],
        ];

        $configJson = json_encode($config);
        return [
            'config' => $configJson,
            'hmac' => GeneralUtility::hmac($configJson, 'InlineContext'),
        ];
    }
}
