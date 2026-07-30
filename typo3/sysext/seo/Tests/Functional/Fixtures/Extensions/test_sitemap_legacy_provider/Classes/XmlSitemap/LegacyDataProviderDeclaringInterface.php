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

namespace TYPO3Tests\TestSitemapLegacyProvider\XmlSitemap;

use TYPO3\CMS\Seo\XmlSitemap\XmlSitemapDataProviderInterface;

/**
 * A data provider as it is implemented in TYPO3 v14 and below, additionally declaring
 * XmlSitemapDataProviderInterface. Such a class must not be collected as a tagged service, since
 * its constructor arguments can not be autowired.
 *
 * @deprecated since TYPO3 v15.0, will be removed in TYPO3 v16.0.
 */
final class LegacyDataProviderDeclaringInterface extends LegacyDataProvider implements XmlSitemapDataProviderInterface {}
