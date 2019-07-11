<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Install\Tests\Unit\Controller;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Install\Controller\UpgradeController;
use TYPO3\CMS\Install\Service\LateBootService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class UpgradeControllerTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function versionDataProvider(): array
    {
        return [
            ['master', false],
            ['1.0', false],
            ['1.10', false],
            ['2.3.4', false],
            ['2.3.20', false],
            ['7.6.x', false],
            ['10.0', false],
            ['10.10', false],
            ['10.10.5', false],
            ['10.10.husel', true],
            ['1.2.3.4', true],
            ['9.8.x.x', true],
            ['a.b.c', true],
            ['4.3.x.1', true],
            ['../../../../../../../etc/passwd', true],
            ['husel', true],
        ];
    }

    /**
     * @param string $version
     * @param bool $expectsException
     * @dataProvider versionDataProvider
     * @test
     */
    public function versionIsAsserted(string $version, bool $expectsException): void
    {
        if ($expectsException) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionCode(1537209128);
        }
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getQueryParams()->willReturn([
            'install' => [
                'version' => $version,
            ],
        ]);

        $packageManagerMock = $this->getMockBuilder(PackageManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $lateBootServiceMock = $this->getMockBuilder(LateBootService::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var UpgradeController|\PHPUnit\Framework\MockObject\MockObject $subject */
        $subject = $this->getMockBuilder(UpgradeController::class)
            ->setConstructorArgs([$packageManagerMock, $lateBootServiceMock])
            ->setMethods(['getDocumentationFiles', 'initializeStandaloneView'])
            ->getMock();

        $subject->expects($this->any())->method('getDocumentationFiles')->willReturn([
            'normalFiles' => [],
            'readFiles' => [],
            'notAffectedFiles' => [],
        ]);
        $subject->expects($this->any())
            ->method('initializeStandaloneView')
            ->willReturn($this->prophesize(StandaloneView::class)->reveal());
        $subject->upgradeDocsGetChangelogForVersionAction($requestProphecy->reveal());
    }
}
