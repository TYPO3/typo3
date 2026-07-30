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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Seo\XmlSitemap\AbstractXmlSitemapDataProvider;

/**
 * A data provider as it is implemented in TYPO3 v14 and below: It only extends the deprecated
 * AbstractXmlSitemapDataProvider.
 *
 * @deprecated since TYPO3 v15.0, will be removed in TYPO3 v16.0.
 */
class LegacyDataProvider extends AbstractXmlSitemapDataProvider
{
    public function __construct(ServerRequestInterface $request, string $key, array $config = [], ?ContentObjectRenderer $cObj = null)
    {
        parent::__construct($request, $key, $config, $cObj);
        $this->numberOfItemsPerPage = 2;
        $this->generateItems();
    }

    private function generateItems(): void
    {
        foreach (['first', 'second', 'third'] as $index => $identifier) {
            $this->items[] = [
                'identifier' => $identifier,
                'lastMod' => 1535655601 + $index,
            ];
        }
    }

    protected function defineUrl(array $data): array
    {
        $data['loc'] = $this->cObj->createUrl([
            'parameter' => (int)$this->config['pageId'],
            'queryParameters' => ['tx_test[item]' => $data['identifier']],
            'forceAbsoluteUrl' => 1,
        ]);
        return $data;
    }
}
