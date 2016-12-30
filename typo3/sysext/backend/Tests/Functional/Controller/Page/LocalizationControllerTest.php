<?php
namespace TYPO3\CMS\Backend\Tests\Functional\Controller\Page;

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

use TYPO3\CMS\Backend\Controller\Page\LocalizationController;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Tests\FunctionalTestCase;

/**
 * Test case for TYPO3\CMS\Backend\Controller\Page\LocalizationController
 */
class LocalizationControllerTest extends FunctionalTestCase
{
    /**
     * @var LocalizationController
     */
    protected $subject;

    /**
     * Sets up this test case.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->setUpBackendUserFromFixture(1);
        Bootstrap::getInstance()->initializeLanguageObject();

        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/Fixtures/pages.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/sys_language.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/Controller/Page/Fixtures/tt_content-default-language.xml');

        $this->subject = new LocalizationController();
    }

    /**
     * @test
     */
    public function recordsGetTranslatedFromDefaultLanguage()
    {
        $params = [
            'pageId' => 1,
            'srcLanguageId' => 0,
            'destLanguageId' => 1,
            'uidList' => [1, 2, 3],
            'action' => LocalizationController::ACTION_LOCALIZE,
        ];
        $this->callInaccessibleMethod($this->subject, 'process', $params);

        $expectedResults = [
            [
                'pid' => '1',
                'sys_language_uid' => '1',
                'l18n_parent' => '1',
                'header' => '[Translate to Dansk:] Test content 1',
            ],
            [
                'pid' => '1',
                'sys_language_uid' => '1',
                'l18n_parent' => '2',
                'header' => '[Translate to Dansk:] Test content 2',
            ],
            [
                'pid' => '1',
                'sys_language_uid' => '1',
                'l18n_parent' => '3',
                'header' => '[Translate to Dansk:] Test content 3',
            ],
        ];
        $results = $this->getDatabaseConnection()->exec_SELECTgetRows('pid, sys_language_uid, l18n_parent, header', 'tt_content', 'pid = 1 AND sys_language_uid = 1');
        $this->assertSame($expectedResults, $results);
    }

    /**
     * @test
     */
    public function recordsGetTranslatedFromDifferentTranslation()
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/Controller/Page/Fixtures/tt_content-danish-language.xml');

        $params = [
            'pageId' => 1,
            'srcLanguageId' => 1,
            'destLanguageId' => 2,
            'uidList' => [4, 5, 6], // uids of tt_content-danish-language
            'action' => LocalizationController::ACTION_LOCALIZE,
        ];
        $this->callInaccessibleMethod($this->subject, 'process', $params);

