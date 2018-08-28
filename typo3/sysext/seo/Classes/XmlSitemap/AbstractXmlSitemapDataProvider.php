<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Seo\XmlSitemap;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Base class for XmlSitemapProviders to extend
 */
abstract class AbstractXmlSitemapDataProvider implements XmlSitemapDataProviderInterface
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var int
     */
    protected $lastModified;

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var ContentObjectRenderer
     */
    protected $cObj;

    /**
     * AbstractXmlSitemapDataProvider constructor
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param string $key
     * @param array $config
     * @param ContentObjectRenderer $cObj
     */
    public function __construct(ServerRequestInterface $request, string $key, array $config = [], ContentObjectRenderer $cObj = null)
    {
        $this->key = $key;
        $this->config = $config;

        if ($cObj === null) {
            $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        }
        $this->cObj = $cObj;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return int
     */
    public function getLastModified(): int
    {
        $lastMod = 0;
        foreach ($this->items as $item) {
            if ((int)$item['lastMod'] > $lastMod) {
                $lastMod = $item['lastMod'];
            }
        }

        return $lastMod;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return (array)$this->items;
    }
}
