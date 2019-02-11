<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Seo\XmlSitemap;

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
     * @var int
     */
    protected $numberOfItemsPerPage = 1000;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

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
        $this->request = $request;

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
    public function getNumberOfPages(): int
    {
        return (int)ceil(count($this->items) / $this->numberOfItemsPerPage);
    }

    /**
     * @return int
     */
    public function getLastModified(): int
    {
        $lastMod = 0;
        foreach ($this->items as $item) {
            if ((int)$item['lastMod'] > $lastMod) {
                $lastMod = (int)$item['lastMod'];
            }
        }

        return $lastMod;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function defineUrl(array $data): array
    {
        return $data;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        $pageNumber = (int)($this->request->getQueryParams()['page'] ?? 0);
        $page = $pageNumber > 0 ? $pageNumber : 0;
        $items = array_slice(
            $this->items,
            $page * $this->numberOfItemsPerPage,
            $this->numberOfItemsPerPage
        );

        return array_map([$this, 'defineUrl'], $items);
    }
}
