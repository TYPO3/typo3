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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Base class for XmlSitemapProviders to extend
 */
abstract class AbstractXmlSitemapDataProvider implements XmlSitemapDataProviderInterface
{
    protected string $key;
    protected array $items = [];
    protected array $config = [];
    protected ContentObjectRenderer $cObj;
    protected int $numberOfItemsPerPage = 1000;
    protected ServerRequestInterface $request;

    public function __construct(ServerRequestInterface $request, string $key, array $config = [], ContentObjectRenderer $cObj = null)
    {
        $this->key = $key;
        $this->config = $config;
        $this->request = $request;
        $this->cObj = $cObj ?? GeneralUtility::makeInstance(ContentObjectRenderer::class);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getNumberOfPages(): int
    {
        return (int)ceil(count($this->items) / $this->numberOfItemsPerPage);
    }

    public function getLastModified(): int
    {
        $lastMod = 0;
        foreach ($this->items as $item) {
            if ((int)($item['lastMod'] ?? 0) > $lastMod) {
                $lastMod = (int)$item['lastMod'];
            }
        }

        return $lastMod;
    }

    protected function defineUrl(array $data): array
    {
        return $data;
    }

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
