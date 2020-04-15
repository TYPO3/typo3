<?php

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

namespace TYPO3\CMS\Core\Tests\Unit\Cache\Frontend;

use TYPO3\CMS\Core\Cache\Backend\PhpCapableBackendInterface;
use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the PHP source code cache frontend
 */
class PhpFrontendTest extends UnitTestCase
{
    /**
     * @test
     */
    public function setChecksIfTheIdentifierIsValid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1264023823);

        $cache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['isValidEntryIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache->expects(self::once())->method('isValidEntryIdentifier')->with('foo')->willReturn(false);
        $cache->set('foo', 'bar');
    }

    /**
     * @test
     */
    public function setPassesPhpSourceCodeTagsAndLifetimeToBackend()
    {
        $originalSourceCode = 'return "hello world!";';
        $modifiedSourceCode = '<?php' . chr(10) . $originalSourceCode . chr(10) . '#';
        $mockBackend = $this->createMock(PhpCapableBackendInterface::class);
        $mockBackend->expects(self::once())->method('set')->with('Foo-Bar', $modifiedSourceCode, ['tags'], 1234);
        $cache = GeneralUtility::makeInstance(PhpFrontend::class, 'PhpFrontend', $mockBackend);
        $cache->set('Foo-Bar', $originalSourceCode, ['tags'], 1234);
    }

    /**
     * @test
     */
    public function setThrowsInvalidDataExceptionOnNonStringValues()
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionCode(1264023824);

        $cache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache->set('Foo-Bar', []);
    }

    /**
     * @test
     */
    public function requireOnceCallsTheBackendsRequireOnceMethod()
    {
        $mockBackend = $this->createMock(PhpCapableBackendInterface::class);
        $mockBackend->expects(self::once())->method('requireOnce')->with('Foo-Bar')->willReturn('hello world!');
        $cache = GeneralUtility::makeInstance(PhpFrontend::class, 'PhpFrontend', $mockBackend);
        $result = $cache->requireOnce('Foo-Bar');
        self::assertSame('hello world!', $result);
    }

    /**
     * @test
     */
    public function requireCallsTheBackendsRequireMethod()
    {
        $mockBackend = $this->createMock(SimpleFileBackend::class);
        $mockBackend->expects(self::once())->method('require')->with('Foo-Bar')->willReturn('hello world!');
        $cache = GeneralUtility::makeInstance(PhpFrontend::class, 'PhpFrontend', $mockBackend);
        $result = $cache->require('Foo-Bar');
        self::assertSame('hello world!', $result);
    }
}
