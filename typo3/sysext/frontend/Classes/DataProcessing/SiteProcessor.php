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

namespace TYPO3\CMS\Frontend\DataProcessing;

use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Fetch the site object containing all information about the current site
 *
 * Example TypoScript configuration:
 *
 * 10 = TYPO3\CMS\Frontend\DataProcessing\SiteProcessor
 * 10 {
 *   as = site
 * }
 *
 * where "as" names the variable containing the site object
 */
class SiteProcessor implements DataProcessorInterface
{
    protected ?TypoScriptFrontendController $tsfe;

    public function __construct(TypoScriptFrontendController $tsfe = null)
    {
        $this->tsfe = $tsfe ?? $GLOBALS['TSFE'] ?? null;
    }

    /**
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array $contentObjectConfiguration The configuration of Content Object
     * @param array $processorConfiguration The configuration of this processor
     * @param array $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array the processed data as key/value store
     */
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData): array
    {
        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, 'site');
        $processedData[$targetVariableName] = $this->getCurrentSite();
        return $processedData;
    }

    /**
     * Returns the currently configured "site" if a site is configured (= resolved) in the current request.
     *
     * @return Site|null
     */
    protected function getCurrentSite(): ?Site
    {
        if ($this->tsfe instanceof TypoScriptFrontendController) {
            return $this->tsfe->getSite();
        }
        return null;
    }
}
