<?php
namespace TYPO3\CMS\Backend\Tests\Functional\Controller;

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

use TYPO3\CMS\Backend\Controller\FormInlineAjaxController;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case for TYPO3\CMS\Backend\Controller\Page\LocalizationController
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

    protected function setUp()
    {
        parent::setUp();

        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/pages.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/pages_language_overlay.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/sys_language.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/Fixtures/tx_irretutorial_1ncsv_hotel.xml');

        $this->setUpBackendUserFromFixture(1);
        Bootstrap::getInstance()->initializeLanguageObject();

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
                'context' => json_encode($this->getContext()),
            ],
        ];

        $request = new ServerRequest();
        $request = $request->withParsedBody($parsedBody);
        $response = new Response();

        $response = $this->subject->createAction($request, $response);
        $body = (string)$response->getBody();
        $jsonArray = json_decode($body, true);

        $this->assertNotEmpty($jsonArray['data']);
    }

    /**
     * @test
     */
    public function createActionWithExistingParentReturnsResponseForInlineChildData()
    {
        $parsedBody = [
            'ajax' => [
                0 => 'data-1-tx_irretutorial_1ncsv_hotel-1-offers-tx_irretutorial_1ncsv_offer',
                'context' => json_encode($this->getContext()),
            ],
        ];

        $request = new ServerRequest();
        $request = $request->withParsedBody($parsedBody);

        $response = new Response();

        $response = $this->subject->createAction($request, $response);
        $body = (string)$response->getBody();
        $jsonArray = json_decode($body, true);

        $this->assertNotEmpty($jsonArray['data']);
    }

    /**
     * @test
     */
    public function createActionWithExistingLocalizedParentReturnsResponseWithLocalizedChildData()
    {
        $parsedBody = [
            'ajax' => [
                0 => 'data-1-tx_irretutorial_1ncsv_hotel-2-offers-tx_irretutorial_1ncsv_offer',
                'context' => json_encode($this->getContext()),
            ],
        ];

        $request = new ServerRequest();
        $request = $request->withParsedBody($parsedBody);
        $response = new Response();

        $response = $this->subject->createAction($request, $response);
        $body = (string)$response->getBody();
        $jsonArray = json_decode($body, true);

        $this->assertRegExp('/<option value="1"[^>]* selected="selected">Dansk<\/option>/', $jsonArray['data']);
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
                0 => 'data-1-tx_irretutorial_1ncsv_hotel-2-offers-tx_irretutorial_1ncsv_offer',
                'context' => json_encode($this->getContext())
            ],
        ];

        $request = new ServerRequest();
        $request = $request->withParsedBody($parsedBody);
        $response = new Response();

        $response = $this->subject->createAction($request, $response);
        $body = (string)$response->getBody();
        $jsonArray = json_decode($body, true);

        $this->assertNotRegExp('/<select[^>]* name="data\[tx_irretutorial_1ncsv_offer\]\[NEW[1-9]+\]\[sys_language_uid\]"[^>]*>/', $jsonArray['data']);
    }

    /**
     * @return array
     */
    protected function getContext()
    {
        $context = [
            'config' => [
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
                    'localizationMode' => 'select',
                    'localizeChildrenAtParentLocalization' => true,
                ],
                'default' => '',
                'minitems' => 0,
                'inline' => [
                    'first' => false,
                    'last' => false,
                ],
            ],
        ];

        return array_merge(
            $context,
            [
                'hmac' => GeneralUtility::hmac(serialize($context['config'])),
            ]
        );
    }
}
