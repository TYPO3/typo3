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

namespace TYPO3\CMS\Core\Http\Client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
class GuzzleClientFactory
{
    /**
     * Creates the client to do requests
     * @return ClientInterface
     */
    public static function getClient(): ClientInterface
    {
        $httpOptions = $GLOBALS['TYPO3_CONF_VARS']['HTTP'];
        $httpOptions['verify'] = filter_var($httpOptions['verify'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $httpOptions['verify'];

        if (isset($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']) && is_array($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'])) {
            $stack = HandlerStack::create();
            foreach ($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] ?? [] as $name => $handler) {
                $stack->push($handler, (string)$name);
            }
            $httpOptions['handler'] = $stack;
        }

        return GeneralUtility::makeInstance(Client::class, $httpOptions);
    }
}
