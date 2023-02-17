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

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService;
use TYPO3\CMS\Core\Page\DefaultJavaScriptAssetTrait;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Event\AfterLinkIsGeneratedEvent;

/**
 * Main class for generating any kind of frontend links.
 * Contains all logic for the infamous typolink() functionality.
 */
class LinkFactory implements LoggerAwareInterface
{
    use DefaultJavaScriptAssetTrait;
    use LoggerAwareTrait;

    public function __construct(
        protected readonly LinkService $linkService,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly TypoLinkCodecService $typoLinkCodecService,
        protected readonly FrontendInterface $runtimeCache,
        protected readonly SiteFinder $siteFinder,
    ) {
    }

    /**
     * Main method to create links from typolink strings and configuration.
     */
    public function create(string $linkText, array $linkConfiguration, ContentObjectRenderer $contentObjectRenderer): LinkResultInterface
    {
        if (isset($linkConfiguration['parameter.'])) {
            // Evaluate "parameter." stdWrap but keep additional information (like target, class and title)
            $linkParameterParts = $this->typoLinkCodecService->decode($linkConfiguration['parameter'] ?? '');
            $modifiedLinkParameterString = $contentObjectRenderer->stdWrap($linkParameterParts['url'], $linkConfiguration['parameter.']);
            // As the stdWrap result might contain target etc. as well again (".field = header_link")
            // the result is then taken from the stdWrap and overridden if the value is not empty.
            $modifiedLinkParameterParts = $this->typoLinkCodecService->decode($modifiedLinkParameterString ?? '');
            $linkParameterParts = array_replace($linkParameterParts, array_filter($modifiedLinkParameterParts, 'trim'));
            $linkParameter = $this->typoLinkCodecService->encode($linkParameterParts);
        } else {
            $linkParameter = trim((string)($linkConfiguration['parameter'] ?? ''));
        }
        try {
            [$linkParameter, $target, $classList, $title] = $this->resolveTypolinkParameterString($linkParameter, $linkConfiguration);
        } catch (UnableToLinkException $e) {
            $this->logger->warning($e->getMessage(), ['linkConfiguration' => $linkConfiguration]);
            throw $e;
        }
        $linkDetails = $this->resolveLinkDetails($linkParameter, $linkConfiguration, $contentObjectRenderer);
        if ($linkDetails === null) {
            throw new UnableToLinkException('Could not resolve link details from ' . $linkParameter, 1642001442, null, $linkText);
        }

        $linkResult = $this->buildLinkResult($linkText, $linkDetails, $target, $linkConfiguration, $contentObjectRenderer);

        // Enrich the link result with resolved attributes and run post processing
        $linkResult = $this->addAdditionalAnchorTagAttributes($linkResult, $linkConfiguration, $contentObjectRenderer);

        // Check, if the target is coded as a JS open window link:
        $linkResult = $this->addJavaScriptOpenWindowInformationAttributes($linkResult, $linkConfiguration, $contentObjectRenderer);
        $linkResult = $this->addSecurityRelValues($linkResult);
        // Title attribute, will override any title attribute from ->addAdditionalAnchorTagAttributes()
        $title = $title ?: trim((string)$contentObjectRenderer->stdWrapValue('title', $linkConfiguration));
        if (!empty($title)) {
            $linkResult = $linkResult->withAttribute('title', $title);
        }
        // Class attribute, will override any class attribute from ->addAdditionalAnchorTagAttributes()
        if (!empty($classList)) {
            $linkResult = $linkResult->withAttribute('class', $classList);
        }

        if ($linkConfiguration['userFunc'] ?? false) {
            $linkResult = $contentObjectRenderer->callUserFunction($linkConfiguration['userFunc'], $linkConfiguration['userFunc.'] ?? [], $linkResult);
            if (!($linkResult instanceof LinkResultInterface)) {
                throw new UnableToLinkException('Calling typolink.userFunc resulted in not returning a valid typolink', 1642171035, null, $linkText);
            }
        }

        $event = new AfterLinkIsGeneratedEvent($linkResult, $contentObjectRenderer, $linkConfiguration);
        $event = $this->eventDispatcher->dispatch($event);
        return $event->getLinkResult();
    }

    /**
     * Creates a link result for a given URL (usually something like "19 _blank css-class "testtitle with whitespace" &X=y").
     * Helpful if you want to create any kind of URL (also possible in TYPO3 Backend).
     */
    public function createUri(string $urlParameter, ContentObjectRenderer $contentObjectRenderer = null): LinkResultInterface
    {
        $contentObjectRenderer = $contentObjectRenderer ?? GeneralUtility::makeInstance(ContentObjectRenderer::class);
        return $this->create('', ['parameter' => $urlParameter], $contentObjectRenderer);
    }

    /**
     * Legacy method, use createUri() instead.
     * @deprecated will be removed in TYPO3 v13.0.
     */
    public function createFromUriString(string $urlParameter): LinkResultInterface
    {
        trigger_error('LinkFactory->createFromUriString() will be removed in TYPO3 v13.0. Use createUri() instead.', E_USER_DEPRECATED);
        return $this->createUri($urlParameter);
    }

