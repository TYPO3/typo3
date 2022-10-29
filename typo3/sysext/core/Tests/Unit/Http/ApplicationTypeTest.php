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

namespace TYPO3\CMS\Core\Tests\Unit\Http;

use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ApplicationTypeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function fromRequestThrowsIfTypeIsMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1606222812);
        ApplicationType::fromRequest(new ServerRequest());
    }

    /**
     * @test
     */
    public function isFrontendReturnsTrueIfFrontend(): void
    {
        self::assertTrue(
            ApplicationType::fromRequest((new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE))
                ->isFrontend()
        );
    }

    /**
     * @test
     */
    public function isFrontendReturnsFalseIfNotFrontend(): void
    {
        self::assertFalse(
            ApplicationType::fromRequest((new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE))
                ->isFrontend()
        );
    }

    /**
     * @test
     */
    public function isBackendReturnsTrueIfBackend(): void
    {
        self::assertTrue(
            ApplicationType::fromRequest((new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE))
                ->isBackend()
        );
    }

    /**
     * @test
     */
    public function isBackendReturnsTrueIfNotBackend(): void
    {
        self::assertFalse(
            ApplicationType::fromRequest((new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE))
                ->isBackend()
        );
    }
}
