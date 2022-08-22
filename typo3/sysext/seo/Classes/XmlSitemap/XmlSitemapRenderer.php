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
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Seo\XmlSitemap\Exception\InvalidConfigurationException;
use TYPO3Fluid\Fluid\View\TemplateView;

/**
 * Class to render the XML Sitemap to be used as a UserFunction
 * @internal this class is not part of TYPO3's Core API.
 */
class XmlSitemapRenderer
{
    protected array $configuration;
    protected TemplateView $view;

    public function __construct(
        protected TypoScriptService $typoScriptService,
        protected RenderingContextFactory $renderingContextFactory,
    ) {
    }

    protected function initialize(array $fullConfiguration)
    {
        $this->configuration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($fullConfiguration['plugin.']['tx_seo.'] ?? []);
        $renderingContext = $this->renderingContextFactory->create();
        $templatePaths = $renderingContext->getTemplatePaths();
        $templatePaths->setTemplateRootPaths($this->configuration['view']['templateRootPaths']);
        $templatePaths->setLayoutRootPaths($this->configuration['view']['layoutRootPaths']);
        $templatePaths->setPartialRootPaths($this->configuration['view']['partialRootPaths']);
        $templatePaths->setFormat('xml');
        $this->view = GeneralUtility::makeInstance(TemplateView::class, $renderingContext);
    }

    /**
     * @param string $_ unused, but needed as this is called via userfunc and passes a string as first parameter
     * @param array $typoScriptConfiguration TypoScript configuration specified in USER Content Object
     * @param ServerRequestInterface $request
     * @return string
     * @throws InvalidConfigurationException
     */
    public function render(string $_, array $typoScriptConfiguration, ServerRequestInterface $request): string
    {
        $this->initialize($GLOBALS['TSFE']->tmpl->setup);
        $this->view->assign('type', $GLOBALS['TSFE']->type);
        $sitemapType = $typoScriptConfiguration['sitemapType'] ?? 'xmlSitemap';
        $this->view->assign('xslFile', $this->getXslFilePath($sitemapType));
        if (!empty($sitemap = ($request->getQueryParams()['sitemap'] ?? null))) {
            return $this->renderSitemap($request, $sitemap, $sitemapType);
        }

        return $this->renderIndex($request, $sitemapType);
    }

    protected function renderIndex(ServerRequestInterface $request, string $sitemapType): string
    {
        $sitemaps = [];
        foreach ($this->configuration['config'][$sitemapType]['sitemaps'] ?? [] as $sitemap => $config) {
            if (!empty($config['provider']) && is_string($config['provider'])
                && class_exists($config['provider'])
                && is_subclass_of($config['provider'], XmlSitemapDataProviderInterface::class)
            ) {
                /** @var XmlSitemapDataProviderInterface $provider */
                $provider = GeneralUtility::makeInstance(
                    $config['provider'],
                    $request,
                    $sitemap,
                    (array)($config['config'] ?? [])
                );

                $pages = $provider->getNumberOfPages();

                for ($page = 0; $page < $pages; $page++) {
                    $sitemaps[] = [
                        'key' => $sitemap,
                        'page' => $page,
                        'lastMod' => $provider->getLastModified(),
                    ];
                }
            }
        }

        $this->view->assign('sitemapType', $sitemapType);
        $this->view->assign('sitemaps', $sitemaps);

        return $this->view->render('Index');
    }

    protected function renderSitemap(ServerRequestInterface $request, string $sitemap, string $sitemapType): string
    {
        if (!empty($sitemapConfig = $this->configuration['config'][$sitemapType]['sitemaps'][$sitemap] ?? null)) {
            if (class_exists($sitemapConfig['provider']) &&
                is_subclass_of($sitemapConfig['provider'], XmlSitemapDataProviderInterface::class)) {
                /** @var XmlSitemapDataProviderInterface $provider */
                $provider = GeneralUtility::makeInstance(
                    $sitemapConfig['provider'],
                    $request,
                    $sitemap,
                    (array)($sitemapConfig['config'] ?? [])
                );

                $items = $provider->getItems();

                $this->view->assign('xslFile', $this->getXslFilePath($sitemapType, $sitemap));
                $this->view->assign('items', $items);
                $this->view->assign('sitemapType', $sitemapType);

                $template = ($sitemapConfig['config']['template'] ?? false) ?: 'Sitemap';
                return $this->view->render($template);
            }
            throw new InvalidConfigurationException('No valid provider set for ' . $sitemap, 1535578522);
        }

        throw new PropagateResponseException(
            GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'No valid configuration found for sitemap ' . $sitemap
            ),
            1535578569
        );
    }

    protected function getXslFilePath(string $sitemapType = null, string $sitemap = null): string
    {
        $path = $this->configuration['config']['xslFile'] ?? 'EXT:seo/Resources/Public/CSS/Sitemap.xsl';
        $path = ($sitemapType !== null) ? ($this->configuration['config'][$sitemapType]['sitemaps']['xslFile'] ?? $path) : $path;
        $path = ($sitemapType !== null && $sitemap !== null) ? ($this->configuration['config'][$sitemapType]['sitemaps'][$sitemap]['config']['xslFile'] ?? $path) : $path;
        return PathUtility::getAbsoluteWebPath(GeneralUtility::getFileAbsFileName($path));
    }
}
