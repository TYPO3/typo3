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

namespace TYPO3\CMS\Core\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Controller\FileDumpController;
use TYPO3\CMS\Core\Crypto\HashAlgo;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\Event\ModifyFileDumpEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[BackupGlobals(true)]
final class FileDumpControllerTest extends UnitTestCase
{
    public static function invalidTokenDataProvider(): array
    {
        return [
            'missing token' => [null],
            'wrong token' => ['invalid'],
            'array token' => [['invalid']],
            'nested array token' => [[['invalid']]],
            'wrong integers only token' => [12345],
            'wrong token looking right' => ['12345 '],
            'valid string token, but not matching' => ['37d4752947d8a85f5d77ccc0107da573e1873b89b8bb5ddc0b62ac8240c5609e'],
        ];
    }

    #[DataProvider('invalidTokenDataProvider')]
    #[Test]
    public function dumpActionRespondsWithForbiddenForInvalidToken(mixed $token): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'encryption-key';
        $queryParams = ['eID' => 'dumpFile', 't' => 'f', 'f' => '1'];
        if ($token !== null) {
            $queryParams['token'] = $token;
        }
        $request = new ServerRequest('https://example.org/')->withQueryParams($queryParams);
        $subject = new FileDumpController(
            self::createStub(EventDispatcherInterface::class),
            self::createStub(ResourceFactory::class),
            new ResponseFactory(),
            new HashService(),
            self::createStub(FileNameValidator::class),
            self::createStub(ProcessedFileRepository::class),
        );

        self::assertSame(403, $subject->dumpAction($request)->getStatusCode());
    }

    #[Test]
    public function dumpActionPassesValidTokenAndDispatchesEventForRequestedFile(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'encryption-key';
        $hashService = new HashService();
        $token = $hashService->hmac('dumpFile|f|1', 'resourceStorageDumpFile', HashAlgo::SHA3_256);
        $request = new ServerRequest('https://example.org/')->withQueryParams(
            ['eID' => 'dumpFile', 't' => 'f', 'f' => '1', 'token' => $token]
        );

        $storage = self::createStub(ResourceStorage::class);
        $storage->method('getDriverType')->willReturn('Local');
        $file = self::createStub(File::class);
        $file->method('getStorage')->willReturn($storage);
        $file->method('getIdentifier')->willReturn('/file.txt');
        $file->method('getName')->willReturn('file.txt');
        $resourceFactory = $this->createMock(ResourceFactory::class);
        $resourceFactory->expects($this->once())->method('getFileObject')->with(1)->willReturn($file);
        $fileNameValidator = self::createStub(FileNameValidator::class);
        $fileNameValidator->method('isValid')->willReturn(true);

        $responseFactory = new ResponseFactory();
        $dispatchedEvent = null;
        $eventDispatcher = self::createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(
            static function (ModifyFileDumpEvent $event) use ($responseFactory, &$dispatchedEvent): ModifyFileDumpEvent {
                $dispatchedEvent = $event;
                $event->setResponse($responseFactory->createResponse(200));
                return $event;
            }
        );

        $subject = new FileDumpController(
            $eventDispatcher,
            $resourceFactory,
            $responseFactory,
            $hashService,
            $fileNameValidator,
            self::createStub(ProcessedFileRepository::class),
        );

        $response = $subject->dumpAction($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertInstanceOf(ModifyFileDumpEvent::class, $dispatchedEvent);
        self::assertSame($file, $dispatchedEvent->getFile());
        self::assertNotEmpty($response->getHeaderLine('content-security-policy'));
    }
}
