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
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Interface for XmlSitemapDataProviders containing the methods that are called by the XmlSitemapRenderer
 */
interface XmlSitemapDataProviderInterface
{
    public function __construct(ServerRequestInterface $request, string $name, array $config = [], ContentObjectRenderer $cObj = null);
    public function getKey(): string;
    public function getItems(): array;
    public function getLastModified(): int;
    public function getNumberOfPages(): int;
}
