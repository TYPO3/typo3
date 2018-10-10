<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Frontend\Utility;

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
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

class CanonicalizationUtility
{
    /**
     * Get all params that are not needed to determine a canonicalized URL
     *
     * The format of the additionalCanonicalizedUrlParameters is:
     * $parameters = [
     *  'foo',
     *  'bar',
     *  'foo[bar]'
     * ]
     *
     * @param int $pageId Id of the page you want to get the excluded params
     * @param array $additionalCanonicalizedUrlParameters Which GET-params should stay besides the params used for cHash calculation
     *
     * @return array
     */
    public static function getParamsToExcludeForCanonicalizedUrl(int $pageId, array $additionalCanonicalizedUrlParameters = []): array
    {
        $cacheHashCalculator = GeneralUtility::makeInstance(CacheHashCalculator::class);

        $GET = ($GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface) ? $GLOBALS['TYPO3_REQUEST']->getQueryParams() : [];
        $GET['id'] = $pageId;

        $queryString = HttpUtility::buildQueryString($GET, '&');
        $cHashArray = $cacheHashCalculator->getRelevantParameters($queryString);

        // By exploding the earlier imploded array, we get the flat array with URL params
        $urlParameters = GeneralUtility::explodeUrl2Array($queryString);

        $paramsToExclude = array_keys(
            array_diff(
                $urlParameters,
                $cHashArray
            )
        );

        return array_diff($paramsToExclude, $additionalCanonicalizedUrlParameters);
    }
}
