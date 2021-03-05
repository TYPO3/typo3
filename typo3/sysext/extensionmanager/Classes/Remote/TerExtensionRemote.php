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

namespace TYPO3\CMS\Extensionmanager\Remote;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Domain\Repository\BulkExtensionRepositoryWriter;
use TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility;

/**
 * Class for downloading .t3x files from extensions.typo3.org and validating the results.
 * This also includes the ListableRemoteInterface, which means it downloads extensions.xml.gz files and can import
 * it in the database.
 *
 * This is the only dependency for the concrete TER implementation on extensions.typo3.org and
 * encapsulates the definition where an extension is located in TER.
 *
 * Not responsible for:
 * - installing / activating an extension
 */
class TerExtensionRemote implements ExtensionDownloaderRemoteInterface, ListableRemoteInterface
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $localExtensionListCacheFile;

    /**
     * @var string
     */
    protected $remoteBase = 'https://extensions.typo3.org/fileadmin/ter/';

    public function __construct(string $identifier, array $options = [])
    {
        $this->identifier = $identifier;
        $this->localExtensionListCacheFile = Environment::getVarPath() . '/extensionmanager/' . $this->identifier . '.extensions.xml.gz';

        if ($options['remoteBase'] ?? '') {
            $this->remoteBase = $options['remoteBase'];
        }
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Download the xml.gz file, and extract it into the database.
     *
     * @param bool $force
     */
    public function getAvailablePackages(bool $force = false): void
    {
        if ($force || $this->needsUpdate()) {
            $this->fetchPackageList();
        }
    }

    public function needsUpdate(): bool
    {
        $threshold = new \DateTimeImmutable('-7 days');
        if ($this->getLastUpdate() < $threshold) {
            return true;
        }
        return $this->isDownloadedExtensionListUpToDate() !== true;
    }

    /**
     * TER provides a extensions.md5 which contains the hashsum of the current remote extensions.gz file.
     * Let's check if this is the same, if so, it is not needed to download a new extensions.gz.
     * @return bool
     */
    protected function isDownloadedExtensionListUpToDate(): bool
    {
        if (!file_exists($this->localExtensionListCacheFile)) {
            return false;
        }
        try {
            $response = $this->downloadFile('extensions.md5');
            $md5SumOfRemoteExtensionListFile = $response->getBody()->getContents();
            return hash_equals($md5SumOfRemoteExtensionListFile, md5_file($this->localExtensionListCacheFile) ?: '');
        } catch (DownloadFailedException $exception) {
            return false;
        }
    }

    public function getLastUpdate(): \DateTimeInterface
    {
        if (file_exists($this->localExtensionListCacheFile) && filesize($this->localExtensionListCacheFile) > 0) {
            $mtime = filemtime($this->localExtensionListCacheFile);
            return new \DateTimeImmutable('@' . $mtime);
        }
        // Select a very old date (hint: easter egg)
        return new \DateTimeImmutable('1975-04-13');
    }

    /**
     * Downloads the extensions.xml.gz and imports it into the database.
     */
    protected function fetchPackageList(): void
    {
        try {
            $extensionListXml = $this->downloadFile('extensions.xml.gz');
            GeneralUtility::writeFileToTypo3tempDir($this->localExtensionListCacheFile, $extensionListXml->getBody()->getContents());
            GeneralUtility::makeInstance(BulkExtensionRepositoryWriter::class)->import($this->localExtensionListCacheFile, $this->identifier);
        } catch (DownloadFailedException $e) {
            // Do not update package list
        }
    }

    /**
     * Internal method
     * @param string $remotePath
     * @return ResponseInterface
     * @throws DownloadFailedException
     */
    protected function downloadFile(string $remotePath): ResponseInterface
    {
        try {
            $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
            return $requestFactory->request($this->remoteBase . $remotePath);
        } catch (\Throwable $e) {
            throw new DownloadFailedException(sprintf('The file "%s" could not be fetched. Possible reasons: network problems, allow_url_fopen is off, cURL is not available', $this->remoteBase . $remotePath), 1334426297);
        }
    }

    /**
     * Downloads a single extension, and extracts the t3x file into a target location folder.
     *
     * @param string $extensionKey
     * @param string $version
     * @param FileHandlingUtility $fileHandler
     * @param string|null $verificationHash
     * @param string $pathType
     * @throws DownloadFailedException
     * @throws VerificationFailedException
     */
    public function downloadExtension(string $extensionKey, string $version, FileHandlingUtility $fileHandler, string $verificationHash = null, string $pathType = 'Local'): void
    {
        $extensionPath = strtolower($extensionKey);
        $remotePath = $extensionPath[0] . '/' . $extensionPath[1] . '/' . $extensionPath . '_' . $version . '.t3x';
        try {
            $downloadedContent = (string)$this->downloadFile($remotePath)->getBody()->getContents();
        } catch (\Throwable $e) {
            throw new DownloadFailedException(sprintf('The T3X file "%s" could not be fetched. Possible reasons: network problems, allow_url_fopen is off, cURL is not available.', $this->remoteBase . $remotePath), 1334426097);
        }
        if ($verificationHash && !$this->isDownloadedPackageValid($verificationHash, $downloadedContent)) {
            throw new VerificationFailedException('MD5 hash of downloaded file not as expected: ' . md5($downloadedContent) . ' != ' . $verificationHash, 1334426098);
        }
        $extensionData = $this->decodeExchangeData($downloadedContent);
        if (!empty($extensionData['extKey']) && is_string($extensionData['extKey'])) {
            $fileHandler->unpackExtensionFromExtensionDataArray($extensionData['extKey'], $extensionData, $pathType);
        } else {
            throw new VerificationFailedException('Downloaded t3x file could not be extracted', 1334426698);
        }
    }

    /**
     * Validates the integrity of the contents of a downloaded file.
     *
     * @param string $expectedHash
     * @param string $fileContents
     * @return bool
     */
    protected function isDownloadedPackageValid(string $expectedHash, string $fileContents): bool
    {
        return hash_equals($expectedHash, md5($fileContents));
    }

    /**
     * Decodes extension array from t3x file contents.
     * This kind of data is when an extension is uploaded to TER
     *
     * @param string $stream Data stream
     * @throws VerificationFailedException
     * @return array Array with result on success, otherwise an error string.
     */
    protected function decodeExchangeData(string $stream): array
    {
        [$expectedHash, $compressionType, $contents] = explode(':', $stream, 3);
        if ($compressionType === 'gzcompress') {
            if (function_exists('gzuncompress')) {
                $contents = gzuncompress($contents) ?: '';
            } else {
                throw new VerificationFailedException('No decompressor available for compressed content. gzcompress()/gzuncompress() functions are not available', 1601370681);
            }
        }
        if ($this->isDownloadedPackageValid($expectedHash, $contents)) {
            $output = unserialize($contents, ['allowed_classes' => false]);
            if (!is_array($output)) {
                throw new VerificationFailedException('Content could not be unserialized to an array. Strange (since MD5 hashes match!)', 1601370682);
            }
        } else {
            throw new VerificationFailedException('MD5 mismatch. Maybe the extension file was downloaded and saved as a text file by the browser and thereby corrupted.', 1601370683);
        }
        return $output;
    }
}
