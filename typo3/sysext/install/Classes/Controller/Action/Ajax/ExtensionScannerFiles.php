<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\Controller\Action\Ajax;

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

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Return a list of files of an extension
 */
class ExtensionScannerFiles extends AbstractAjaxAction
{
    /**
     * Get list of files of an extension for extension scanner
     *
     * @return array
     * @throws \RuntimeException
     */
    protected function executeAction(): array
    {
        // Get and validate path
        $extension = $this->postValues['extension'];
        $extensionBasePath = PATH_site . 'typo3conf/ext/' . $extension;
        if (empty($extension) || !GeneralUtility::isAllowedAbsPath($extensionBasePath)) {
            throw new \RuntimeException(
                'Path to extension ' . $extension . ' not allowed.',
                1499777261
            );
        }
        if (!is_dir($extensionBasePath)) {
            throw new \RuntimeException(
                'Extension path ' . $extensionBasePath . ' does not exist or is no directory.',
                1499777330
            );
        }

        $finder = new Finder();
        $files = $finder->files()->in($extensionBasePath)->name('*.php')->sortByName();
        // A list of file names relative to extension directory
        $relativeFileNames = [];
        foreach ($files as $file) {
            /** @var $file SplFileInfo */
            $relativeFileNames[] = $file->getRelativePathname();
        }

        $this->view->assignMultiple([
            'success' => true,
            'files' => $relativeFileNames,
        ]);
        return $this->view->render();
    }
}