        $expectedResults = [
            [
                'pid' => '1',
                'sys_language_uid' => '2',
                'l18n_parent' => '1',
                'header' => '[Translate to Deutsch:] Test indhold 1',
            ],
            [
                'pid' => '1',
                'sys_language_uid' => '2',
                'l18n_parent' => '2',
                'header' => '[Translate to Deutsch:] Test indhold 2',
            ],
            [
                'pid' => '1',
                'sys_language_uid' => '2',
                'l18n_parent' => '3',
                'header' => '[Translate to Deutsch:] Test indhold 3',
            ],
        ];
        $results = $this->getDatabaseConnection()->exec_SELECTgetRows('pid, sys_language_uid, l18n_parent, header', 'tt_content', 'pid = 1 AND sys_language_uid = 2');
        $this->assertSame($expectedResults, $results);
    }

    /**
     * @test
     */
    public function recordsGetCopiedFromDefaultLanguage()
    {
        $params = [
            'pageId' => 1,
            'srcLanguageId' => 0,
            'destLanguageId' => 2,
            'uidList' => [1, 2, 3],
            'action' => LocalizationController::ACTION_COPY,
        ];
        $this->callInaccessibleMethod($this->subject, 'process', $params);

        $expectedResults = [
            [
                'pid' => '1',
                'sys_language_uid' => '2',
                'l18n_parent' => '0',
                'header' => '[Translate to Deutsch:] Test content 1',
            ],
            [
                'pid' => '1',
                'sys_language_uid' => '2',
                'l18n_parent' => '0',
                'header' => '[Translate to Deutsch:] Test content 2',
            ],
            [
                'pid' => '1',
                'sys_language_uid' => '2',
                'l18n_parent' => '0',
                'header' => '[Translate to Deutsch:] Test content 3',
            ],
        ];
        $results = $this->getDatabaseConnection()->exec_SELECTgetRows('pid, sys_language_uid, l18n_parent, header', 'tt_content', 'pid = 1 AND sys_language_uid = 2');
        $this->assertSame($expectedResults, $results);
    }

    /**
     * @test
     */
    public function recordsGetCopiedFromAnotherLanguage()
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/Controller/Page/Fixtures/tt_content-danish-language.xml');

        $params = [
            'pageId' => 1,
            'srcLanguageId' => 1,
            'destLanguageId' => 2,
            'uidList' => [4, 5, 6], // uids of tt_content-danish-language
            'action' => LocalizationController::ACTION_COPY,
        ];
        $this->callInaccessibleMethod($this->subject, 'process', $params);

        $expectedResults = [
            [
                'pid' => '1',
                'sys_language_uid' => '2',
                'l18n_parent' => '0',
                'header' => '[Translate to Deutsch:] Test indhold 1',
            ],
            [
                'pid' => '1',
                'sys_language_uid' => '2',
                'l18n_parent' => '0',
                'header' => '[Translate to Deutsch:] Test indhold 2',
            ],
            [
                'pid' => '1',
                'sys_language_uid' => '2',
                'l18n_parent' => '0',
                'header' => '[Translate to Deutsch:] Test indhold 3',
            ],
        ];
        $results = $this->getDatabaseConnection()->exec_SELECTgetRows('pid, sys_language_uid, l18n_parent, header', 'tt_content', 'pid = 1 AND sys_language_uid = 2');
        $this->assertSame($expectedResults, $results);
    }

    /**
     * @test
     */
    public function copyingNewContentFromLanguageIntoExistingLocalizationHasSameOrdering()
    {
        $params = [
            'pageId' => 1,
            'srcLanguageId' => 0,
            'destLanguageId' => 1,
            'uidList' => [1, 2, 3],
            'action' => LocalizationController::ACTION_COPY,
        ];
        $this->callInaccessibleMethod($this->subject, 'process', $params);

        // Create another content element in default language
        $data = [
            'tt_content' => [
                'NEW123456' => [
                    'sys_language_uid' => 0,
                    'header' => 'Test content 2.5',
                    'pid' => -2,
                ],
            ],
        ];
        $dataHandler = new DataHandler();
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();
        $newContentElementUid = $dataHandler->substNEWwithIDs['NEW123456'];

        // Copy the new content element
        $params = [
            'pageId' => 1,
            'srcLanguageId' => 0,
            'destLanguageId' => 1,
            'uidList' => [$newContentElementUid],
            'action' => LocalizationController::ACTION_COPY,
        ];
        $this->callInaccessibleMethod($this->subject, 'process', $params);

        $expectedResults = [
            [
                'pid' => '1',
                'sys_language_uid' => '1',
                'l18n_parent' => '0',
                'header' => '[Translate to Dansk:] Test content 1',
            ],
            [
                'pid' => '1',
                'sys_language_uid' => '1',
                'l18n_parent' => '0',
                'header' => '[Translate to Dansk:] Test content 2.5',
            ],
            [
                'pid' => '1',
                'sys_language_uid' => '1',
                'l18n_parent' => '0',
                'header' => '[Translate to Dansk:] Test content 2',
            ],
            [
                'pid' => '1',
                'sys_language_uid' => '1',
                'l18n_parent' => '0',
                'header' => '[Translate to Dansk:] Test content 3',
            ],
        ];
        $results = $this->getDatabaseConnection()->exec_SELECTgetRows('pid, sys_language_uid, l18n_parent, header', 'tt_content', 'pid = 1 AND sys_language_uid = 1', '', 'sorting ASC');
        $this->assertSame($expectedResults, $results);
    }
}