    /**
     * Dispatches the linkDetails + configuration to the concrete typolink Builder (page, email etc)
     * and returns a LinkResultInterface.
     */
    protected function buildLinkResult(string $linkText, array $linkDetails, string $target, array $linkConfiguration, ContentObjectRenderer $contentObjectRenderer): LinkResultInterface
    {
        if (isset($linkDetails['type']) && isset($GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder'][$linkDetails['type']])) {
            /** @var AbstractTypolinkBuilder $linkBuilder */
            $linkBuilder = GeneralUtility::makeInstance(
                $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder'][$linkDetails['type']],
                $contentObjectRenderer,
                // AbstractTypolinkBuilder type hints an optional dependency to TypoScriptFrontendController.
                // Some core parts however "fake" $GLOBALS['TSFE'] to stdCLass() due to its long list of
                // dependencies. f:html view helper is such a scenario. This of course crashes if given to typolink builder
                // classes. For now, we check the instance and hand over 'null', giving the link builders the option
                // to take care of tsfe themselves. This scenario is for instance triggered when in BE login when sys_news
                // records set links.
                $contentObjectRenderer->getTypoScriptFrontendController() instanceof TypoScriptFrontendController ? $contentObjectRenderer->getTypoScriptFrontendController() : null
            );
            try {
                return $linkBuilder->build($linkDetails, $linkText, $target, $linkConfiguration);
            } catch (UnableToLinkException $e) {
                $this->logger->debug('Unable to link "{text}"', [
                    'text' => $e->getLinkText(),
                    'exception' => $e,
                ]);
                // Only return the link text directly (done in cObj->typolink)
                throw $e;
            }
        } elseif (isset($linkDetails['url'])) {
            $linkResult = new LinkResult($linkDetails['type'], $linkDetails['url']);
            return $linkResult
                ->withTarget($target)
                ->withLinkConfiguration($linkConfiguration)
                ->withLinkText($linkText);
        }
        throw new UnableToLinkException('No suitable link handler for resolving ' . $linkDetails['typoLinkParameter'], 1642000232, null, $linkText);
    }

    /**
     * Creates $linkDetails out of the link parameter so the concrete LinkBuilder can be resolved.
     */
    protected function resolveLinkDetails(string $linkParameter, array $linkConfiguration, ContentObjectRenderer $contentObjectRenderer): ?array
    {
        $linkDetails = null;
        if (!$linkParameter) {
            // Support anchors without href value if id or name attribute is present.
            $aTagParams = (string)$contentObjectRenderer->stdWrapValue('ATagParams', $linkConfiguration);
            $aTagParams = GeneralUtility::get_tag_attributes($aTagParams);
            // If it looks like an anchor tag, render it anyway
            if (isset($aTagParams['id']) || isset($aTagParams['name'])) {
                $linkDetails = [
                    'type' => LinkService::TYPE_INPAGE,
                    'url' => '',
                ];
            }
        } else {
            // Detecting kind of link and resolve all necessary parameters
            try {
                $linkDetails = $this->linkService->resolve($linkParameter);
            } catch (UnknownLinkHandlerException|InvalidPathException $exception) {
                $this->logger->warning('The link could not be generated', ['exception' => $exception]);
                return null;
            }
        }
        if (is_array($linkDetails)) {
            $linkDetails['typoLinkParameter'] = $linkParameter;
        }
        return $linkDetails;
    }

    /**
     * Does the magic to split the full "typolink" string like "15,13 _blank myclass &more=1" into separate parts
     *
     * @param string $mixedLinkParameter destination data like "15,13 _blank myclass &more=1" used to create the link
     * @param array $linkConfiguration TypoScript configuration
     */
    protected function resolveTypolinkParameterString(string $mixedLinkParameter, array &$linkConfiguration = []): array
    {
        $linkParameterParts = $this->typoLinkCodecService->decode($mixedLinkParameter);
        [$linkHandlerKeyword] = explode(':', $linkParameterParts['url'], 2);
        if (in_array(strtolower((string)preg_replace('#\s|[[:cntrl:]]#', '', (string)$linkHandlerKeyword)), ['javascript', 'data'], true)) {
            // Disallow insecure scheme's like javascript: or data:
            throw new UnableToLinkException('Insuecure scheme for linking detected with "' . $mixedLinkParameter . "'", 1641986533);
        }

        // additional parameters that need to be set
        if ($linkParameterParts['additionalParams'] !== '') {
            $forceParams = $linkParameterParts['additionalParams'];
            // params value
            $linkConfiguration['additionalParams'] = ($linkConfiguration['additionalParams'] ?? '') . $forceParams[0] === '&' ? $forceParams : '&' . $forceParams;
        }

        return [
            $linkParameterParts['url'],
            $linkParameterParts['target'],
            $linkParameterParts['class'],
            $linkParameterParts['title'],
        ];
    }

    protected function addJavaScriptOpenWindowInformationAttributes(LinkResultInterface $linkResult, array $linkConfiguration, ContentObjectRenderer $contentObjectRenderer): LinkResultInterface
    {
        $JSwindowParts = [];
        if ($linkResult->getTarget() && preg_match('/^([0-9]+)x([0-9]+)(:(.*)|.*)$/', $linkResult->getTarget(), $JSwindowParts)) {
            // Take all pre-configured and inserted parameters and compile parameter list, including width+height:
            $JSwindow_tempParamsArr = GeneralUtility::trimExplode(',', strtolower(($linkConfiguration['JSwindow_params'] ?? '') . ',' . ($JSwindowParts[4] ?? '')), true);
            $JSwindow_paramsArr = [];
            $target = $linkConfiguration['target'] ?? 'FEopenLink';
            foreach ($JSwindow_tempParamsArr as $JSv) {
                [$JSp, $JSv] = explode('=', $JSv, 2);
                // If the target is set as JS param, this is extracted
                if ($JSp === 'target') {
                    $target = $JSv;
                } else {
                    $JSwindow_paramsArr[$JSp] = $JSp . '=' . $JSv;
                }
            }
            // Add width/height:
            $JSwindow_paramsArr['width'] = 'width=' . $JSwindowParts[1];
            $JSwindow_paramsArr['height'] = 'height=' . $JSwindowParts[2];

            $JSwindowAttrs = [
                'data-window-url' => $linkResult->getUrl(),
                'data-window-target' => $target,
                'data-window-features' => implode(',', $JSwindow_paramsArr),
            ];
            $linkResult = $linkResult->withAttributes($JSwindowAttrs);
            $linkResult = $linkResult->withAttribute('target', $target);
            $this->addDefaultFrontendJavaScript();
        }
        return $linkResult;
    }

    /**
     * An abstraction method to add parameters to an A tag.
     * Uses the ATagParams property, also includes the global TypoScript config.ATagParams
     */
    protected function addAdditionalAnchorTagAttributes(LinkResultInterface $linkResult, array $linkConfiguration, ContentObjectRenderer $contentObjectRenderer): LinkResultInterface
    {
        $aTagParams = $contentObjectRenderer->stdWrapValue('ATagParams', $linkConfiguration);
        // Add the global config.ATagParams
        $globalParams = $contentObjectRenderer->getTypoScriptFrontendController() ? trim($contentObjectRenderer->getTypoScriptFrontendController()->config['config']['ATagParams'] ?? '') : '';
        $aTagParams = trim($globalParams . ' ' . $aTagParams);
        if (!empty($aTagParams)) {
            // Decode entities here, as they are doubly escaped again when using HTML output
            $aTagParams = GeneralUtility::get_tag_attributes($aTagParams, true);
            // Ensure "href" is not in the list of aTagParams to avoid double tags, usually happens within buggy parseFunc settings
            unset($aTagParams['href']);
            $linkResult = $linkResult->withAttributes($aTagParams);
        }
        return $linkResult;
    }

    protected function addSecurityRelValues(LinkResultInterface $linkResult): LinkResultInterface
    {
        $target = (string)($linkResult->getTarget() ?: $linkResult->getAttribute('data-window-target'));
        if (in_array($target, ['', null, '_self', '_parent', '_top'], true) || $this->isInternalUrl($linkResult->getUrl())) {
            return $linkResult;
        }
        $relAttributeValue = 'noreferrer';
        if ($linkResult->getAttribute('rel') !== null) {
            $existingAttributeValue = $linkResult->getAttribute('rel');
            $relAttributeValue = implode(' ', array_unique(array_merge(
                [$relAttributeValue],
                GeneralUtility::trimExplode(' ', $existingAttributeValue)
            )));
        }
        return $linkResult->withAttribute('rel', $relAttributeValue);
    }

    /**
     * Checks whether the given url is an internal url.
     *
     * It will check the host part only, against all configured sites
     * whether the given host is any. If so, the url is considered internal.
     *
     * Note: It would be good to move this to EXT:core/Classes/Site which accepts also a PSR-7 request and
     * also accepts a PSR-7 Uri to move away from GeneralUtility::isOnCurrentHost
     */
    protected function isInternalUrl(string $url): bool
    {
        $parsedUrl = parse_url($url);
        $foundDomains = 0;
        if (!isset($parsedUrl['host'])) {
            return true;
        }

        $cacheIdentifier = sha1('isInternalDomain' . $parsedUrl['host']);

        if ($this->runtimeCache->has($cacheIdentifier) === false) {
            foreach ($this->siteFinder->getAllSites() as $site) {
                if ($site->getBase()->getHost() === $parsedUrl['host']) {
                    ++$foundDomains;
                    break;
                }
                if ($site->getBase()->getHost() === '' && GeneralUtility::isOnCurrentHost($url)) {
                    ++$foundDomains;
                    break;
                }
            }
            $this->runtimeCache->set($cacheIdentifier, $foundDomains > 0);
        }

        return (bool)$this->runtimeCache->get($cacheIdentifier);
    }
}
