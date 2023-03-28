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
 * Class to render the XML Sitemap to be used as a UserFunction.
 *
 * @internal this class is not part of TYPO3's Core API.
 */
final class XmlSitemapRenderer
{
    public function __construct(
        private readonly TypoScriptService $typoScriptService,
        private readonly RenderingContextFactory $renderingContextFactory,
    ) {
    }

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
        $renderingContext = $this->renderingContextFactory->create();
        $templatePaths = $renderingContext->getTemplatePaths();
        $templatePaths->setTemplateRootPaths($viewConfiguration['templateRootPaths'] ?? []);
        $templatePaths->setLayoutRootPaths($viewConfiguration['layoutRootPaths'] ?? []);
        $templatePaths->setPartialRootPaths($viewConfiguration['partialRootPaths'] ?? []);
        $templatePaths->setFormat('xml');
        $sitemapType = $typoScriptConfiguration['sitemapType'] ?? 'xmlSitemap';
        $view = GeneralUtility::makeInstance(TemplateView::class, $renderingContext);
        $view->assign('type', $request->getAttribute('frontend.controller')->getPageArguments()->getPageType());
        $view->assign('sitemapType', $sitemapType);
        $configConfiguration = $configurationArrayWithoutDots['config'] ?? [];
        if (!empty($sitemapName = ($request->getQueryParams()['sitemap'] ?? null))) {
            $view->assign('xslFile', $this->getXslFilePath($configConfiguration, $sitemapType, $sitemapName));
            return $this->renderSitemap($request, $view, $configConfiguration, $sitemapType, $sitemapName);
        }
        $view->assign('xslFile', $this->getXslFilePath($configConfiguration, $sitemapType));
        return $this->renderIndex($request, $view, $configConfiguration, $sitemapType);
    }

    private function renderIndex(ServerRequestInterface $request, TemplateView $view, array $configConfiguration, string $sitemapType): string
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

    private function renderSitemap(ServerRequestInterface $request, TemplateView $view, array $configConfiguration, string $sitemapType, string $sitemapName): string
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
                $template = $sitemapConfig['template'] ?? 'Sitemap';
                return $view->render($template);
            }
            throw new InvalidConfigurationException('No valid provider set for ' . $sitemapName, 1535578522);
        }
        throw new PropagateResponseException(
            GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'No valid configuration found for sitemap ' . $sitemapName
            ),
            1535578569
        );
    }

    private function getXslFilePath(array $configConfiguration, string $sitemapType, string $sitemapName = null): string
    {
        $path = $configConfiguration[$sitemapType]['sitemaps'][$sitemapName]['config']['xslFile']
            ?? $configConfiguration[$sitemapType]['sitemaps']['xslFile']
            ?? $configConfiguration['xslFile']
            ?? 'EXT:seo/Resources/Public/CSS/Sitemap.xsl';
        return PathUtility::getAbsoluteWebPath(GeneralUtility::getFileAbsFileName($path));
    }
}
