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

use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\UriFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Http\UriFactory
 */
class UriFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function implementsPsr17FactoryInterface()
    {
        $factory = new UriFactory();
        self::assertInstanceOf(UriFactoryInterface::class, $factory);
    }

    /**
     * @test
     */
    public function testUriIsCreated()
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('https://user:pass@domain.localhost:3000/path?query');

        self::assertInstanceOf(UriInterface::class, $uri);
        self::assertSame('user:pass', $uri->getUserInfo());
        self::assertSame('domain.localhost', $uri->getHost());
        self::assertSame(3000, $uri->getPort());
        self::assertSame('/path', $uri->getPath());
        self::assertSame('query', $uri->getQuery());
    }
}
