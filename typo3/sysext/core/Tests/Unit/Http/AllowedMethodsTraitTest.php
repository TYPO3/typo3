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

use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\Error\MethodNotAllowedException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AllowedMethodsTraitTest extends UnitTestCase
{
    private Fixtures\AllowedMethodsTraitTestAccessor $accessor;
    private ServerRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accessor = new Fixtures\AllowedMethodsTraitTestAccessor();
        $this->request = new ServerRequest('GET', 'https://typo3-testing.local/');
    }

    #[Test]
    public function assertAllowedHttpMethodThrowsExceptionIfNoAllowedHttpMethodsAreGiven(): void
    {
        $this->expectExceptionObject(
            new \LogicException(
                'Allowed HTTP methods cannot be empty.',
                1732188461,
            ),
        );

        $this->accessor->callAssertAllowedHttpMethod($this->request);
    }

    #[Test]
    public function assertAllowedHttpMethodDoesNotThrowExceptionIfRequestMethodIsAllowed(): void
    {
        $exception = null;

        try {
            $this->accessor->callAssertAllowedHttpMethod($this->request, 'GET');
        } catch (MethodNotAllowedException $exception) {
        }

        self::assertNull($exception);
    }

    #[Test]
    public function assertAllowedHttpMethodThrowsExceptionIfRequestMethodIsNotAllowed(): void
    {
        $this->expectExceptionObject(
            new MethodNotAllowedException(['DELETE', 'POST'], 1732193708)
        );

        $this->accessor->callAssertAllowedHttpMethod($this->request, 'DELETE', 'POST');
    }
}
