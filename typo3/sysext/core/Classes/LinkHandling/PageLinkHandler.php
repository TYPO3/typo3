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

namespace TYPO3\CMS\Core\LinkHandling;

use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Resolves links to pages and the parameters given
 */
class PageLinkHandler implements LinkHandlingInterface
{
    /**
     * The Base URN for this link handling to act on
     * @var string
     */
    protected $baseUrn = 't3://page';

    /**
     * Returns all valid parameters for linking to a TYPO3 page as a string
     */
    public function asString(array $parameters): string
    {
        $urn = $this->baseUrn . (isset($parameters['pageuid']) ? '?uid=' . $parameters['pageuid'] : '');
        $urn = rtrim($urn, ':');
        // Page type is set and not empty (= "0" in this case means it is not empty)
        if (isset($parameters['pagetype']) && strlen((string)$parameters['pagetype']) > 0) {
            $urn .= '&type=' . $parameters['pagetype'];
        }
        if (!empty($parameters['parameters'])) {
            $urn .= '&' . ltrim($parameters['parameters'], '?&');
        }
        if (!empty($parameters['fragment'])) {
            $urn .= '#' . $parameters['fragment'];
        }

        return $urn;
    }

    /**
     * Returns all relevant information built in the link to a page (see asString())
     */
    public function resolveHandlerData(array $data): array
    {
        $result = [];
        if (isset($data['uid'])) {
            $result['pageuid'] = MathUtility::canBeInterpretedAsInteger($data['uid']) ? (int)$data['uid'] : $data['uid'];
            unset($data['uid']);
        }
        if (isset($data['type'])) {
            $result['pagetype'] = $data['type'];
            unset($data['type']);
        }
        if (!empty($data)) {
            $result['parameters'] = http_build_query($data, '', '&', PHP_QUERY_RFC3986);
        }
        if (empty($result)) {
            $result['pageuid'] = 'current';
        }

        return $result;
    }
}
