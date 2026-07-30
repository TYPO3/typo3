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

/**
 * The result of a XmlSitemapDataProvider: The items of the requested page, and the information
 * needed to render the sitemap index (last modification date and the total number of pages).
 *
 * The items are resolved when they are requested, since the sitemap index needs the meta
 * information only and therefore never renders any item.
 */
final readonly class XmlSitemap
{
    public const DEFAULT_ITEMS_PER_PAGE = 1000;

    private function __construct(
        private \Closure $itemsResolver,
        private int $lastModified,
        private int $numberOfPages,
    ) {}

    /**
     * Creates a sitemap for a single page out of all items a provider has collected. The optional
     * item mapper is applied to the items of the requested page only, which allows providers to
     * generate URLs for those items exclusively.
     *
     * @param \Closure(array): array|null $itemMapper
     */
    public static function forPage(
        array $allItems,
        int $page = 0,
        ?\Closure $itemMapper = null,
        int $itemsPerPage = self::DEFAULT_ITEMS_PER_PAGE,
    ): self {
        return new self(
            static function () use ($allItems, $page, $itemMapper, $itemsPerPage): array {
                $items = array_slice($allItems, $page * $itemsPerPage, $itemsPerPage);
                return $itemMapper === null ? $items : array_map($itemMapper, $items);
            },
            self::resolveLastModified($allItems),
            (int)ceil(count($allItems) / $itemsPerPage),
        );
    }

    /**
     * Creates a sitemap for providers taking care of paging themselves, for instance by limiting
     * their database query to the items of the requested page.
     *
     * @param \Closure(): array $itemsResolver Resolves the items of the requested page
     */
    public static function create(\Closure $itemsResolver, int $lastModified, int $numberOfPages): self
    {
        return new self($itemsResolver, $lastModified, $numberOfPages);
    }

    public function getItems(): array
    {
        return ($this->itemsResolver)();
    }

    public function getLastModified(): int
    {
        return $this->lastModified;
    }

    public function getNumberOfPages(): int
    {
        return $this->numberOfPages;
    }

    private static function resolveLastModified(array $items): int
    {
        $lastModified = 0;
        foreach ($items as $item) {
            $lastModified = max($lastModified, (int)($item['lastMod'] ?? 0));
        }
        return $lastModified;
    }
}
