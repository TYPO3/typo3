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
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\SystemResource\Exception\CanNotResolvePublicResourceException;
use TYPO3\CMS\Core\SystemResource\Exception\CanNotResolveSystemResourceException;
use TYPO3\CMS\Core\SystemResource\Exception\InvalidSystemResourceIdentifierException;
use TYPO3\CMS\Core\SystemResource\Identifier\PackageResourceIdentifier;
use TYPO3\CMS\Core\SystemResource\Identifier\SystemResourceIdentifierFactory;
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
     * Load all registered stylesheets from $GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets']
     */
    protected function loadStylesheets(PageRenderer $pageRenderer): void
    {
        // @todo this needs to be replaced with usage of the yet to be created
        //       System Resource API that can handle folders
        //       This will then remove the need to use internal SystemResourceIdentifierFactory here
        $identifierFactory = GeneralUtility::makeInstance(SystemResourceIdentifierFactory::class);
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        foreach ($GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets'] ?? [] as $potentialResourceIdentifier) {
            try {
                $resourceIdentifier = $identifierFactory->create($potentialResourceIdentifier);
            } catch (InvalidSystemResourceIdentifierException) {
                continue;
            }
            if (!$resourceIdentifier instanceof PackageResourceIdentifier) {
                continue;
            }
            $package = $packageManager->getPackage($resourceIdentifier->getPackageKey());
            $relativePath = $resourceIdentifier->getRelativePath();
            $absolutePath = $package->getPackagePath() . $relativePath;
            if (is_dir($absolutePath)) {
                // Path like 'PKG:vendor/my-extension:Resources/Public/Css/Backend'
                foreach (GeneralUtility::getFilesInDir($absolutePath, 'css') as $cssFile) {
                    $pageRenderer->addCssFile((string)$resourceIdentifier->withRelativePath(rtrim($relativePath, '/') . '/' . $cssFile));
                }
            } elseif (file_exists($absolutePath)) {
                // A single file 'PKG:my_extension:Resources/Public/Css/Backend/main.css' or just a single file
                $pageRenderer->addCssFile((string)$resourceIdentifier);
            }
        }
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
        return $this->getUriForFileName($request, 'EXT:backend/Resources/Public/Icons/favicon.ico');
    }

    /**
     * Returns the uri for a system resource
     *
     * @throws CanNotResolvePublicResourceException
     * @throws CanNotResolveSystemResourceException
     */
    protected function getUriForFileName(ServerRequestInterface $request, string $resourceIdentifier): string
    {
        return (string)PathUtility::getSystemResourceUri($resourceIdentifier, $request);
    }

    protected function getNormalizedParams(ServerRequestInterface $request): NormalizedParams
    {
        return $request->getAttribute('normalizedParams');
    }
}
