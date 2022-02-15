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

use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Install\Controller\UpgradeController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class UpgradeControllerTest extends UnitTestCase
{
    use ProphecyTrait;

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

        /** @var UpgradeController|MockObject $subject */
        $subject = $this->getMockBuilder(UpgradeController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDocumentationFiles', 'initializeView'])
            ->getMock();

        $subject->method('getDocumentationFiles')->willReturn([
            'normalFiles' => [],
            'readFiles' => [],
            'notAffectedFiles' => [],
        ]);
        $viewProphecy = $this->prophesize(ViewInterface::class);
        $viewProphecy->assignMultiple(Argument::cetera())->willReturn($viewProphecy->reveal());
        $viewProphecy->render(Argument::cetera())->willReturn('');
        $subject->method('initializeView')->willReturn($viewProphecy->reveal());
        $subject->upgradeDocsGetChangelogForVersionAction($requestProphecy->reveal());
    }
}
