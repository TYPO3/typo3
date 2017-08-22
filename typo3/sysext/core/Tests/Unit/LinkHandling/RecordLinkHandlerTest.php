<?php

namespace TYPO3\CMS\Core\Tests\Unit\LinkHandling;

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

use TYPO3\CMS\Core\LinkHandling\RecordLinkHandler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RecordLinkHandlerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function asStringReturnsUrl()
    {
        $subject = new RecordLinkHandler();
        $parameters = [
            'identifier' => 'tx_identifier',
            'uid' => 123
        ];
        $url = sprintf(
            't3://record?identifier=%s&uid=%s',
            $parameters['identifier'],
            $parameters['uid']
        );

        $this->assertEquals($url, $subject->asString($parameters));
    }

    /**
     * @return array
     */
    public function missingParameterDataProvider(): array
    {
        return [
            'identifier is missing' => [
                [
                    'uid' => 123
                ]
            ],
            'uid is missing' => [
                [
                    'identifier' => 'identifier',
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider missingParameterDataProvider
     * @param array $parameters
     */
    public function resolveHandlerDataThrowsExceptionIfParameterIsMissing(array $parameters)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1486155151);

        $subject = new RecordLinkHandler();
        $subject->resolveHandlerData($parameters);
    }

    /**
     * @test
     * @dataProvider missingParameterDataProvider
     * @param array $parameters
     */
    public function asStringThrowsExceptionIfParameterIsMissing(array $parameters)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1486155150);

        $subject = new RecordLinkHandler();
        $subject->asString($parameters);
    }
}
