<?php
namespace TYPO3\CMS\IndexedSearch\Tests\Functional\Tca;

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

use TYPO3\CMS\Backend\Tests\Functional\Form\FormTestService;
use TYPO3\CMS\Core\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

class IndexConfigVisibleFieldsTest extends FunctionalTestCase
{
    protected static $commonIndexConfigFields = [
        'title',
        'starttime',
        'hidden',
        'description',
        'timer_next_indexing',
        'timer_offset',
        'timer_frequency',
        'type',
    ];

    protected static $indexConfigFieldsByType = [
        '0' => [],
        '1' => [
            'table2index',
            'alternative_source_pid',
            'fieldlist',
            'get_params',
            'chashcalc',
            'recordsbatch',
            'records_indexonchange',
        ],
        '2' => [
            'filepath',
            'extensions',
            'depth',
        ],
        '3' => [
            'externalUrl',
            'depth',
            'url_deny',
        ],
        '4' => [
            'alternative_source_pid',
            'depth',
        ],
    ];

    protected static $metaIndexConfigFields = [
        'title',
        'description',
        'type',
        'indexcfgs',
    ];

    protected $coreExtensionsToLoad = ['indexed_search'];

    /**
     * @test
     */
    public function indexConfigFormContainsExpectedFields()
    {
        $this->setUpBackendUserFromFixture(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $formEngineTestService = GeneralUtility::makeInstance(FormTestService::class);

        foreach (static::$indexConfigFieldsByType as $type => $additionalFields) {
            $formResult = $formEngineTestService->createNewRecordForm('index_config', ['type' => $type]);
            $expectedFields = array_merge(static::$commonIndexConfigFields, $additionalFields);

            foreach ($expectedFields as $expectedField) {
                $this->assertNotFalse(
                    $formEngineTestService->formHtmlContainsField($expectedField, $formResult['html']),
                    'The field ' . $expectedField . ' is not in the form HTML for index type ' . $type
                );
            }

            $this->assertNotFalse(
                strpos($formResult['html'], 'Session ID'),
                'The field Session ID is not in the form HTML'
            );
        }
    }

    /**
     * @test
     */
    public function indexConfigFormContainsExpectedFieldsForMetaConfiguration()
    {
        $this->setUpBackendUserFromFixture(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $formEngineTestService = GeneralUtility::makeInstance(FormTestService::class);
        $formResult = $formEngineTestService->createNewRecordForm('index_config', ['type' => '5']);

        foreach (static::$metaIndexConfigFields as $expectedField) {
            $this->assertNotFalse(
                $formEngineTestService->formHtmlContainsField($expectedField, $formResult['html']),
                'The field ' . $expectedField . ' is not in the form HTML for index type ' . $type
            );
        }
    }
}
