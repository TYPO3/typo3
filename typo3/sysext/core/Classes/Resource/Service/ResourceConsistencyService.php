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

namespace TYPO3\CMS\Core\Resource\Service;

use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Localization\LabelBag;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\MimeTypeDetector;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Validation\ResultException;
use TYPO3\CMS\Core\Validation\ResultMessage;

/**
 * This service is invoked by ResourceStorage when modifying files, validating the following:
 * + only explicitly allowed file-extensions are allowed:
 *   see `TYPO3_CONF_VARS` settings for `textfile_ext`, `mediafile_ext` and `miscfile_ext`
 * + only files having valid file-extension to mime-type items are allowed:
 *   e.g. denies using `image.exe` with `image/png`
 *
 * @phpstan-type ExceptionItem array{storage: ResourceStorage, resource: string|FileInterface, targetFileName: string}
 * @phpstan-type ExceptionItemCollection array<string, ExceptionItem>
 * @internal
 */
final class ResourceConsistencyService
{
    /**
     * Exception items, which shall not be validated.
     * These are usually set by internal components (e.g. `ext:impexp`).
     *
     * @var ExceptionItemCollection
     */
    private array $exceptionItems = [];

    public function __construct(
        private readonly Random $random,
        private readonly Features $features,
        private readonly MimeTypeDetector $mimeTypeDetector,
    ) {}

    public function addExceptionItem(ResourceStorage $storage, string|FileInterface $resource, string $targetFileName): void
    {
        $identifier = $this->random->generateRandomHexString(40);
        $this->exceptionItems[$identifier] = $this->createExceptionItem($storage, $resource, $targetFileName);
    }

    public function removeException(string $identifier): void
    {
        unset($this->exceptionItems[$identifier]);
    }

    /**
     * @param FileInterface|string $resource holding the contents
     * @param string $targetFileName (optional) target file name to be used as the identifier
     * @throws ResultException
     */
    public function validate(ResourceStorage $storage, string|FileInterface $resource, string $targetFileName = ''): void
    {
        if (!$this->shallValidate($storage, $resource, $targetFileName)) {
            return;
        }
        if ($targetFileName !== '') {
            $fileExtension = pathinfo($targetFileName, PATHINFO_EXTENSION);
        }
        if ($resource instanceof FileInterface) {
            $mimeType = $resource->getMimeType();
            $fileSize = $resource->getSize();
            $fileExtension ??= $resource->getExtension();
        } else {
            $fileInfo = new FileInfo($resource);
            $mimeType = (string)$fileInfo->getMimeType($targetFileName);
            $fileSize = $fileInfo->isReadable() ? $fileInfo->getSize() : 0;
            $fileExtension ??= $fileInfo->getExtension();
        }
        // `AbstractFile::getSize()` returns `null` instead of `0`
        $isEmptyFile = empty($fileSize);
        $messages = [];
        // skip mime-type checks for empty files
        if (!$isEmptyFile && !$this->areFileExtensionAndMimeTypeConsistent($fileExtension, $mimeType)) {
            $arguments = [$mimeType, $fileExtension];
            $messages[] = new ResultMessage(
                sprintf('Mime-type "%s" not allowed for file extension "%s"', ...$arguments),
                new LabelBag(
                    'LLL:EXT:core/Resources/Private/Language/fileMessages.xlf:FileUtility.MimeTypeNotAllowedForFileExtension',
                    ...$arguments
                )
            );
        }
        if (!$this->isFileExtensionAllowed($fileExtension)) {
            $arguments = [$fileExtension];
            $messages[] = new ResultMessage(
                sprintf('File extension "%s" is not in the list of allowed values', ...$arguments),
                new LabelBag(
                    'LLL:EXT:core/Resources/Private/Language/fileMessages.xlf:FileUtility.FileExtensionIsNotAllowed',
                    ...$arguments
                )
            );
        }
        if ($messages !== []) {
            throw new ResultException('Resource consistency check failed', 1747230949, ...$messages);
        }
    }

    private function areFileExtensionAndMimeTypeConsistent(string $fileExtension, string $mimeType): bool
    {
        if (!$this->features->isFeatureEnabled('security.system.enforceFileExtensionMimeTypeConsistency')) {
            return true;
        }
        $fileExtension = mb_strtolower($fileExtension);
        $assumedMimesTypeOfFileExtension = $this->mimeTypeDetector->getMimeTypesForFileExtension($fileExtension);
        // pass, in case no assumed mime-type was found (e.g., for individual file extension)
        return $assumedMimesTypeOfFileExtension === []
            || ($mimeType !== '' && in_array($mimeType, $assumedMimesTypeOfFileExtension, true));
    }

    private function isFileExtensionAllowed(string $fileExtension): bool
    {
        if (!$this->features->isFeatureEnabled('security.system.enforceAllowedFileExtensions')) {
            return true;
        }
        $fileExtension = mb_strtolower($fileExtension);
        return in_array($fileExtension, $this->getAllowedFileExtensions(), true);
    }

    private function getAllowedFileExtensions(): array
    {
        $allowedFileExtensions = GeneralUtility::trimExplode(
            ',',
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'] . ','
            . $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'] . ','
            . $GLOBALS['TYPO3_CONF_VARS']['SYS']['miscfile_ext'],
            true
        );
        return array_map(mb_strtolower(...), $allowedFileExtensions);
    }

    private function shallValidate(ResourceStorage $storage, string|FileInterface $resource, string $targetFileName): bool
    {
        $needle = $this->createExceptionItem($storage, $resource, $targetFileName);
        $exceptionItems = array_filter(
            $this->exceptionItems,
            fn(array $exception): bool => $this->exceptionItemsMatch($exception, $needle),
        );
        if ($exceptionItems === []) {
            return true;
        }
        foreach (array_keys($exceptionItems) as $identifier) {
            $this->removeException($identifier);
        }
        return false;
    }

    /**
     * @return ExceptionItem
     */
    private function createExceptionItem(ResourceStorage $storage, string|FileInterface $resource, string $targetFileName): array
    {
        return [
            'storage' => $storage,
            'resource' => $resource,
            'targetFileName' => $targetFileName,
        ];
    }

    private function exceptionItemsMatch(array $left, array $right): bool
    {
        foreach ($right as $key => $value) {
            if ($value !== ($left[$key] ?? null)) {
                return false;
            }
        }
        return true;
    }
}
