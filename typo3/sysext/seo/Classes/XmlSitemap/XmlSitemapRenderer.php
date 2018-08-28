<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Seo\XmlSitemap;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Seo\XmlSitemap\Exception\InvalidConfigurationException;

/**
 * Class to render the XML Sitemap to be used as a UserFunction
 */
class XmlSitemapRenderer
{
    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var \TYPO3\CMS\Fluid\View\StandaloneView
     */
    protected $view;

    public function __construct()
    {
        $this->configuration = $this->getConfiguration();
        $this->view = $this->getStandaloneView();
        $this->view->assign(
            'xslFile',
            PathUtility::stripPathSitePrefix(
                ExtensionManagementUtility::extPath('seo', 'Resources/Public/CSS/Sitemap.xsl')
            )
        );
    }

    /**
     * @return string
     * @throws InvalidConfigurationException
     */
    public function render(): string
    {
        // Inject request from globals until request will be available to cObj
        $request = $GLOBALS['TYPO3_REQUEST'];
        $this->view->assign('type', $request->getQueryParams()['type']);
        if (!empty($sitemap = $request->getQueryParams()['sitemap'])) {
            return $this->renderSitemap($request, $sitemap);
        }

        return $this->renderIndex($request);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return string
     */
    protected function renderIndex(ServerRequestInterface $request): string
    {
        $sitemaps = [];
        foreach ($this->configuration['config']['xmlSitemap']['sitemaps'] ?? [] as $sitemap => $config) {
            if (class_exists($config['provider']) &&
                is_subclass_of($config['provider'], XmlSitemapDataProviderInterface::class)) {
                /** @var XmlSitemapDataProviderInterface $provider */
                $provider = GeneralUtility::makeInstance(
                    $config['provider'],
                    $request,
                    $sitemap,
                    (array)$config['config']
                );

                $sitemaps[] = [
                    'key' => $sitemap,
                    'lastMod' => $provider->getLastModified()
                ];
            }
        }

        $this->view->assign('sitemaps', $sitemaps);
        $this->view->setTemplate('Index');

        return $this->view->render();
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param string $sitemap
     * @return string
     * @throws \TYPO3\CMS\Seo\XmlSitemap\Exception\InvalidConfigurationException
     */
    protected function renderSitemap(ServerRequestInterface $request, string $sitemap): string
    {
        if (!empty($sitemapConfig = $this->configuration['config']['xmlSitemap']['sitemaps'][$sitemap])) {
            if (class_exists($sitemapConfig['provider']) &&
                is_subclass_of($sitemapConfig['provider'], XmlSitemapDataProviderInterface::class)) {
                /** @var XmlSitemapDataProviderInterface $provider */
                $provider = GeneralUtility::makeInstance(
                    $sitemapConfig['provider'],
                    $request,
                    $sitemap,
                    (array)$sitemapConfig['config']
                );

                $items = $provider->getItems();

                $template = $sitemapConfig['config']['template'] ?: 'Sitemap';
                $this->view->setTemplate($template);
                $this->view->assign('items', $items);

                return $this->view->render();
            }
            throw new InvalidConfigurationException('No valid provider set for ' . $sitemap, 1535578522);
        }

        throw new InvalidConfigurationException('No valid configuration found for sitemap ' . $sitemap, 1535578569);
    }

    /**
     * @return \TYPO3\CMS\Fluid\View\StandaloneView
     */
    protected function getStandaloneView(): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplateRootPaths($this->configuration['view']['templateRootPaths']);
        $view->setLayoutRootPaths($this->configuration['view']['layoutRootPaths']);
        $view->setPartialRootPaths($this->configuration['view']['partialRootPaths']);
        $view->setFormat('xml');

        return $view;
    }

    /**
     * Get the whole typoscript array
     * @return array
     */
    private function getConfiguration(): array
    {
        $configurationManager = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(ConfigurationManagerInterface::class);

        return $configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'seo'
        );
    }
}
