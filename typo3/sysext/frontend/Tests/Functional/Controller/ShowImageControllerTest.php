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
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ShowImageControllerTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        $this->pathsToProvideInTestInstance = [
            ...$this->pathsToProvideInTestInstance,
            'typo3/sysext/frontend/Tests/Functional/Fixtures/Images/' => 'fileadmin/',
        ];
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/fileadmin_images.csv');
    }

    public static function contentIsGeneratedForLocalFilesDataProvider(): \Generator
    {
        yield 'numeric fileId, json encoded' => [
            'fileId' => 1,
            'baseUrl' => 'https://website.local/?eID=tx_cms_showpic',
            'queryParams' => [
                'file' => 1,
                'parameters' => [json_encode([])],
            ],
            'expectedImageTag' => '<img src="fileadmin/kasper-skarhoj1.jpg" alt="alternative 1" title="Kasper Skarhoj" width="401" height="600">',
            'expectedSource' => 'fileadmin/kasper-skarhoj1.jpg',
            'expectedTitle' => 'Kasper Skarhoj',
            'expectedAlternative' => 'alternative 1',
            'expectedWidth' => '401',
            'expectedHeight' => '600',
        ];
        yield 'numeric fileId, outdated (valid) PHP encoded' => [
            'fileId' => 2,
            'baseUrl' => 'https://website.local/?eID=tx_cms_showpic',
            'queryParams' => [
                'file' => 2,
                'parameters' => [serialize([])],
            ],
            'expectedImageTag' => '<img src="fileadmin/team-t3board10.jpg" alt="alternative 2" title="Team T3Board" width="1024" height="683">',
            'expectedSource' => 'fileadmin/team-t3board10.jpg',
            'expectedTitle' => 'Team T3Board',
            'expectedAlternative' => 'alternative 2',
            'expectedWidth' => '1024',
            'expectedHeight' => '683',
        ];
    }

    /**
     * @param array<string, int|string> $queryParams
     */
    #[DataProvider('contentIsGeneratedForLocalFilesDataProvider')]
    #[Test]
    public function contentIsGeneratedForLocalFiles(
        int $fileId,
        string $baseUrl,
        array $queryParams,
        string $expectedImageTag,
        string $expectedSource,
        string $expectedTitle,
        string $expectedAlternative,
        string $expectedWidth,
        string $expectedHeight,
    ): void {
        $uri = new Uri($baseUrl);
        $hashService = $this->get(HashService::class);
        $queryParams['md5'] = $hashService->hmac(implode('|', [$fileId, $queryParams['parameters'][0]]), 'tx_cms_showpic');
        $uri = $uri->withQuery($uri->getQuery() . '&' . http_build_query($queryParams));

        $request = new InternalRequest((string)$uri);
        $response = $this->executeFrontendSubRequest($request);
        self::assertSame(200, $response->getStatusCode());
        $content = (string)$response->getBody();
        self::assertNotEmpty($content);

        $document = (new HTML5())->loadHTML($content);
        $titles = $document->getElementsByTagName('title');
        $images = $document->getElementsByTagName('img');
        self::assertSame($expectedTitle, $titles->item(0)->nodeValue);
        self::assertSame($expectedSource, $images->item(0)->getAttribute('src'));
        self::assertSame($expectedTitle, $images->item(0)->getAttribute('title'));
        self::assertSame($expectedAlternative, $images->item(0)->getAttribute('alt'));
        self::assertSame($expectedWidth, $images->item(0)->getAttribute('width'));
        self::assertSame($expectedHeight, $images->item(0)->getAttribute('height'));
        self::assertStringContainsString($expectedImageTag, $content);
    }

    #[Test]
    public function missingFileUidReturns410ResponseHttpStatusCode(): void
    {
        $uri = new Uri('https://website.local/?eID=tx_cms_showpic');
        $queryParams = [
            'parameters' => [json_encode([])],
        ];
        $expectedHttpStatusCode = 410;
        $hashService = $this->get(HashService::class);
        $fileId = null;
        $queryParams['md5'] = $hashService->hmac(implode('|', [$fileId, $queryParams['parameters'][0]]), 'tx_cms_showpic');
        $uri = $uri->withQuery($uri->getQuery() . '&' . http_build_query($queryParams));

        $request = new InternalRequest((string)$uri);
        $response = $this->executeFrontendSubRequest($request);
        self::assertSame($expectedHttpStatusCode, $response->getStatusCode());
    }

    #[Test]
    public function invalidHmacValueReturns410ResponseHttpStatusCode(): void
    {
        $uri = new Uri('https://website.local/?eID=tx_cms_showpic');
        $queryParams = [
            'file' => 1,
            'parameters' => [json_encode([])],
        ];
        $expectedHttpStatusCode = 410;
        $queryParams['md5'] = 'invalid-md5';
        $uri = $uri->withQuery($uri->getQuery() . '&' . http_build_query($queryParams));

        $request = new InternalRequest((string)$uri);
        $response = $this->executeFrontendSubRequest($request);
        self::assertSame($expectedHttpStatusCode, $response->getStatusCode());
    }
}
