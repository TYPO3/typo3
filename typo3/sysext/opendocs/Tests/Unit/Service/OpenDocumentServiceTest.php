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

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Opendocs\Service\OpenDocumentService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class OpenDocumentServiceTest extends UnitTestCase
{
    protected OpenDocumentService $subject;
    protected MockObject&BackendUserAuthentication $backendUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backendUser = $this->getMockBuilder(BackendUserAuthentication::class)->disableOriginalConstructor()->getMock();
        $GLOBALS['BE_USER'] = $this->backendUser;
        $this->subject = new OpenDocumentService();
    }

    /**
     * @test
     */
    public function getsOpenDocumentsFromUserSession(): void
    {
        $this->backendUser->method('getModuleData')->with('FormEngine', 'ses')->willReturn([
            [
                'identifier1' => [ 'data1' ],
                'identifier2' => [ 'data2' ],
            ],
            'identifier2',
        ]);

        $openDocuments = $this->subject->getOpenDocuments();
        $expected = [
            'identifier1' => [ 'data1' ],
            'identifier2' => [ 'data2' ],
        ];

        self::assertEquals($expected, $openDocuments);
    }

    /**
     * @test
     */
    public function handlesUserSessionWithoutOpenDocuments(): void
    {
        $this->backendUser->method('getModuleData')->with('FormEngine', 'ses')->willReturn(null);

        $openDocuments = $this->subject->getOpenDocuments();

        self::assertEquals([], $openDocuments);
    }

    /**
     * @test
     */
    public function getsRecentDocumentsFromUserSession(): void
    {
        $this->backendUser->method('getModuleData')->with('opendocs::recent')->willReturn([
            'identifier1' => [ 'data1' ],
        ]);

        $recentDocuments = $this->subject->getRecentDocuments();
        $expected = [
            'identifier1' => [ 'data1' ],
        ];

        self::assertEquals($expected, $recentDocuments);
    }

    /**
     * @test
     */
    public function handlesUserSessionWithoutRecentDocuments(): void
    {
        $this->backendUser->method('getModuleData')->with('opendocs::recent')->willReturn(null);

        $recentDocuments = $this->subject->getRecentDocuments();

        self::assertEquals([], $recentDocuments);
    }

    /**
     * @test
     */
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

        $this->backendUser->expects(self::atLeastOnce())->method('pushModuleData')->withConsecutive(
            ['FormEngine', $expectedOpenDocumentsData],
            ['opendocs::recent', $expectedRecentDocumentsData]
        );

        $this->subject->closeDocument('identifier9');
        $this->subject->closeDocument('identifier9');
        $this->subject->closeDocument('unknownIdentifier');
    }
}
