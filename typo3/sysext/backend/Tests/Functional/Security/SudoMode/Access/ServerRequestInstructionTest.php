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

namespace TYPO3\CMS\Backend\Tests\Functional\Security\SudoMode\Access;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Backend\Security\SudoMode\Access\ServerRequestInstruction;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ServerRequestInstructionTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function instructionCanBeDehydratedDataProvider(): \Generator
    {
        $body = new Stream('php://temp', 'w+b');
        $body->write('test-content');
        $body->rewind();
        yield 'in-memory content' => [$body];

        $body = new Stream(__DIR__ . '/Fixtures/test-content.txt', 'r');
        $body->rewind();
        yield 'file content' => [$body];
    }

    #[Test]
    #[DataProvider('instructionCanBeDehydratedDataProvider')]
    public function instructionCanBeDehydrated(StreamInterface $body): void
    {
        $headers = ['Content-Type' => ['text/plain']];
        $request = new ServerRequest('https://example.com', 'POST', $body, $headers);
        $instruction = ServerRequestInstruction::createForServerRequest($request);
        $json = json_encode($instruction);
        $data = json_decode($json, true);
        $result = ServerRequestInstruction::buildFromArray($data);
        self::assertEquals($result->getUri(), $instruction->getUri());
        self::assertSame($result->getMethod(), $instruction->getMethod());
        self::assertSame($result->getBody()->getContents(), $instruction->getBody()->getContents());
        self::assertSame($result->getHeaders(), $instruction->getHeaders());
    }
}
