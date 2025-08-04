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

namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Renders a link to re-import the static SQL data of an extension.
 *
 * @internal
 */
final class ReloadSqlDataViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    protected static string $registryNamespace = 'extensionDataImport';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('extension', 'array', 'Extension key', true);
    }

    public function render(): string
    {
        $extension = $this->arguments['extension'];
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $staticSqlDataFile = $extension['packagePath'] . 'ext_tables_static+adt.sql';
        if (!file_exists($staticSqlDataFile)) {
            return '<span class="btn btn-default disabled">' . $iconFactory->getIcon('empty-empty', IconSize::SMALL)->render() . '</span>';
        }

        $registry = GeneralUtility::makeInstance(Registry::class);
        $oldFileHash = $registry->get(self::$registryNamespace, PathUtility::stripPathSitePrefix($staticSqlDataFile));

        $fileHashIsEqual = true;
        // We used to only store "1" in the database when data was imported
        // No need to compare file content here and just show the reload icon
        if (!empty($oldFileHash) && $oldFileHash !== 1) {
            $currentFileHash = hash_file('xxh3', $staticSqlDataFile);
            $fileHashIsEqual = $oldFileHash === $currentFileHash;
        }

        if ($fileHashIsEqual) {
            $iconIdentifier = 'actions-database-reload';
            $languageKey = 'extensionList.databaseReload';
        } else {
            $iconIdentifier = 'actions-database-import';
            $languageKey = 'extensionList.databaseImport';
        }

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        /** @var RequestInterface $request */
        $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        $uriBuilder->setRequest($request);
        $uriBuilder->reset();
        $uri = $uriBuilder->uriFor(
            'reloadExtensionData',
            ['extensionKey' => $extension['key']],
            'Action'
        );
        $this->tag->addAttribute('href', $uri);
        $this->tag->addAttribute('title', htmlspecialchars($this->getLanguageService()->sL(
            'LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:' . $languageKey
        )));
        $this->tag->setContent($iconFactory->getIcon($iconIdentifier, IconSize::SMALL)->render());

        return $this->tag->render();
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
