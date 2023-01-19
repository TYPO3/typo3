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

namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class GeneralUtilityTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider gpMergedDataProvider
     */
    public function gpMergedWillMergeArraysFromGetAndPost($get, $post, $expected): void
    {
        $_POST = $post;
        $_GET = $get;
        self::assertEquals($expected, GeneralUtility::_GPmerged('cake'));
    }

    /**
     * Data provider for gpMergedWillMergeArraysFromGetAndPost
     */
    public function gpMergedDataProvider(): array
    {
        $fullDataArray = ['cake' => ['a' => 'is a', 'b' => 'lie']];
        $postPartData = ['cake' => ['b' => 'lie']];
        $getPartData = ['cake' => ['a' => 'is a']];
        $getPartDataModified = ['cake' => ['a' => 'is not a']];
        return [
            'Key doesn\' exist' => [['foo'], ['bar'], []],
            'No POST data' => [$fullDataArray, [], $fullDataArray['cake']],
            'No GET data' => [[], $fullDataArray, $fullDataArray['cake']],
            'POST and GET are merged' => [$getPartData, $postPartData, $fullDataArray['cake']],
            'POST is preferred over GET' => [$getPartDataModified, $fullDataArray, $fullDataArray['cake']],
        ];
    }

    /**
     * Data provider for canRetrieveGlobalInputsThroughGet
     * and canRetrieveGlobalInputsThroughPost
     * @todo once _GET() becomes deprecated too, only move the test, the provider was copied
     */
    public function getAndPostDataProvider(): array
    {
        return [
            'canRetrieveGlobalInputsThroughPosted input data doesn\'t exist' => ['cake', [], null],
            'No key will return entire input data' => [null, ['cake' => 'l\\ie'], ['cake' => 'l\\ie']],
            'Can retrieve specific input' => ['cake', ['cake' => 'l\\ie', 'foo'], 'l\\ie'],
            'Can retrieve nested input data' => ['cake', ['cake' => ['is a' => 'l\\ie']], ['is a' => 'l\\ie']],
        ];
    }

    /**
     * @test
     * @dataProvider getAndPostDataProvider
     */
    public function canRetrieveGlobalInputsThroughPost($key, $post, $expected): void
    {
        $_POST = $post;
        self::assertSame($expected, GeneralUtility::_POST($key));
    }
}
