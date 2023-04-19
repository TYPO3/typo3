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

namespace TYPO3\CMS\Backend\Template;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * This is an internal helper trait to DRY basic PageRenderer backend
 * setup code in the backend. It configures the PageRenderer for general
 * backend use (charset, favicon, ...).
 * Most prominent use is ModuleTemplate - The View used to render backend
 * modules that have doc headers. It's also used in controllers that render
 * backend things that have no doc header: For instance the login, the main
 * frame and link handlers - the iframes within modals.
 * The PageRenderer in general is more on the "maybe we can get rid of it soon"
 * side. This trait exists to simplify a possible refactoring. In general,
 * controllers should strive to do as little PageRenderer calls as possible and
 * move existing calls to templates using f:be.pageRenderer ViewHelper. This
 * will simplify substituting PageRenderer with a slim dedicated backend solution.
 *
 * @internal helper. Do not use in extensions.
 */
trait PageRendererBackendSetupTrait
{
    /**
     * Sets mandatory parameters for the PageRenderer.
     */
    protected function setUpBasicPageRendererForBackend(
        PageRenderer $pageRenderer,
        ExtensionConfiguration $extensionConfiguration,
        ServerRequestInterface $request,
        LanguageService $languageService,
    ): void {
        $pageRenderer->setLanguage($languageService->getLocale());
        $pageRenderer->setMetaTag('name', 'viewport', 'width=device-width, initial-scale=1');
        $pageRenderer->setFavIcon($this->getBackendFavicon($extensionConfiguration, $request));
        $nonce = $request->getAttribute('nonce');
        if ($nonce !== null) {
            $pageRenderer->setNonce($nonce);
            $pageRenderer->setApplyNonceHint(true);
        }
        $this->loadStylesheets($pageRenderer);
    }

    /**
     * Load all registered stylesheets from $GLOBALS['TBE_STYLES'] "API"
     */
    protected function loadStylesheets(PageRenderer $pageRenderer): void
    {
        if (!empty($GLOBALS['TBE_STYLES']['stylesheet'])) {
            trigger_error(
                '$GLOBALS[\'TBE_STYLES\'][\'stylesheet\'] will be removed in TYPO3 v13.0. Use $GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'stylesheets\'] instead.',
                E_USER_DEPRECATED
            );
            $pageRenderer->addCssFile($GLOBALS['TBE_STYLES']['stylesheet']);
        }
        if (!empty($GLOBALS['TBE_STYLES']['stylesheet2'])) {
            trigger_error(
                '$GLOBALS[\'TBE_STYLES\'][\'stylesheet2\'] will be removed in TYPO3 v13.0. Use $GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'stylesheets\'] instead.',
                E_USER_DEPRECATED
            );
            $pageRenderer->addCssFile($GLOBALS['TBE_STYLES']['stylesheet2']);
        }
        // Add all *.css files of the directory $path to the stylesheets
        foreach ($this->getRegisteredStylesheetFolders() as $folder) {
            trigger_error(
                '$GLOBALS[\'TBE_STYLES\'][\'skins\'][\'stylesheetDirectories\'] will be removed in TYPO3 v13.0. Use $GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'stylesheets\'] instead.',
                E_USER_DEPRECATED
            );
            // Read all files in directory and sort them alphabetically
            foreach (GeneralUtility::getFilesInDir($folder, 'css', true) as $cssFile) {
                $pageRenderer->addCssFile($cssFile);
            }
        }
        foreach ($GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets'] ?? [] as $path) {
            $path = GeneralUtility::getFileAbsFileName($path);
            if (!$path) {
                continue;
            }
            if (is_dir($path)) {
                // Path like 'EXT:my_extension/Resources/Public/Css/Backend'
                foreach (GeneralUtility::getFilesInDir($path, 'css', true) as $cssFile) {
                    $pageRenderer->addCssFile($cssFile);
                }
            } elseif (file_exists($path)) {
                // A single file 'EXT:my_extension/Resources/Public/Css/Backend/main.css' or just a single file
                $pageRenderer->addCssFile($path);
            }
        }
    }

    /**
     * Return an array of all stylesheet directories registered via $GLOBAlS['TBE_STYLES']['skins'].
     * @deprecated will be removed in TYPO3 v13.0. Use $GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets'] instead.
     */
    protected function getRegisteredStylesheetFolders(): array
    {
        $stylesheetDirectories = [];
        foreach ($GLOBALS['TBE_STYLES']['skins'] ?? [] as $skin) {
            foreach ($skin['stylesheetDirectories'] ?? [] as $stylesheetDir) {
                $directory = GeneralUtility::getFileAbsFileName($stylesheetDir);
                if (!empty($directory)) {
                    $stylesheetDirectories[] = $directory;
                }
            }
        }
        return $stylesheetDirectories;
    }

    /**
     * Retrieves configured favicon for backend with fallback.
     */
    protected function getBackendFavicon(ExtensionConfiguration $extensionConfiguration, ServerRequestInterface $request): string
    {
        $backendFavicon = $extensionConfiguration->get('backend', 'backendFavicon');
        if (!empty($backendFavicon)) {
            return $this->getUriForFileName($request, $backendFavicon);
        }
        return PathUtility::getPublicResourceWebPath('EXT:backend/Resources/Public/Icons/favicon.ico');
    }

    /**
     * Returns the uri of a relative reference, resolves the "EXT:" prefix
     * (way of referring to files inside extensions) and checks that the file is inside
     * the project root of the TYPO3 installation
     *
     * @param string $filename The input filename/filepath to evaluate
     * @return string Returns the filename of $filename if valid, otherwise blank string.
     */
    protected function getUriForFileName(ServerRequestInterface $request, string $filename): string
    {
        if (PathUtility::hasProtocolAndScheme($filename)) {
            return $filename;
        }
        $urlPrefix = '';
        if (PathUtility::isExtensionPath($filename)) {
            $filename = PathUtility::getPublicResourceWebPath($filename);
        } elseif (!str_starts_with($filename, '/')) {
            $urlPrefix = $this->getNormalizedParams($request)->getSitePath();
        }
        return $urlPrefix . $filename;
    }

    protected function getNormalizedParams(ServerRequestInterface $request): NormalizedParams
    {
        return $request->getAttribute('normalizedParams');
    }
}
