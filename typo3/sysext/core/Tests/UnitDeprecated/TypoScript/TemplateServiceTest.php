<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Tests\UnitDeprecated\TypoScript;

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

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TemplateServiceTest extends UnitTestCase
{
    /**
     * @var TemplateService
     */
    protected $templateService;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $packageManagerProphecy = $this->prophesize(PackageManager::class);
        $this->templateService = new TemplateService(new Context(), $packageManagerProphecy->reveal());
    }

    /**
     * @test
     */
    public function getFileNameReturnsUrlCorrectly(): void
    {
        $this->assertSame('http://example.com', $this->templateService->getFileName('http://example.com'));
        $this->assertSame('https://example.com', $this->templateService->getFileName('https://example.com'));
    }

    /**
     * @test
     */
    public function getFileNameReturnsFileCorrectly(): void
    {
        $this->assertSame('typo3/index.php', $this->templateService->getFileName('typo3/index.php'));
    }

    /**
     * @test
     */
    public function getFileNameReturnsNullIfDirectory(): void
    {
        $this->assertNull($this->templateService->getFileName(__DIR__));
    }

    /**
     * @test
     */
    public function getFileNameReturnsNullWithInvalidFileName(): void
    {
        $this->assertNull($this->templateService->getFileName('  '));
        $this->assertNull($this->templateService->getFileName('something/../else'));
    }
}
