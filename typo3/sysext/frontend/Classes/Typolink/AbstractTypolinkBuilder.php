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

use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Http\UrlProcessorInterface;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Abstract class to provide proper helper for most types necessary
 * Hands in the contentobject which is needed here for all the stdWrap magic.
 */
abstract class AbstractTypolinkBuilder
{
    /**
     * @var ContentObjectRenderer
     */
    protected $contentObjectRenderer;

    /**
     * AbstractTypolinkBuilder constructor.
     *
     * @param $contentObjectRenderer ContentObjectRenderer
     */
    public function __construct(ContentObjectRenderer $contentObjectRenderer)
    {
        $this->contentObjectRenderer = $contentObjectRenderer;
    }

    /**
     * Should be implemented by all subclasses to return an array with three parts:
     * - URL
     * - Link Text (can be modified)
     * - Target (can be modified)
     *
     * @param array $linkDetails parsed link details by the LinkService
     * @param string $linkText the link text
     * @param string $target the target to point to
     * @param array $conf the TypoLink configuration array
     * @return array an array with three parts (URL, Link Text, Target)
     */
    abstract public function build(array &$linkDetails, string $linkText, string $target, array $conf): array;

    /**
     * Forces a given URL to be absolute.
     *
     * @param string $url The URL to be forced to be absolute
     * @param array $configuration TypoScript configuration of typolink
     * @return string The absolute URL
     */
    protected function forceAbsoluteUrl(string $url, array $configuration): string
    {
        if (!empty($url) && !empty($configuration['forceAbsoluteUrl']) &&  preg_match('#^(?:([a-z]+)(://)([^/]*)/?)?(.*)$#', $url, $matches)) {
            $urlParts = [
                'scheme' => $matches[1],
                'delimiter' => '://',
                'host' => $matches[3],
                'path' => $matches[4]
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
            $forceAbsoluteUrl = &$configuration['forceAbsoluteUrl.']['scheme'];
            if (!empty($forceAbsoluteUrl) && $urlParts['scheme'] !== $forceAbsoluteUrl) {
                $urlParts['scheme'] = $forceAbsoluteUrl;
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
     * Helper method to a fallback method parsing HTML out of it
     *
     * @param string $originalLinkText the original string, if empty, the fallback link text
     * @param string $fallbackLinkText the string to be used.
     * @return string the final text
     */
    protected function parseFallbackLinkTextIfLinkTextIsEmpty(string $originalLinkText, string $fallbackLinkText): string
    {
        if ($originalLinkText === '') {
            return $this->contentObjectRenderer->parseFunc($fallbackLinkText, ['makelinks' => 0], '< lib.parseFunc');
        }
        return $originalLinkText;
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
        $targetAttributeAllowed = (!$respectFrameSetOption || !$tsfe->config['config']['doctype'] ||
            in_array((string)$tsfe->config['config']['doctype'], ['xhtml_trans', 'xhtml_frames', 'xhtml_basic', 'html5'], true));

        $target = '';
        if (isset($conf[$name])) {
            $target = $conf[$name];
        } elseif ($targetAttributeAllowed) {
            $target = $fallbackTarget;
        }
        if ($conf[$name . '.']) {
            $target = (string)$this->contentObjectRenderer->stdWrap($target, $conf[$name . '.']);
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
        if (
            empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['urlProcessing']['urlProcessors'])
            || !is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['urlProcessing']['urlProcessors'])
        ) {
            return $url;
        }

        $urlProcessors = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['urlProcessing']['urlProcessors'];
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
        if (!$GLOBALS['TSFE']) {
            // This usually happens when typolink is created by the TYPO3 Backend, where no TSFE object
            // is there. This functionality is currently completely internal, as these links cannot be
            // created properly from the Backend.
            // However, this is added to avoid any exceptions when trying to create a link
            $GLOBALS['TSFE'] = GeneralUtility::makeInstance(
                TypoScriptFrontendController::class,
                    [],
                    (int)GeneralUtility::_GP('id'),
                    (int)GeneralUtility::_GP('type')
            );
            $GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance(PageRepository::class);
            $GLOBALS['TSFE']->sys_page->init(false);
            $GLOBALS['TSFE']->tmpl = GeneralUtility::makeInstance(TemplateService::class);
            $GLOBALS['TSFE']->tmpl->init();
        }
        return $GLOBALS['TSFE'];
    }
}
