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

namespace TYPO3\CMS\Seo\XmlSitemap;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\HashValue;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyRegistry;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Seo\XmlSitemap\Exception\InvalidConfigurationException;

/**
 * Class to render the XML Sitemap to be used as a UserFunction.
 *
 * @internal this class is not part of TYPO3's Core API.
 */
#[Autoconfigure(public: true)]
final readonly class XmlSitemapRenderer
{
    public function __construct(
        private TypoScriptService $typoScriptService,
        private ErrorController $errorController,
        private ViewFactoryInterface $viewFactory,
        private PolicyRegistry $policyRegistry,
    ) {}

    /**
     * @param string $_ unused, but needed as this is called via userfunc and passes a string as first parameter
     * @param array $typoScriptConfiguration TypoScript configuration specified in USER Content Object
     * @throws InvalidConfigurationException
     */
    public function render(string $_, array $typoScriptConfiguration, ServerRequestInterface $request): string
    {
        $settingsTree = $request->getAttribute('frontend.typoscript')->getSetupTree()->getChildByName('plugin')->getChildByName('tx_seo');
        $configurationArrayWithoutDots = $this->typoScriptService->convertTypoScriptArrayToPlainArray($settingsTree->toArray());
        $viewConfiguration = $configurationArrayWithoutDots['view'] ?? [];
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: $viewConfiguration['templateRootPaths'] ?? [],
            partialRootPaths: $viewConfiguration['partialRootPaths'] ?? [],
            layoutRootPaths: $viewConfiguration['layoutRootPaths'] ?? [],
            request: $request,
            format: 'xml',
        );
        $view = $this->viewFactory->create($viewFactoryData);
        $sitemapType = $typoScriptConfiguration['sitemapType'] ?? 'xmlSitemap';
        $view->assign('type', $request->getAttribute('routing')->getPageType());
        $view->assign('sitemapType', $sitemapType);
        $configConfiguration = $configurationArrayWithoutDots['config'] ?? [];
        if (!empty($sitemapName = ($request->getQueryParams()['tx_seo']['sitemap'] ?? null))) {
            $xslPath = $this->getXslFilePath($configConfiguration, $sitemapType, $sitemapName);
            $this->applyDynamicContentSecurityPolicy($xslPath);
            $view->assign('xslFile', $this->getUriFromFilePath($xslPath));
            return $this->renderSitemap($request, $view, $configConfiguration, $sitemapType, $sitemapName);
        }
        $xslPath = $this->getXslFilePath($configConfiguration, $sitemapType);
        $this->applyDynamicContentSecurityPolicy($xslPath);
        $view->assign('xslFile', $this->getUriFromFilePath($xslPath));
        return $this->renderIndex($request, $view, $configConfiguration, $sitemapType);
    }

    private function renderIndex(ServerRequestInterface $request, ViewInterface $view, array $configConfiguration, string $sitemapType): string
    {
        $sitemaps = [];
        foreach ($configConfiguration[$sitemapType]['sitemaps'] as $sitemapName => $sitemapConfig) {
            $sitemapProvider = $sitemapConfig['provider'] ?? null;
            if (is_string($sitemapName)
                && is_string($sitemapProvider)
                && class_exists($sitemapProvider)
                && is_subclass_of($sitemapProvider, XmlSitemapDataProviderInterface::class)
            ) {
                /** @var XmlSitemapDataProviderInterface $provider */
                $provider = GeneralUtility::makeInstance($sitemapProvider, $request, $sitemapName, $sitemapConfig['config'] ?? []);
                $pages = $provider->getNumberOfPages();
                for ($page = 0; $page < $pages; $page++) {
                    $sitemaps[] = [
                        'key' => $sitemapName,
                        'page' => $page,
                        'lastMod' => $provider->getLastModified(),
                    ];
                }
            }
        }
        $view->assign('sitemaps', $sitemaps);
        return $view->render('Index');
    }

    private function renderSitemap(ServerRequestInterface $request, ViewInterface $view, array $configConfiguration, string $sitemapType, string $sitemapName): string
    {
        $sitemapConfig = $configConfiguration[$sitemapType]['sitemaps'][$sitemapName] ?? null;
        if ($sitemapConfig) {
            $sitemapProvider = $sitemapConfig['provider'] ?? null;
            if (is_string($sitemapProvider)
                && class_exists($sitemapProvider)
                && is_subclass_of($sitemapProvider, XmlSitemapDataProviderInterface::class)
            ) {
                /** @var XmlSitemapDataProviderInterface $provider */
                $provider = GeneralUtility::makeInstance($sitemapProvider, $request, $sitemapName, $sitemapConfig['config'] ?? []);
                $items = $provider->getItems();
                $view->assign('items', $items);
                $template = $sitemapConfig['config']['template'] ?? $sitemapConfig['template'] ?? 'Sitemap';
                return $view->render($template);
            }
            throw new InvalidConfigurationException('No valid provider set for ' . $sitemapName, 1535578522);
        }
        throw new PropagateResponseException(
            $this->errorController->pageNotFoundAction(
                $request,
                'No valid configuration found for sitemap ' . $sitemapName
            ),
            1535578569
        );
    }

    private function getXslFilePath(array $configConfiguration, string $sitemapType, ?string $sitemapName = null): string
    {
        $path = $configConfiguration[$sitemapType]['sitemaps'][$sitemapName]['config']['xslFile']
            ?? $configConfiguration[$sitemapType]['sitemaps']['xslFile']
            ?? $configConfiguration['xslFile']
            ?? 'EXT:seo/Resources/Public/CSS/Sitemap.xsl';
        return GeneralUtility::getFileAbsFileName($path);
    }

    private function getUriFromFilePath(string $filePath): string
    {
        return PathUtility::getAbsoluteWebPath($filePath);
    }

    /**
     * Applies `Content-Security-Policy` mutations for `unsafe-hashes` for XSLT styles.
     * This is done dynamically, since XSLT styles might change some day...
     *
     * The expected hash for the default XSLT styles is `sha256-d0ax6zoVJBeBpy4l3O2FJ6Y1L4SalCWw2x62uoJH15k=`.
     */
    private function applyDynamicContentSecurityPolicy(string $xslPath): void
    {
        if (!file_exists($xslPath)) {
            return;
        }
        $dom = new \DOMDocument();
        $dom->load($xslPath);
        if (!$dom instanceof \DOMDocument) {
            return;
        }
        $hashes = [];
        foreach ($dom->getElementsByTagName('style') as $node) {
            if (!$node instanceof \DOMElement || $node->getAttribute('type') !== 'text/css') {
                continue;
            }
            $hashes[] = HashValue::hash($node->textContent);
        }
        if ($hashes === []) {
            return;
        }
        $this->policyRegistry->appendMutationCollection(
            new MutationCollection(
                new Mutation(
                    MutationMode::Extend,
                    Directive::StyleSrcElem,
                    SourceKeyword::unsafeHashes,
                    ...$hashes
                )
            )
        );
    }
}
