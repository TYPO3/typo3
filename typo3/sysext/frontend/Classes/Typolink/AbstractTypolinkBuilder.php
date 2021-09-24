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

namespace TYPO3\CMS\Frontend\Typolink;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Http\UrlProcessorInterface;

/**
 * Abstract class to provide proper helper for most types necessary
 * Hands in the ContentObject and TSFE which are needed here for all the stdWrap magic.
 */
abstract class AbstractTypolinkBuilder
{
    /**
     * @var ContentObjectRenderer
     */
    protected $contentObjectRenderer;

    /**
     * @var TypoScriptFrontendController|null
     */
    protected $typoScriptFrontendController;

    /**
     * AbstractTypolinkBuilder constructor.
     *
     * @param ContentObjectRenderer $contentObjectRenderer
     * @param TypoScriptFrontendController $typoScriptFrontendController
     */
    public function __construct(ContentObjectRenderer $contentObjectRenderer, TypoScriptFrontendController $typoScriptFrontendController = null)
    {
        $this->contentObjectRenderer = $contentObjectRenderer;
        $this->typoScriptFrontendController = $typoScriptFrontendController ?? $GLOBALS['TSFE'] ?? null;
    }

    /**
     * Should be implemented by all subclasses to return an array with three parts:
     * - URL
     * - Link Text (can be modified)
     * - Target (can be modified)
     * - Tag Attributes (optional)
     *
     * @param array $linkDetails parsed link details by the LinkService
     * @param string $linkText the link text
     * @param string $target the target to point to
     * @param array $conf the TypoLink configuration array
     * @return array|LinkResultInterface an array with three parts (URL, Link Text, Target) - please note that in TYPO3 v12.0. this method will require to return a LinkResultInterface object
     */
    abstract public function build(array &$linkDetails, string $linkText, string $target, array $conf);

    /**
     * Forces a given URL to be absolute.
     *
     * @param string $url The URL to be forced to be absolute
     * @param array $configuration TypoScript configuration of typolink
     * @return string The absolute URL
     */
    protected function forceAbsoluteUrl(string $url, array $configuration): string
    {
        if (!empty($url) && !empty($configuration['forceAbsoluteUrl']) && preg_match('#^(?:([a-z]+)(://)([^/]*)/?)?(.*)$#', $url, $matches)) {
            $urlParts = [
                'scheme' => $matches[1],
                'delimiter' => '://',
                'host' => $matches[3],
                'path' => $matches[4],
            ];
            $isUrlModified = false;
            // Set scheme and host if not yet part of the URL:
            if (empty($urlParts['host'])) {
                $urlParts['scheme'] = GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'https' : 'http';
                $urlParts['host'] = GeneralUtility::getIndpEnv('HTTP_HOST');
                $urlParts['path'] = '/' . ltrim($urlParts['path'], '/');
                // absRefPrefix has been prepended to $url beforehand
                // so we only modify the path if no absRefPrefix has been set
                // otherwise we would destroy the path
                if ($this->getTypoScriptFrontendController()->absRefPrefix === '') {
                    $urlParts['path'] = GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . ltrim($urlParts['path'], '/');
                }
                $isUrlModified = true;
            }
            // Override scheme:
            $forcedScheme = $configuration['forceAbsoluteUrl.']['scheme'] ?? null;
            if (!empty($forcedScheme) && $urlParts['scheme'] !== $forcedScheme) {
                $urlParts['scheme'] = $forcedScheme;
                $isUrlModified = true;
            }
            // Also ensure the path has a "/" at the beginning when concatenating everything else together
            if ($urlParts['path'] !== '') {
                $urlParts['path'] = '/' . ltrim($urlParts['path'], '/');
                $isUrlModified = true;
            }
            // Recreate the absolute URL:
            if ($isUrlModified) {
                $url = implode('', $urlParts);
            }
        }
        return $url;
    }

    /**
     * Determines whether lib.parseFunc is defined.
     *
     * @return bool
     */
    protected function isLibParseFuncDefined(): bool
    {
        $configuration = $this->contentObjectRenderer->mergeTSRef(
            ['parseFunc' => '< lib.parseFunc'],
            'parseFunc'
        );
        return !empty($configuration['parseFunc.']) && is_array($configuration['parseFunc.']);
    }

    /**
     * Helper method to a fallback method parsing HTML out of it
     *
     * @param string $originalLinkText the original string, if empty, the fallback link text
     * @param string $fallbackLinkText the string to be used.
     * @return string the final text
     */
    protected function parseFallbackLinkTextIfLinkTextIsEmpty(string $originalLinkText, string $fallbackLinkText): string
    {
        if ($originalLinkText !== '') {
            return $originalLinkText;
        }
        if ($this->isLibParseFuncDefined()) {
            return $this->contentObjectRenderer->parseFunc($fallbackLinkText, ['makelinks' => 0], '< lib.parseFunc');
        }
        // encode in case `lib.parseFunc` is not configured
        return $this->encodeFallbackLinkTextIfLinkTextIsEmpty($originalLinkText, $fallbackLinkText);
    }

