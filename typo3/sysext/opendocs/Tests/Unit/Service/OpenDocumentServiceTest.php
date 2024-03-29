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

namespace TYPO3\CMS\Opendocs\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Opendocs\Service\OpenDocumentService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class OpenDocumentServiceTest extends UnitTestCase
{
    private BackendUserAuthentication&MockObject $backendUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backendUser = $this->getMockBuilder(BackendUserAuthentication::class)->disableOriginalConstructor()->getMock();
        $GLOBALS['BE_USER'] = $this->backendUser;
    }

    #[Test]
    public function getsOpenDocumentsFromUserSession(): void
    {
        $this->backendUser->method('getModuleData')->with('FormEngine', 'ses')->willReturn([
            [
                'identifier1' => [ 'data1' ],
                'identifier2' => [ 'data2' ],
            ],
            'identifier2',
        ]);
        $subject = new OpenDocumentService();
        $openDocuments = $subject->getOpenDocuments();
        $expected = [
            'identifier1' => [ 'data1' ],
            'identifier2' => [ 'data2' ],
        ];
        self::assertEquals($expected, $openDocuments);
    }

    #[Test]
    public function handlesUserSessionWithoutOpenDocuments(): void
    {
        $this->backendUser->method('getModuleData')->with('FormEngine', 'ses')->willReturn(null);
        $subject = new OpenDocumentService();
        $openDocuments = $subject->getOpenDocuments();
        self::assertEquals([], $openDocuments);
    }

    #[Test]
    public function getsRecentDocumentsFromUserSession(): void
    {
        $this->backendUser->method('getModuleData')->with('opendocs::recent')->willReturn([
            'identifier1' => [ 'data1' ],
        ]);
        $subject = new OpenDocumentService();
        $recentDocuments = $subject->getRecentDocuments();
        $expected = [
            'identifier1' => [ 'data1' ],
        ];
        self::assertEquals($expected, $recentDocuments);
    }

    #[Test]
    public function handlesUserSessionWithoutRecentDocuments(): void
    {
        $this->backendUser->method('getModuleData')->with('opendocs::recent')->willReturn(null);
        $subject = new OpenDocumentService();
        $recentDocuments = $subject->getRecentDocuments();
        self::assertEquals([], $recentDocuments);
    }

    #[Test]
    public function closesDocument(): void
    {
        $this->backendUser->method('getModuleData')->willReturnMap([
            [
                'FormEngine',
                'ses',
                [
                    [
                        'identifier8' => ['data8'],
                        'identifier9' => ['data9'],
                    ],
                    'identifier9',
                ],
            ],
            [
                'opendocs::recent',
                '',
                [
                    'identifier8' => [ 'data8' ],
                    'identifier7' => [ 'data7' ],
                    'identifier6' => [ 'data6' ],
                    'identifier5' => [ 'data5' ],
                    'identifier4' => [ 'data4' ],
                    'identifier3' => [ 'data3' ],
                    'identifier2' => [ 'data2' ],
                    'identifier1' => [ 'data1' ],
                ],
            ],
        ]);

        $expectedOpenDocumentsData = [
            [
                'identifier8' => [ 'data8' ],
            ],
            'identifier9',
        ];

        $expectedRecentDocumentsData = [
            'identifier9' => [ 'data9' ],
            'identifier8' => [ 'data8' ],
            'identifier7' => [ 'data7' ],
            'identifier6' => [ 'data6' ],
            'identifier5' => [ 'data5' ],
            'identifier4' => [ 'data4' ],
            'identifier3' => [ 'data3' ],
            'identifier2' => [ 'data2' ],
        ];

        $series = [
            ['FormEngine', $expectedOpenDocumentsData],
            ['opendocs::recent', $expectedRecentDocumentsData],
        ];
        $this->backendUser->method('pushModuleData')->willReturnCallback(function (string $module, array $data) use (&$series): void {
            $expectedArgs = array_shift($series);
            self::assertSame($expectedArgs[0], $module);
            self::assertSame($expectedArgs[1], $data);
        });

        $subject = new OpenDocumentService();
        $subject->closeDocument('identifier9');
    }
}
