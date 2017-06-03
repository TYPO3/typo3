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
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Install\UpgradeAnalysis\DocumentationFile;

/**
 * Ajax controller, part of "extension scanner". Called at the end of "scan all"
 * as last action. Gets a list of RST file hashes that matched, goes through all
 * existing RST files, finds those marked as "FullyScanned" and marks those that
 * did not had any matches as "you are not affected".
 */
class ExtensionScannerMarkFullyScannedRestFiles extends AbstractAjaxAction
{
    /**
     * Get list of files of an extension for extension scanner
     *
     * @return array
     */
    protected function executeAction(): array
    {
        $foundRestFileHashes = (array)$this->postValues['hashes'];

        // First un-mark files marked as scanned-ok
        $registry = new Registry();
        $registry->removeAllByNamespace('extensionScannerNotAffected');

        // Find all .rst files (except those from v8), see if they are tagged with "FullyScanned"
        // and if their content is not in incoming "hashes" array, mark as "not affected"
        $documentationFile = new DocumentationFile();
        $finder = new Finder();
        $restFilesBasePath = PATH_site . ExtensionManagementUtility::siteRelPath('core') . 'Documentation/Changelog';
        $restFiles = $finder->files()->in($restFilesBasePath);
        $fullyScannedRestFilesNotAffected = [];
        foreach ($restFiles as $restFile) {
            // Skip files in "8.x" directory
            /** @var $restFile SplFileInfo */
            if (substr($restFile->getRelativePath(), 0, 1) === '8') {
                continue;
            }

            // Build array of file (hashes) not affected by current scan, if they are tagged as "FullyScanned"
            $parsedRestFile = array_pop($documentationFile->getListEntry(realpath($restFile->getPathname())));
            if (!in_array($parsedRestFile['file_hash'], $foundRestFileHashes, true)
                && in_array('FullyScanned', $parsedRestFile['tags'], true)
            ) {
                $fullyScannedRestFilesNotAffected[] = $parsedRestFile['file_hash'];
            }
        }

        foreach ($fullyScannedRestFilesNotAffected as $fileHash) {
            $registry->set('extensionScannerNotAffected', $fileHash, $fileHash);
        }

        $this->view->assignMultiple([
            'success' => true,
            'markedAsNotAffected' => count($fullyScannedRestFilesNotAffected),
        ]);
        return $this->view->render();
    }
}
