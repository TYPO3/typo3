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

namespace TYPO3\CMS\Core\Resource;

/**
 * Map text/plain to concrete mime types, based on their
 * supplied file extension.
 * This mapping is only allowed for text/plain
 *
 * @internal
 */
final readonly class MimeTypeCompatibilityTypeGuesser
{
    private array $mimeTypeCompatibility;

    public function __construct()
    {
        $this->mimeTypeCompatibility = $this->buildMimeTypeCompatibilityList();
    }

    public function guessMimeType(array &$parameters, \SplFileInfo $fileInfo): void
    {
        $mimeType = $parameters['mimeType'];
        $map = $this->mimeTypeCompatibility[$mimeType] ?? null;
        if (!is_array($map)) {
            return;
        }

        $fileName = $parameters['targetFileName'] ?? $fileInfo->getFilename();
        $extension = $this->getFileExtension($fileName);
        if (isset($map[$extension])) {
            $parameters['mimeType'] = $map[$extension];
        }
    }

    public function getMimeTypeCompatibilityList(): array
    {
        return $this->mimeTypeCompatibility;
    }

    private function buildMimeTypeCompatibilityList(): array
    {
        $mimeTypeCompatibility = [];

        foreach ((new MimeTypeCollection())->getMap() as $mimeType => $extensions) {
            if (str_ends_with($mimeType, '+xml')) {
                foreach ($extensions as $extension) {
                    $mimeTypeCompatibility['text/xml'][$extension] = $mimeType;
                }
            } elseif (str_ends_with($mimeType, '+json')) {
                foreach ($extensions as $extension) {
                    // Some PHP variants can detect application/json, some detect text/plain
                    $mimeTypeCompatibility['application/json'][$extension] = $mimeType;
                    $mimeTypeCompatibility['text/plain'][$extension] = $mimeType;
                }
            } elseif (
                str_ends_with($mimeType, '+yaml') ||
                (str_starts_with($mimeType, 'text/') && $mimeType !== 'text/plain')
            ) {
                foreach ($extensions as $extension) {
                    $mimeTypeCompatibility['text/plain'][$extension] = $mimeType;
                }
            }
        }

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['mimeTypeCompatibility'] ?? [] as $mimeType => $map) {
            foreach ($map as $extension => $newMimeType) {
                $mimeTypeCompatibility[$mimeType][$extension] = $newMimeType;
            }
        }

        return $mimeTypeCompatibility;
    }

    private function getFileExtension(string $filename): string
    {
        $pos = strrpos($filename, '.');
        return $pos === false ? '' : mb_strtolower(substr($filename, $pos + 1));
    }
}
