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

namespace TYPO3\CMS\Fluid\ViewHelpers\Link;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * ViewHelper for creating links to a file (FAL).
 *
 * ```
 *   <f:link.file file="{file}" target="_blank" download="true" filename="some-file.pdf">See file</f:link.file>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-link-file
 */
final class FileViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('file', FileInterface::class, 'Specifies the file to create a link to', true);
        $this->registerArgument('download', 'bool', 'Specifies if file should be downloaded instead of displayed');
        $this->registerArgument('filename', 'string', 'Specifies an alternative filename. If filename contains a file extension, this must be the same as from \'file\'.');
    }

    public function render(): string
    {
        $file = $this->arguments['file'];

        if (!($file instanceof FileInterface)) {
            throw new Exception('Argument \'file\' must be an instance of ' . FileInterface::class, 1621511632);
        }

        // Get the public URL. This url is either be defined by a GeneratePublicUrlForResourceEvent,
        // an OnlineMedia helper, the corresponding driver or using the file dump functionality.
        $publicUrl = $file->getPublicUrl();

        // Early return in case public url is null as this indicates the file is
        // not accessible, e.g. because the corresponding storage is offline.
        if ($publicUrl === null) {
            return '';
        }

        if (str_contains($publicUrl, 'dumpFile')) {
            // In case we deal with is a file dump URL, recreate the URL
            // by taking the defined view helper arguments into account.
            $publicUrl = $this->createFileDumpUrl($file);
        } elseif ($this->arguments['download'] ?? false) {
            // In case the URL directly links to the file (no eID) and
            // the file should be downloaded instead of displayed, this
            // must be set by the "download" tag attribute, which may
            // contain an alternative filename.
            $this->tag->addAttribute(
                'download',
                $this->getAlternativeFilename($file)
            );
        }

        $this->tag->addAttribute('href', $publicUrl);
        $childContent = $this->renderChildren();
        $this->tag->setContent($childContent ? (string)$childContent : htmlspecialchars($file->getName()));
        $this->tag->forceClosingTag(true);

        return $this->tag->render();
    }

    /**
     * Create a file dump URL, taking the view helper arguments into account
     */
    protected function createFileDumpUrl(FileInterface $file): string
    {
        $parameters = ['eID' => 'dumpFile'];

        if ($file instanceof File) {
            $parameters['t'] = 'f';
            $parameters['f'] = $file->getUid();
        } elseif ($file instanceof FileReference) {
            $parameters['t'] = 'r';
            $parameters['r'] = $file->getUid();
        } elseif ($file instanceof ProcessedFile) {
            $parameters['t'] = 'p';
            $parameters['p'] = $file->getUid();
        }

        if ($download = $this->arguments['download'] ?? false) {
            $parameters['dl'] = (int)$download;
        }

        if (($filename = $this->getAlternativeFilename($file)) !== '') {
            $parameters['fn'] = $filename;
        }

        $hashService = GeneralUtility::makeInstance(HashService::class);
        $parameters['token'] = $hashService->hmac(implode('|', $parameters), 'resourceStorageDumpFile');

        return GeneralUtility::locationHeaderUrl(PathUtility::getAbsoluteWebPath(Environment::getPublicPath() . '/index.php'))
            . '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }

    protected function getAlternativeFilename(FileInterface $file): string
    {
        $alternativeFilename = $this->arguments['filename'] ?? '';

        // Return early if filename is empty or not valid
        if ($alternativeFilename === '' || !preg_match('/^[0-9a-z._\-]+$/i', $alternativeFilename)) {
            return '';
        }

        $extension = pathinfo($alternativeFilename, PATHINFO_EXTENSION);
        if ($extension === '') {
            // Add original extension in case alternative filename did not contain any
            $alternativeFilename = rtrim($alternativeFilename, '.') . '.' . $file->getExtension();
        }

        // Check if given or resolved extension matches the original one
        return $file->getExtension() === pathinfo($alternativeFilename, PATHINFO_EXTENSION)
            ? $alternativeFilename
            : '';
    }
}
