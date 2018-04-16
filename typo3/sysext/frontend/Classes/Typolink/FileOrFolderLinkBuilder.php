<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Typolink;

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
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Frontend\Http\UrlProcessorInterface;

/**
 * Builds a TypoLink to a folder or file
 */
class FileOrFolderLinkBuilder extends AbstractTypolinkBuilder
{
    /**
     * @inheritdoc
     */
    public function build(array &$linkDetails, string $linkText, string $target, array $conf): array
    {
        $fileOrFolderObject = $linkDetails['file'] ? $linkDetails['file'] : $linkDetails['folder'];
        // check if the file exists or if a / is contained (same check as in detectLinkType)
        if (!($fileOrFolderObject instanceof FileInterface) && !($fileOrFolderObject instanceof Folder)) {
            throw new UnableToLinkException(
                'File "' . $linkDetails['typoLinkParameter'] . '" did not exist, so "' . $linkText . '" was not linked.',
                1490989449,
                null,
                $linkText
            );
        }

        $tsfe = $this->getTypoScriptFrontendController();
        $linkLocation = $fileOrFolderObject->getPublicUrl();
        if ($linkLocation === null) {
            // set the linkLocation to an empty string if null,
            // so it does not collide with the various string functions
            $linkLocation = '';
        }
        // Setting title if blank value to link
        $linkText = $this->parseFallbackLinkTextIfLinkTextIsEmpty($linkText, rawurldecode($linkLocation));
        if (strpos($linkLocation, '/') !== 0
            && parse_url($linkLocation, PHP_URL_SCHEME) === null
        ) {
            $linkLocation = $tsfe->absRefPrefix . $linkLocation;
        }
        $url = $this->processUrl(UrlProcessorInterface::CONTEXT_FILE, $linkLocation, $conf);
        return [
            $this->forceAbsoluteUrl($url, $conf),
            $linkText,
            $target ?: $this->resolveTargetAttribute($conf, 'fileTarget', false, $tsfe->fileTarget)
        ];
    }
}
