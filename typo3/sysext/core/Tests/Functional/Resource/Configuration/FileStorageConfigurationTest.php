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

namespace TYPO3\CMS\Core\Tests\Functional\Resource\Configuration;

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Configuration\FileStorageConfiguration;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileStorageConfigurationException;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileStorageConfigurationTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    private string $configFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configFile = Environment::getConfigPath() . '/system/' . FileStorageConfiguration::CONFIG_FILE_NAME;
    }

    protected function tearDown(): void
    {
        @unlink($this->configFile);
        putenv('TYPO3_TEST_STORAGE_PATH');
        parent::tearDown();
    }

    #[Test]
    public function fileIsReadAndEntriesAreNormalized(): void
    {
        file_put_contents($this->configFile, <<<YAML
storages:
  2:
    name: 'Assets (S3)'
    driver: AmazonS3
    public: false
    processingFolder: '1:/processed/assets/'
    configuration:
      bucket: assets
  1:
    name: fileadmin
    default: true
    configuration:
      basePath: public/fileadmin/
YAML);
        $subject = $this->get(FileStorageConfiguration::class);
        self::assertTrue($subject->isFilePresent());
        self::assertSame(
            [
                1 => [
                    'identifier' => 1,
                    'name' => 'fileadmin',
                    'driver' => 'Local',
                    'default' => true,
                    'browsable' => true,
                    'public' => true,
                    'writable' => true,
                    'online' => true,
                    'autoExtractMetadata' => true,
                    'processingFolder' => '',
                    'allowedFileExtensions' => [],
                    'configuration' => ['basePath' => 'public/fileadmin/'],
                ],
                2 => [
                    'identifier' => 2,
                    'name' => 'Assets (S3)',
                    'driver' => 'AmazonS3',
                    'default' => false,
                    'browsable' => true,
                    'public' => false,
                    'writable' => true,
                    'online' => true,
                    'autoExtractMetadata' => true,
                    'processingFolder' => '1:/processed/assets/',
                    'allowedFileExtensions' => [],
                    'configuration' => ['bucket' => 'assets'],
                ],
            ],
            $subject->getAll()
        );
    }

    #[Test]
    public function emptyFileYieldsImplicitDefault(): void
    {
        file_put_contents($this->configFile, '');
        $subject = $this->get(FileStorageConfiguration::class);
        self::assertSame($subject->getImplicitDefault(), $subject->getAll());
    }

    #[Test]
    public function emptyStoragesMapYieldsImplicitDefault(): void
    {
        file_put_contents($this->configFile, "storages: []\n");
        $subject = $this->get(FileStorageConfiguration::class);
        self::assertSame($subject->getImplicitDefault(), $subject->getAll());
    }

    #[Test]
    public function singleStorageBecomesDefault(): void
    {
        file_put_contents($this->configFile, "storages:\n  5:\n    name: files\n");
        self::assertTrue($this->get(FileStorageConfiguration::class)->get(5)['default']);
    }

    #[Test]
    public function environmentPlaceholdersAreResolvedButKeptInRawContent(): void
    {
        putenv('TYPO3_TEST_STORAGE_PATH=media/');
        file_put_contents($this->configFile, "storages:\n  1:\n    configuration:\n      basePath: '%env(TYPO3_TEST_STORAGE_PATH)%'\n");
        $subject = $this->get(FileStorageConfiguration::class);
        self::assertSame('media/', $subject->get(1)['configuration']['basePath']);
        self::assertSame('%env(TYPO3_TEST_STORAGE_PATH)%', $subject->loadRaw()['storages'][1]['configuration']['basePath']);
    }

    #[Test]
    public function nonNumericIdentifierThrows(): void
    {
        file_put_contents($this->configFile, "storages:\n  assets:\n    name: assets\n");
        $this->expectException(InvalidFileStorageConfigurationException::class);
        $this->expectExceptionCode(1789862410);
        $this->get(FileStorageConfiguration::class)->getAll();
    }

    #[Test]
    public function storagesKeyMustBeMap(): void
    {
        file_put_contents($this->configFile, "storages: fileadmin\n");
        $this->expectException(InvalidFileStorageConfigurationException::class);
        $this->expectExceptionCode(1789862401);
        $this->get(FileStorageConfiguration::class)->getAll();
    }

    #[Test]
    public function flushRereadsTheFile(): void
    {
        $subject = $this->get(FileStorageConfiguration::class);
        self::assertSame('fileadmin', $subject->get(1)['name']);
        file_put_contents($this->configFile, "storages:\n  1:\n    name: files\n");
        self::assertSame('fileadmin', $subject->get(1)['name']);
        $subject->flush();
        self::assertSame('files', $subject->get(1)['name']);
    }

    #[Test]
    public function unknownOptionsAreIgnoredWhenReading(): void
    {
        file_put_contents($this->configFile, "storages:\n  1:\n    name: files\n    optionOfANewerVersion: true\n");
        $storage = $this->get(FileStorageConfiguration::class)->get(1);
        self::assertSame('files', $storage['name']);
        self::assertArrayNotHasKey('optionOfANewerVersion', $storage);
    }

    #[Test]
    public function invalidEntryBecomesAnOfflineStorage(): void
    {
        file_put_contents($this->configFile, "storages:\n  1:\n    name: files\n    default: true\n  2:\n    name: broken\n    public: maybe\n");
        $storages = $this->get(FileStorageConfiguration::class)->getAll();
        self::assertTrue($storages[1]['online']);
        self::assertSame('broken', $storages[2]['name']);
        self::assertFalse($storages[2]['online']);
        self::assertFalse($storages[2]['writable']);
    }

    #[Test]
    public function storageWithUnresolvedEnvironmentVariableIsOffline(): void
    {
        file_put_contents($this->configFile, "storages:\n  1:\n    name: files\n    default: true\n  2:\n    name: remote\n    configuration:\n      basePath: '%env(TYPO3_TEST_STORAGE_PATH)%'\n");
        $storages = $this->get(FileStorageConfiguration::class)->getAll();
        self::assertTrue($storages[1]['online']);
        self::assertFalse($storages[2]['online']);
        self::assertSame([], $storages[2]['configuration']);
    }

    #[Test]
    public function firstOfSeveralDefaultStoragesIsUsedWhenReading(): void
    {
        file_put_contents($this->configFile, "storages:\n  1:\n    default: true\n  2:\n    default: true\n  3:\n    name: three\n");
        $storages = $this->get(FileStorageConfiguration::class)->getAll();
        self::assertTrue($storages[1]['default']);
        self::assertFalse($storages[2]['default']);
        self::assertFalse($storages[3]['default']);
    }

    #[Test]
    public function firstStorageIsDefaultWhenNoneIsMarkedWhenReading(): void
    {
        file_put_contents($this->configFile, "storages:\n  2:\n    name: two\n  3:\n    name: three\n");
        $storages = $this->get(FileStorageConfiguration::class)->getAll();
        self::assertTrue($storages[2]['default']);
        self::assertFalse($storages[3]['default']);
    }

    #[Test]
    public function strictNormalizationRejectsSeveralDefaults(): void
    {
        $this->expectException(InvalidFileStorageConfigurationException::class);
        $this->expectExceptionCode(1789862408);
        $this->get(FileStorageConfiguration::class)->normalizeAll([1 => ['default' => true], 2 => ['default' => true]]);
    }

    #[Test]
    public function parsedFileIsReusedFromCoreCacheByNextRequest(): void
    {
        file_put_contents($this->configFile, "storages:\n  3:\n    name: three\n    configuration:\n      basePath: fileadmin/\n");
        $expected = $this->get(FileStorageConfiguration::class)->getAll();

        $yamlFileLoader = $this->createMock(YamlFileLoader::class);
        $yamlFileLoader->expects($this->never())->method('load');
        $nextRequestSubject = $this->createSubjectForNextRequest($yamlFileLoader);

        self::assertSame($expected, $nextRequestSubject->getAll());
    }

    #[Test]
    public function changedFileIsReadByNextRequestWithoutFlushingCaches(): void
    {
        file_put_contents($this->configFile, "storages:\n  3:\n    name: three\n");
        $this->get(FileStorageConfiguration::class)->getAll();
        file_put_contents($this->configFile, "storages:\n  4:\n    name: four\n");

        $nextRequestSubject = $this->createSubjectForNextRequest($this->get(YamlFileLoader::class));

        self::assertSame([4], array_keys($nextRequestSubject->getAll()));
    }

    private function createSubjectForNextRequest(YamlFileLoader $yamlFileLoader): FileStorageConfiguration
    {
        $cache = $this->get(CacheManager::class)->getCache('core');
        self::assertInstanceOf(PhpFrontend::class, $cache);
        return new FileStorageConfiguration(Environment::getConfigPath(), $yamlFileLoader, new NullLogger(), $cache);
    }
}
