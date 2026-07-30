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

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Interface for XmlSitemapDataProviders, which are called by the XmlSitemapRenderer.
 *
 * Implementations are stateless services receiving all runtime information via XmlSitemapRequest,
 * which allows them to use dependency injection for their own dependencies.
 */
#[AutoconfigureTag('seo.xmlsitemap.provider')]
interface XmlSitemapDataProviderInterface
{
    public function getSitemap(XmlSitemapRequest $sitemapRequest): XmlSitemap;
}
