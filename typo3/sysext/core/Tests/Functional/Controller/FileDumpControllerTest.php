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

namespace TYPO3\CMS\Core\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Controller\FileDumpController;
use TYPO3\CMS\Core\Crypto\HashAlgo;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileDumpControllerTest extends FunctionalTestCase
{
    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Controller/FileDumpController/Folders/fileadmin/user_upload/harmless.jpg' => 'fileadmin/user_upload/harmless.jpg',
        'typo3/sysext/core/Tests/Functional/Fixtures/Controller/FileDumpController/Folders/fileadmin/user_upload/secret.jpg' => 'fileadmin/user_upload/secret.jpg',
        'typo3/sysext/core/Tests/Functional/Fixtures/Controller/FileDumpController/Folders/fileadmin/_processed_/harmless_processed.jpg' => 'fileadmin/_processed_/harmless_processed.jpg',
        'typo3/sysext/core/Tests/Functional/Fixtures/Controller/FileDumpController/Folders/fileadmin/_processed_/secret_processed.jpg' => 'fileadmin/_processed_/secret_processed.jpg',
    ];

    private FileDumpController $controller;
    private HashService $hashService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Controller/FileDumpController/DatabaseImport.csv');
        $this->controller = $this->get(FileDumpController::class);
        $this->hashService = $this->get(HashService::class);
    }

    /**
     * Fixture layout (uids deliberately colliding across tables):
     *   sys_file uid=1                 -> harmless.jpg
     *   sys_file uid=2                 -> secret.jpg
     *   sys_file_reference uid=1       -> uid_local=2 (secret.jpg)   <- collides with sys_file uid 1
     *   sys_file_reference uid=2       -> uid_local=1 (harmless.jpg) <- legitimate
     *   sys_file_processedfile uid=1   -> original=2 (secret.jpg)    <- collides with sys_file uid 1
     *   sys_file_processedfile uid=3   -> original=1 (harmless.jpg)  <- legitimate
     */
    public static function replayedTokenIsRejectedDataProvider(): iterable
    {
        yield 'token for f=1 replayed as r=1 must not resolve sys_file_reference' => [
            ['eID' => 'dumpFile', 't' => 'f', 'f' => 1],
            ['eID' => 'dumpFile', 't' => 'f', 'r' => 1],
        ];
        yield 'token for f=1 replayed as p=1 must not resolve sys_file_processedfile' => [
            ['eID' => 'dumpFile', 't' => 'f', 'f' => 1],
            ['eID' => 'dumpFile', 't' => 'f', 'p' => 1],
        ];
    }

    #[Test]
    #[DataProvider('replayedTokenIsRejectedDataProvider')]
    public function replayedTokenIsRejected(array $signedParameters, array $requestParameters): void
    {
        $token = $this->createToken($signedParameters);
        $response = $this->controller->dumpAction($this->buildRequest($requestParameters, $token));

        self::assertSame(403, $response->getStatusCode());
    }

    public static function matchingSelectorAndTypeSucceedsDataProvider(): iterable
    {
        yield 'sys_file, harmless' => [['eID' => 'dumpFile', 't' => 'f', 'f' => 1], 'harmless.jpg'];
        yield 'sys_file, secret' => [['eID' => 'dumpFile', 't' => 'f', 'f' => 2], 'secret.jpg'];
        yield 'sys_file_reference, legitimate' => [['eID' => 'dumpFile', 't' => 'r', 'r' => 2], 'harmless.jpg'];
        yield 'sys_file_processedfile, legitimate' => [['eID' => 'dumpFile', 't' => 'p', 'p' => 3], 'harmless_processed.jpg'];
    }

    #[Test]
    #[DataProvider('matchingSelectorAndTypeSucceedsDataProvider')]
    public function matchingSelectorAndTypeSucceeds(array $parameters, string $expectedFilename): void
    {
        $token = $this->createToken($parameters);
        $response = $this->controller->dumpAction($this->buildRequest($parameters, $token));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString($expectedFilename, $response->getHeaderLine('Content-Disposition'));
    }

    #[Test]
    public function requestWithoutAnySelectorIsRejected(): void
    {
        $parameters = ['eID' => 'dumpFile', 't' => 'f'];
        $token = $this->createToken($parameters);
        $response = $this->controller->dumpAction($this->buildRequest($parameters, $token));

        self::assertSame(403, $response->getStatusCode());
    }

    #[Test]
    public function requestWithTwoSelectorsIsRejected(): void
    {
        $parameters = ['eID' => 'dumpFile', 't' => 'f', 'f' => 1, 'r' => 2];
        $token = $this->createToken($parameters);
        $response = $this->controller->dumpAction($this->buildRequest($parameters, $token));

        self::assertSame(403, $response->getStatusCode());
    }

    #[Test]
    public function requestWithTypeNotMatchingAnySelectorIsRejected(): void
    {
        $parameters = ['eID' => 'dumpFile', 't' => 'x', 'f' => 1];
        $token = $this->createToken($parameters);
        $response = $this->controller->dumpAction($this->buildRequest($parameters, $token));

        self::assertSame(403, $response->getStatusCode());
    }

    private function createToken(array $parameters): string
    {
        return $this->hashService->hmac(implode('|', $parameters), 'resourceStorageDumpFile', HashAlgo::SHA3_256);
    }

    private function buildRequest(array $parameters, string $token): ServerRequestInterface
    {
        unset($parameters['eID']);
        return new ServerRequest()->withQueryParams(array_merge(['eID' => 'dumpFile'], $parameters, ['token' => $token]));
    }
}
