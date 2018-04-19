<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\MetaTag;

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

class Html5MetaTagManager extends AbstractMetaTagManager
{
    /**
     * Array of properties that can be handled by this manager
     *
     * @var array
     */
    protected $handledProperties = [
        'application-name' => [],
        'author' => [],
        'description' => [],
        'generator' => [],
        'keywords' => [],
        'referrer' => [],
        'content-language' => [
            'nameAttribute' => 'http-equiv'
        ],
        'content-type' => [
            'nameAttribute' => 'http-equiv'
        ],
        'default-style' => [
            'nameAttribute' => 'http-equiv'
        ],
        'refresh' => [
            'nameAttribute' => 'http-equiv'
        ],
        'set-cookie' => [
            'nameAttribute' => 'http-equiv'
        ],
        'content-security-policy' => [
            'nameAttribute' => 'http-equiv'
        ],
        'viewport' => [],
        'robots' => [],
        'expires' => [
            'nameAttribute' => 'http-equiv'
        ],
        'cache-control' => [
            'nameAttribute' => 'http-equiv'
        ],
        'pragma' => [
            'nameAttribute' => 'http-equiv'
        ]
    ];
}
