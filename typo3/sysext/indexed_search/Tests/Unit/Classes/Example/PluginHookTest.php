<?php

namespace TYPO3\CMS\IndexedSearch\Tests\Unit;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\IndexedSearch\Example\PluginHook;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * This class contains unit tests for the indexer
 */
class PluginHookTest extends UnitTestCase
{
    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    /**
     * Indexer instance
     *
     * @var \TYPO3\CMS\IndexedSearch\Example\PluginHook
     */
    protected $subject;

    /**
     * Sets up the test
     */
    protected function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
        $this->subject = new PluginHook();
    }

    /**
     * @test
     */
    public function getDisplayResults_postProcReturnsTheOriginalSearchResultBecauseOfMissingItems()
    {
        $searchResult = [
            'count' => 0,
            'rows' => []
        ];

        $result = $this->subject->getDisplayResults_postProc($searchResult);
        self::assertSame($searchResult, $result);
    }

    /**
     * @test
     */
    public function getDisplayResults_postProcModifiesTheDescriptionInARowOfSearchResult()
    {
        $searchResult = [
            'count' => 2,
            'rows' => [
                ['description' => 'I am a description field with joe and foo.'],
                ['description' => 'Description will be modified to two bar. foo, bar, joe. ']
            ]
        ];

        $expected = [
            'count' => 2,
            'rows' => [
                ['description' => 'I am a description field with joe and bar.'],
                ['description' => 'Description will be modified to two bar. bar, bar, joe. ']
            ]
        ];

        $result = $this->subject->getDisplayResults_postProc($searchResult);
        self::assertSame($expected, $result);
    }

    /**
     * Tear down the tests
     */
    protected function tearDown()
    {
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        unset($this->subject);
        parent::tearDown();
    }
}
