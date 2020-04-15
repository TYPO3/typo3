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

namespace TYPO3\CMS\Core\Tests\Functional\Cache\Frontend;

use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class VariableFrontendTest extends FunctionalTestCase
{
    public function insertSerializedArrayIntoLobAndRetrieveItDataProvider()
    {
        $arrayToSerialize = [
            'string' => 'Serialize a string',
            'integer' => 0,
            'anotherIntegerValue' => 123456,
            'float' => 12.34,
            'bool' => true,
            'array' => [
                0 => 'test',
                1 => 'another test',
            ],
        ];

        return [
            [
                $arrayToSerialize,
                'pages',
                $arrayToSerialize,
            ]
        ];
    }

    /**
     * @dataProvider insertSerializedArrayIntoLobAndRetrieveItDataProvider
     *
     * @test
     *
     * @param array $expected
     * @param string $identifier
     * @param array $arrayToSerialize
     */
    public function insertSerializedArrayIntoLobAndRetrieveIt(
        array $expected,
        string $identifier,
        array $arrayToSerialize
    ) {
        $typo3DatabaseBackend = new Typo3DatabaseBackend('Testing');
        $subject = new VariableFrontend($identifier, $typo3DatabaseBackend);

        $subject->set('myIdentifier', $arrayToSerialize);
        self::assertSame($expected, $subject->get('myIdentifier'));
    }
}
