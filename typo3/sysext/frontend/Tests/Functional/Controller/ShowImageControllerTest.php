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

namespace TYPO3\CMS\Frontend\Tests\Functional\Controller;

use Masterminds\HTML5;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ShowImageController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ShowImageControllerTest extends FunctionalTestCase
{
    private function buildFile(string $identifier, ResourceStorage $storage): FileInterface&MockObject
    {
        $file = $this->createMock(FileInterface::class);
        $file->method('getStorage')->willReturn($storage);
        $file->method('getIdentifier')->willReturn($identifier);
        $file->method('getProperty')
            ->willReturnCallback(
                $this->buildRoundTripClosure(
                    'fileProperty',
                    ['title' => '</title></head></html>']
                )
            );
        return $file;
    }

    private function buildProcessedFile(string $publicUrl): ProcessedFile&MockObject
    {
        $processedFile = $this->createMock(ProcessedFile::class);
        $processedFile
            ->method('getPublicUrl')
            ->willReturn($publicUrl);
        $processedFile
            ->method('getProperty')
            ->with(self::isType('string'))
            ->willReturnCallback($this->buildRoundTripClosure('processedProperty'));

        return $processedFile;
    }

    private function buildRoundTripClosure(string $prefix, array $prependMap = []): \Closure
    {
        return static function (string $name) use ($prefix, $prependMap): string {
            return sprintf(
                '%s<!-- "%s::%s" -->',
                $prependMap[$name] ?? '',
                $prefix,
                $name
            );
        };
    }

    public static function contentIsGeneratedForLocalFilesDataProvider(): \Generator
    {
        yield 'numeric fileId, json encoded' => [
            13,
            [
                'file' => 13,
                'parameters' => [json_encode([])],
            ],
        ];
        yield 'numeric fileId, outdated (valid) PHP encoded' => [
            13,
            [
                'file' => 13,
                'parameters' => [serialize([])],
            ],
        ];
    }

    /**
     * @param array<string, int|string> $queryParams
     */
    #[DataProvider('contentIsGeneratedForLocalFilesDataProvider')]
    #[Test]
    public function contentIsGeneratedForLocalFiles(int $fileId, array $queryParams): void
    {
        $storageDriver = 'Local';
        $expectedSrc = '/fileadmin/local-file/' . $fileId . '?&test=""';
        $expectedTitle = '</title></head></html><!-- "fileProperty::title" -->';

        $storage = $this->createMock(ResourceStorage::class);
        $storage->expects(self::atLeastOnce())
            ->method('getDriverType')
            ->willReturn($storageDriver);
        $file = $this->buildFile('/local-file/' . $fileId, $storage);
        $processedFile = $this->buildProcessedFile($expectedSrc);
        $resourceFactory = $this->createMock(ResourceFactory::class);
        $resourceFactory->expects(self::atLeastOnce())
            ->method('getFileObject')
            ->with($fileId)
            ->willReturn($file);
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory);
        $subject = $this->getMockBuilder(ShowImageController::class)
            ->setConstructorArgs([new Features()])
            ->onlyMethods(['processImage'])
            ->getMock();
        $subject->expects(self::once())
            ->method('processImage')
            ->willReturn($processedFile);

        $hashService = $this->get(HashService::class);
        $queryParams['md5'] = $hashService->hmac(implode('|', [$fileId, $queryParams['parameters'][0]]), 'tx_cms_showpic');
        $request = new ServerRequest();
        $request = $request->withQueryParams($queryParams);

        $response = $subject->processRequest($request);
        $responseBody = (string)$response->getBody();
        $document = (new HTML5())->loadHTML($responseBody);

        $titles = $document->getElementsByTagName('title');
        $images = $document->getElementsByTagName('img');
        self::assertSame($expectedTitle, $titles->item(0)->nodeValue);
        self::assertSame($expectedSrc, $images->item(0)->getAttribute('src'));
        self::assertSame($expectedTitle, $images->item(0)->getAttribute('title'));
        self::assertSame('<!-- "fileProperty::alternative" -->', $images->item(0)->getAttribute('alt'));
        self::assertSame('<!-- "processedProperty::width" -->', $images->item(0)->getAttribute('width'));
        self::assertSame('<!-- "processedProperty::height" -->', $images->item(0)->getAttribute('height'));
    }
}
