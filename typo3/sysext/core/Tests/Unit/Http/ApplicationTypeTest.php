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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ApplicationTypeTest extends UnitTestCase
{
    #[Test]
    public function fromRequestThrowsIfTypeIsMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1606222812);
        ApplicationType::fromRequest(new ServerRequest());
    }

    #[Test]
    public function isFrontendReturnsTrueIfFrontend(): void
    {
        self::assertTrue(
            ApplicationType::fromRequest((new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE))
                ->isFrontend()
        );
    }

    #[Test]
    public function isFrontendReturnsFalseIfNotFrontend(): void
    {
        self::assertFalse(
            ApplicationType::fromRequest((new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE))
                ->isFrontend()
        );
    }

    #[Test]
    public function isBackendReturnsTrueIfBackend(): void
    {
        self::assertTrue(
            ApplicationType::fromRequest((new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE))
                ->isBackend()
        );
    }

    #[Test]
    public function isBackendReturnsTrueIfNotBackend(): void
    {
        self::assertFalse(
            ApplicationType::fromRequest((new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE))
                ->isBackend()
        );
    }

    #[Test]
    public function isFrontendEnumResolved(): void
    {
        $type = ApplicationType::FRONTEND;
        self::assertSame('FE', $type->abbreviate());
    }

    #[Test]
    public function isBackendEnumResolved(): void
    {
        $type = ApplicationType::BACKEND;
        self::assertSame('BE', $type->abbreviate());
    }
}
