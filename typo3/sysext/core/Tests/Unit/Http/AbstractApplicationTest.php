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

namespace TYPO3\CMS\Core\Tests\Unit\Http;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\AbstractApplication;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AbstractApplicationTest extends UnitTestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testCookiesAreSentAsMultipleHeaders()
    {
        if (!extension_loaded('xdebug')) {
            self::markTestSkipped('This test can only be executed if xdebug is present.');
        }

        $application = new class() extends AbstractApplication {
            public function sendResponse(ResponseInterface $response)
            {
                parent::sendResponse($response);
            }
        };

        $response = (new Response())
            ->withStatus(204)
            ->withAddedHeader('Cache-Control', 'public')
            ->withAddedHeader('Cache-Control', 'max-age=3600')
            ->withAddedHeader('Set-Cookie', 'foo=bar')
            ->withAddedHeader('Set-Cookie', 'baz=foobar');

        $application->sendResponse($response);

        self::assertSame([
            'Cache-Control: public, max-age=3600',
            'Set-Cookie: foo=bar',
            'Set-Cookie: baz=foobar',
        ], xdebug_get_headers());
    }
}
