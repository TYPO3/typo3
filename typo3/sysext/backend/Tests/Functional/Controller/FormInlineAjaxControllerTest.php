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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller;

use TYPO3\CMS\Backend\Controller\FormInlineAjaxController;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for TYPO3\CMS\Backend\Controller\FormInlineAjaxController
 */
class FormInlineAjaxControllerTest extends FunctionalTestCase
{
    /**
     * @var FormInlineAjaxController
     */
    protected $subject;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/irre_tutorial',
    ];

    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_language.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/Fixtures/tx_irretutorial_1ncsv_hotel.xml');

        $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $this->subject = new FormInlineAjaxController();
    }

    /**
     * @test
     */
    public function createActionWithNewParentReturnsResponseForInlineChildData()
    {
        $parsedBody = [
            'ajax' => [
                0 => 'data-1-tx_irretutorial_1ncsv_hotel-NEW59c1062549e56282348897-offers-tx_irretutorial_1ncsv_offer',
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
    public function createActionWithExistingParentReturnsResponseForInlineChildData()
    {
        $parsedBody = [
            'ajax' => [
                0 => 'data-1-tx_irretutorial_1ncsv_hotel-NEW59c1062549e56282348897-offers-tx_irretutorial_1ncsv_offer',
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
    public function createActionWithExistingLocalizedParentReturnsResponseWithLocalizedChildData()
    {
        $parsedBody = [
            'ajax' => [
                0 => 'data-1-tx_irretutorial_1ncsv_hotel-NEW59c1062549e56282348897-offers-tx_irretutorial_1ncsv_offer',
                'context' => json_encode($this->getContextForSysLanguageUid(1)),
            ],
        ];

        $request = new ServerRequest();
        $request = $request->withParsedBody($parsedBody);
        $response = new Response();

        $response = $this->subject->createAction($request, $response);
        $body = (string)$response->getBody();
        $jsonArray = json_decode($body, true);

        self::assertRegExp('/<option value="1"[^>]* selected="selected">Dansk<\/option>/', $jsonArray['data']);
    }

    /**
     * @test
     */
    public function createActionWithExistingLocalizedParentAndNotLocalizableChildReturnsResponseWithChildData()
    {
        unset($GLOBALS['TCA']['tx_irretutorial_1ncsv_offer']['ctrl']['languageField']);
        unset($GLOBALS['TCA']['tx_irretutorial_1ncsv_offer']['ctrl']['transOrigPointerField']);
        unset($GLOBALS['TCA']['tx_irretutorial_1ncsv_offer']['ctrl']['transOrigDiffSourceField']);

        $parsedBody = [
            'ajax' => [
                0 => 'data-1-tx_irretutorial_1ncsv_hotel-NEW59c1062549e56282348897-offers-tx_irretutorial_1ncsv_offer',
                'context' => json_encode($this->getContextForSysLanguageUid(1)),
            ],
        ];

        $request = new ServerRequest();
        $request = $request->withParsedBody($parsedBody);
        $response = new Response();

        $response = $this->subject->createAction($request, $response);
        $body = (string)$response->getBody();
        $jsonArray = json_decode($body, true);

        self::assertNotRegExp('/<select[^>]* name="data\[tx_irretutorial_1ncsv_offer\]\[NEW[1-9]+\]\[sys_language_uid\]"[^>]*>/', $jsonArray['data']);
    }

    /**
     * @param int $sysLanguageUid
     * @return array
     */
    protected function getContextForSysLanguageUid(int $sysLanguageUid): array
    {
        $config = [
            'type' => 'inline',
            'foreign_table' => 'tx_irretutorial_1ncsv_offer',
            'maxitems' => 10,
            'appearance' => [
                'showSynchronizationLink' => 1,
                'showAllLocalizationLink' => 1,
                'showPossibleLocalizationRecords' => true,
                'showRemovedLocalizationRecords' => true,
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
            'minitems' => 0,
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