    /**
     * Helper method to a fallback method properly encoding HTML.
     *
     * @param string $originalLinkText the original string, if empty, the fallback link text
     * @param string $fallbackLinkText the string to be used.
     * @return string the final text
     */
    protected function encodeFallbackLinkTextIfLinkTextIsEmpty(string $originalLinkText, string $fallbackLinkText): string
    {
        if ($originalLinkText !== '') {
            return $originalLinkText;
        }
        return htmlspecialchars($fallbackLinkText, ENT_QUOTES);
    }

    /**
     * Creates the value for target="..." in a typolink configuration
     *
     * @param array $conf the typolink configuration
     * @param string $name the key, usually "target", "extTarget" or "fileTarget"
     * @param bool $respectFrameSetOption if set, then the fallback is only used as target if the doctype allows it
     * @param string $fallbackTarget the string to be used when no target is found in the configuration
     * @return string the value of the target attribute, if there is one
     */
    protected function resolveTargetAttribute(array $conf, string $name, bool $respectFrameSetOption = false, string $fallbackTarget = ''): string
    {
        $tsfe = $this->getTypoScriptFrontendController();
        $targetAttributeAllowed = !$respectFrameSetOption
            || (!isset($tsfe->config['config']['doctype']) || !$tsfe->config['config']['doctype'])
            || in_array((string)$tsfe->config['config']['doctype'], ['xhtml_trans', 'xhtml_basic', 'html5'], true);

        $target = '';
        if (isset($conf[$name])) {
            $target = $conf[$name];
        } elseif ($targetAttributeAllowed && !($conf['directImageLink'] ?? false)) {
            $target = $fallbackTarget;
        }
        if (isset($conf[$name . '.']) && $conf[$name . '.']) {
            $target = (string)$this->contentObjectRenderer->stdWrap($target, $conf[$name . '.'] ?? []);
        }
        return $target;
    }

    /**
     * Loops over all configured URL modifier hooks (if available) and returns the generated URL or NULL if no URL was generated.
     *
     * @param string $context The context in which the method is called (e.g. typoLink).
     * @param string $url The URL that should be processed.
     * @param array $typolinkConfiguration The current link configuration array.
     * @return string|null Returns NULL if URL was not processed or the processed URL as a string.
     * @throws \RuntimeException if a hook was registered but did not fulfill the correct parameters.
     */
    protected function processUrl(string $context, string $url, array $typolinkConfiguration = [])
    {
        $urlProcessors = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['urlProcessing']['urlProcessors'] ?? false;
        if (!$urlProcessors) {
            return $url;
        }

        foreach ($urlProcessors as $identifier => $configuration) {
            if (empty($configuration) || !is_array($configuration)) {
                throw new \RuntimeException('Missing configuration for URI processor "' . $identifier . '".', 1491130459);
            }
            if (!is_string($configuration['processor']) || empty($configuration['processor']) || !class_exists($configuration['processor']) || !is_subclass_of($configuration['processor'], UrlProcessorInterface::class)) {
                throw new \RuntimeException('The URI processor "' . $identifier . '" defines an invalid provider. Ensure the class exists and implements the "' . UrlProcessorInterface::class . '".', 1491130460);
            }
        }

        $orderedProcessors = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies($urlProcessors);
        $keepProcessing = true;

        foreach ($orderedProcessors as $configuration) {
            /** @var UrlProcessorInterface $urlProcessor */
            $urlProcessor = GeneralUtility::makeInstance($configuration['processor']);
            $url = $urlProcessor->process($context, $url, $typolinkConfiguration, $this->contentObjectRenderer, $keepProcessing);
            if (!$keepProcessing) {
                break;
            }
        }

        return $url;
    }

    /**
     * @return TypoScriptFrontendController
     */
    public function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        if ($this->typoScriptFrontendController instanceof TypoScriptFrontendController) {
            return $this->typoScriptFrontendController;
        }

        // This usually happens when typolink is created by the TYPO3 Backend, where no TSFE object
        // is there. This functionality is currently completely internal, as these links cannot be
        // created properly from the Backend.
        // However, this is added to avoid any exceptions when trying to create a link.
        // Detecting the "first" site usually comes from the fact that TSFE needs to be instantiated
        // during tests
        $request = $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            $sites = GeneralUtility::makeInstance(SiteFinder::class)->getAllSites();
            $site = reset($sites);
            if (!$site instanceof Site) {
                $site = new NullSite();
            }
        }
        $language = $request->getAttribute('language');
        if (!$language instanceof SiteLanguage) {
            $language = $site->getDefaultLanguage();
        }

        $id = $request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? $site->getRootPageId();
        $type = $request->getQueryParams()['type'] ?? $request->getParsedBody()['type'] ?? '0';

        $this->typoScriptFrontendController = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            GeneralUtility::makeInstance(Context::class),
            $site,
            $language,
            $request->getAttribute('routing', new PageArguments((int)$id, (string)$type, [])),
            GeneralUtility::makeInstance(FrontendUserAuthentication::class)
        );
        $this->typoScriptFrontendController->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $this->typoScriptFrontendController->tmpl = GeneralUtility::makeInstance(TemplateService::class);
        return $this->typoScriptFrontendController;
    }
}
