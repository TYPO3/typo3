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

namespace TYPO3\CMS\Install\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Install\Controller\UpgradeController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UpgradeControllerTest extends UnitTestCase
{
    public static function versionDataProviderWithoutException(): array
    {
        return [
            ['master'],
            ['1.0'],
            ['1.10'],
            ['2.3.4'],
            ['2.3.20'],
            ['7.6.x'],
            ['10.0'],
            ['10.10'],
            ['10.10.5'],
        ];
    }

    #[DataProvider('versionDataProviderWithoutException')]
    #[Test]
    #[DoesNotPerformAssertions]
    public function versionIsAccepted(string $version): void
    {
        $request = (new ServerRequest())->withQueryParams([
            'install' => [
                'version' => $version,
            ],
        ]);
        $subject = $this->getMockBuilder(UpgradeController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDocumentationFiles', 'initializeView'])
            ->getMock();
        $subject->method('getDocumentationFiles')->willReturn([
            'normalFiles' => [],
            'readFiles' => [],
            'notAffectedFiles' => [],
        ]);
        $viewMock = $this->getMockBuilder(ViewInterface::class)->getMock();
        $viewMock->expects($this->any())->method('assignMultiple')->willReturn($viewMock);
        $viewMock->expects($this->any())->method('render')->willReturn('');
        $subject->method('initializeView')->willReturn($viewMock);
        $subject->upgradeDocsGetChangelogForVersionAction($request);
    }
    public static function versionDataProvider(): array
    {
        return [
            ['10.10.husel'],
            ['1.2.3.4'],
            ['9.8.x.x'],
            ['a.b.c'],
            ['4.3.x.1'],
            ['../../../../../../../etc/passwd'],
            ['husel'],
        ];
    }

    #[DataProvider('versionDataProvider')]
    #[Test]
    public function versionIsNotAccepted(string $version): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1537209128);
        $request = (new ServerRequest())->withQueryParams([
            'install' => [
                'version' => $version,
            ],
        ]);
        $subject = $this->getMockBuilder(UpgradeController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDocumentationFiles', 'initializeView'])
            ->getMock();
        $subject->method('getDocumentationFiles')->willReturn([
            'normalFiles' => [],
            'readFiles' => [],
            'notAffectedFiles' => [],
        ]);
        $viewMock = $this->getMockBuilder(ViewInterface::class)->getMock();
        $viewMock->expects($this->any())->method('assignMultiple')->willReturn($viewMock);
        $viewMock->expects($this->any())->method('render')->willReturn('');
        $subject->method('initializeView')->willReturn($viewMock);
        $subject->upgradeDocsGetChangelogForVersionAction($request);
    }
}
