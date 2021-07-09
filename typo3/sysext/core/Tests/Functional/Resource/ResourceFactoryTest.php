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

namespace TYPO3\CMS\Core\Tests\Functional\Resource;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ResourceFactoryTest extends FunctionalTestCase
{
    /**
     * @var ResourceFactory
     */
    private $subject;

    /**
     * @var StorageRepository
     */
    private $storageRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backendUser = $this->setUpBackendUserFromFixture(1);
        $this->subject = GeneralUtility::makeInstance(ResourceFactory::class);
        $this->storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
    }

    protected function tearDown(): void
    {
        unset($this->storageRepository, $this->subject);
        parent::tearDown();
    }

    public function bestStorageIsResolvedDataProvider(): array
    {
        // `{public}` will be replaced by public project path (not having trailing slash)
        // double slashes `//` are used on purpose for given file identifiers
        return $this->mapToDataSet([
            // legacy storage
            '/favicon.ico' => '0:/favicon.ico',
            'favicon.ico' => '0:/favicon.ico',

            '{public}//favicon.ico' => '0:/favicon.ico',
            '{public}/favicon.ico' => '0:/favicon.ico',

            // using storages with relative path
            '/fileadmin/img.png' => '1:/img.png',
            'fileadmin/img.png' => '1:/img.png',
            '/fileadmin/images/img.png' => '1:/images/img.png',
            'fileadmin/images/img.png' => '1:/images/img.png',
            '/documents/doc.pdf' => '2:/doc.pdf',
            'documents/doc.pdf' => '2:/doc.pdf',
            '/fileadmin/nested/images/img.png' => '3:/images/img.png',
            'fileadmin/nested/images/img.png' => '3:/images/img.png',

            '{public}//fileadmin/img.png' => '1:/img.png',
            '{public}/fileadmin/img.png' => '1:/img.png',
            '{public}//fileadmin/images/img.png' => '1:/images/img.png',
            '{public}/fileadmin/images/img.png' => '1:/images/img.png',
            '{public}//documents/doc.pdf' => '2:/doc.pdf',
            '{public}/documents/doc.pdf' => '2:/doc.pdf',
            '{public}//fileadmin/nested/images/img.png' => '3:/images/img.png',
            '{public}/fileadmin/nested/images/img.png' => '3:/images/img.png',

            // using storages with absolute path
            '/files/img.png' => '4:/img.png',
            'files/img.png' => '4:/img.png',
            '/files/images/img.png' => '4:/images/img.png',
            'files/images/img.png' => '4:/images/img.png',
            '/docs/doc.pdf' => '5:/doc.pdf',
            'docs/doc.pdf' => '5:/doc.pdf',
            '/files/nested/images/img.png' => '6:/images/img.png',
            'files/nested/images/img.png' => '6:/images/img.png',

            '{public}//files/img.png' => '4:/img.png',
            '{public}/files/img.png' => '4:/img.png',
            '{public}//files/images/img.png' => '4:/images/img.png',
            '{public}/files/images/img.png' => '4:/images/img.png',
            '{public}//docs/doc.pdf' => '5:/doc.pdf',
            '{public}/docs/doc.pdf' => '5:/doc.pdf',
            '{public}//files/nested/images/img.png' => '6:/images/img.png',
            '{public}/files/nested/images/img.png' => '6:/images/img.png',
        ]);
    }

    /**
     * @param string $sourceIdentifier
     * @param string $expectedCombinedIdentifier
     * @test
     * @dataProvider bestStorageIsResolvedDataProvider
     */
    public function bestStorageIsResolved(string $sourceIdentifier, string $expectedCombinedIdentifier): void
    {
        $this->createLocalStorages();
        $sourceIdentifier = str_replace('{public}', Environment::getPublicPath(), $sourceIdentifier);
        $storage = $this->subject->getStorageObject(0, [], $sourceIdentifier);
        $combinedIdentifier = sprintf('%d:%s', $storage->getUid(), $sourceIdentifier);
        self::assertSame(
            $expectedCombinedIdentifier,
            $combinedIdentifier,
            sprintf('Given identifier "%s"', $sourceIdentifier)
        );
    }

    private function createLocalStorages(): void
    {
        $publicPath = Environment::getPublicPath();
        $prefixDelegate = function (string $value) use ($publicPath): string {
            return $publicPath . '/' . $value;
        };
        // array indexes are not relevant here, but are those expected to be used as storage UID (`1:/file.png`)
        // @todo it is possible to create ambiguous storages, e.g. `fileadmin/` AND `/fileadmin/`
        $relativeNames = [1 => 'fileadmin/', 2 => 'documents/', 3 => 'fileadmin/nested/'];
        $absoluteNames = array_map($prefixDelegate, [4 => 'files/', 5 => 'docs/', 6 => 'files/nested']);
        foreach ($relativeNames as $relativeName) {
            $this->storageRepository->createLocalStorage('rel:' . $relativeName, $relativeName, 'relative');
        }
        foreach ($absoluteNames as $absoluteName) {
            $this->storageRepository->createLocalStorage('abs:' . $absoluteName, $absoluteName, 'absolute');
        }
        // path is outside public project path - which is expected to cause problems (that's why it's tested)
        $outsideName = dirname($publicPath) . '/outside/';
        $this->storageRepository->createLocalStorage('abs:' . $outsideName, $outsideName, 'absolute');
    }

    /**
     * @param array<string, string> $map
     * @return array<string, string[]>
     */
    private function mapToDataSet(array $map): array
    {
        array_walk($map, function (&$item, string $key) {
            $item = [$key, $item];
        });
        return $map;
    }
}
