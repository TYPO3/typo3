<?php

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

namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * ViewHelper for update script link
 * @internal
 */
class ReloadSqlDataViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    protected static string $registryNamespace = 'extensionDataImport';

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument('extension', 'array', 'Extension key', true);
    }

    /**
     * Renders a link to re-import the static SQL data of an extension
     *
     * @return string The rendered a tag
     */
    public function render()
    {
        $extension = $this->arguments['extension'];
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $staticSqlDataFile = $extension['packagePath'] . 'ext_tables_static+adt.sql';
        if (!file_exists($staticSqlDataFile)) {
            return '<span class="btn btn-default disabled">' . $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
        }

        $registry = GeneralUtility::makeInstance(Registry::class);
        $oldMd5Hash = $registry->get(static::$registryNamespace, PathUtility::stripPathSitePrefix($staticSqlDataFile));

        $md5HashIsEqual = true;
        // We used to only store "1" in the database when data was imported
        // No need to compare file content here and just show the reload icon
        if (!empty($oldMd5Hash) && $oldMd5Hash !== 1) {
            $currentMd5Hash = md5_file($staticSqlDataFile);
            $md5HashIsEqual = $oldMd5Hash === $currentMd5Hash;
        }

        if ($md5HashIsEqual) {
            $iconIdentifier = 'actions-database-reload';
            $languageKey = 'extensionList.databaseReload';
        } else {
            $iconIdentifier = 'actions-database-import';
            $languageKey = 'extensionList.databaseImport';
        }

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->setRequest($this->renderingContext->getRequest());
        $uriBuilder->reset();
        $uri = $uriBuilder->uriFor(
            'reloadExtensionData',
            ['extension' => $extension['key']],
            'Action'
        );
        $this->tag->addAttribute('href', $uri);
        $this->tag->addAttribute('title', LocalizationUtility::translate($languageKey, 'extensionmanager'));
        $this->tag->setContent($iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render());

        return $this->tag->render();
    }
}
