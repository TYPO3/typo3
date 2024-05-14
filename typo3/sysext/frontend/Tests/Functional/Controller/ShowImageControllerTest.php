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
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ShowImageController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ShowImageControllerTest extends FunctionalTestCase
{
    private const ENCRYPTION_KEY = '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6';
    private ResourceFactory&MockObject $resourceFactory;
    private ResourceStorage&MockObject $storage;
    private ShowImageController&MockObject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = self::ENCRYPTION_KEY;
        $this->resourceFactory = $this->getMockBuilder(ResourceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subject = $this->getMockBuilder(ShowImageController::class)
            ->onlyMethods(['processImage'])
            ->getMock();
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $this->resourceFactory);
    }

    protected function tearDown(): void
    {
        GeneralUtility::removeSingletonInstance(ResourceFactory::class, $this->resourceFactory);
        unset($this->resourceFactory, $this->storage, $this->subject);
        parent::tearDown();
    }

    public static function contentIsGeneratedForLocalFilesDataProvider(): \Generator
    {
        $fileId = 13;
        $parameters = [];
        $serializedParameters = base64_encode(serialize($parameters));
        $jsonEncodedParameters = base64_encode(json_encode($parameters));
        yield 'numeric fileId, json encoded' => [
            $fileId,
            [
                'file' => $fileId,
                'parameters' => [$jsonEncodedParameters],
                'md5' => hash_hmac('sha1', implode('|', [$fileId, $jsonEncodedParameters]), self::ENCRYPTION_KEY),
            ],
        ];
        yield 'numeric fileId, outdated (valid) PHP encoded' => [
            $fileId,
            [
                'file' => $fileId,
                'parameters' => [$serializedParameters],
                'md5' => hash_hmac('sha1', implode('|', [$fileId, $serializedParameters]), self::ENCRYPTION_KEY),
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

        $this->storage->expects(self::atLeastOnce())
            ->method('getDriverType')
            ->willReturn($storageDriver);
        $file = $this->buildFile('/local-file/' . $fileId, $this->storage);
        $processedFile = $this->buildProcessedFile($expectedSrc);
        $this->resourceFactory->expects(self::atLeastOnce())
            ->method('getFileObject')
            ->with($fileId)
            ->willReturn($file);
        $this->subject->expects(self::once())
            ->method('processImage')
            ->willReturn($processedFile);

        $request = $this->buildRequest($queryParams);
        $response = $this->subject->processRequest($request);
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

    /**
     * @param array<string, int|string> $queryParams
     */
    private function buildRequest(array $queryParams): ServerRequestInterface&MockObject
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')
            ->willReturn($queryParams);

        return $request;
    }

    private function buildFile(string $identifier, ResourceStorage $storage): FileInterface&MockObject
    {
        $file = $this->createMock(FileInterface::class);
        $file->method('getStorage')
            ->willReturn($storage);
        $file->method('getIdentifier')
            ->willReturn($identifier);
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
        $processedFile = $this->getMockBuilder(ProcessedFile::class)
            ->disableOriginalConstructor()
            ->getMock();
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
}
