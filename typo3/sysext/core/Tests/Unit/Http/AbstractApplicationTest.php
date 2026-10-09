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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\AbstractApplication;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AbstractApplicationTest extends UnitTestCase
{
    public static function sendResponseKeepsStatusCodeWithWwwAuthenticateHeaderDataProvider(): array
    {
        return [
            'bad request' => [400],
            'unauthorized' => [401],
            'forbidden' => [403],
        ];
    }

    /**
     * header() is only effective while no output was sent, which requires a process of its own
     */
    #[Test]
    #[DataProvider('sendResponseKeepsStatusCodeWithWwwAuthenticateHeaderDataProvider')]
    #[RunInSeparateProcess]
    public function sendResponseKeepsStatusCodeWithWwwAuthenticateHeader(int $statusCode): void
    {
        // The CLI SAPI starts without a status code, a web SAPI starts with 200
        http_response_code(200);
        $subject = new class extends AbstractApplication {
            public function emit(ResponseInterface $response): void
            {
                $this->sendResponse($response);
            }
        };
        $subject->emit(new Response(null, $statusCode, ['WWW-Authenticate' => 'Bearer error="insufficient_scope"']));
        self::assertSame($statusCode, http_response_code());
    }
}
