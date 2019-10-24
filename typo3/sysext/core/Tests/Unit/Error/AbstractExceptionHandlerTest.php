<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Error;

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

use TYPO3\CMS\Core\Error\AbstractExceptionHandler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the AbstractExceptionHandlerTest class
 */
class AbstractExceptionHandlerTest extends UnitTestCase
{
    /**
     * Data provider with allowed contexts.
     *
     * @return array
     */
    public function exampleUrlsForTokenAnonymization(): array
    {
        return [
            'url with valid token' => [
                'http://localhost/typo3/index.php?M=foo&moduleToken=5f1f7d447f22886e8ea206693b0d530ccd6b2b36',
                'http://localhost/typo3/index.php?M=foo&moduleToken=--AnonymizedToken--'
            ],
            'url with valid token in the middle' => [
                'http://localhost/typo3/index.php?M=foo&moduleToken=5f1f7d447f22886e8ea206693b0d530ccd6b2b36&param=asdf',
                'http://localhost/typo3/index.php?M=foo&moduleToken=--AnonymizedToken--&param=asdf'
            ],
            'url with invalid token' => [
                'http://localhost/typo3/index.php?M=foo&moduleToken=5f1f7d447f22886e8/e',
                'http://localhost/typo3/index.php?M=foo&moduleToken=5f1f7d447f22886e8/e',
            ],
            'url with empty token' => [
                'http://localhost/typo3/index.php?M=foo&moduleToken=',
                'http://localhost/typo3/index.php?M=foo&moduleToken=',
            ],
            'url with no token' => [
                'http://localhost/typo3/index.php?M=foo',
                'http://localhost/typo3/index.php?M=foo',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider exampleUrlsForTokenAnonymization
     * @param string $originalUrl
     * @param string $expectedUrl
     */
    public function anonymizeTokenReturnsCorrectModifiedUrl(string $originalUrl, string $expectedUrl)
    {
        $mock = $this->getAccessibleMockForAbstractClass(AbstractExceptionHandler::class, ['dummy']);
        $anonymizedUrl = $mock->_call('anonymizeToken', $originalUrl);
        self::assertSame($expectedUrl, $anonymizedUrl);
    }
}
