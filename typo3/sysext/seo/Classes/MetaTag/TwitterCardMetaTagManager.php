<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Seo\MetaTag;

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

use TYPO3\CMS\Core\MetaTag\AbstractMetaTagManager;

/**
 * @internal this class is not part of TYPO3's Core API.
 */
class TwitterCardMetaTagManager extends AbstractMetaTagManager
{
    /**
     * Array of properties that can be handled by this manager
     *
     * @var array
     */
    protected $handledProperties = [
        'twitter:card' => [],
        'twitter:site' => [
            'allowedSubProperties' => [
                'id' => [],
            ]
        ],
        'twitter:creator' => [
            'allowedSubProperties' => [
                'id' => [],
            ]
        ],
        'twitter:description' => [],
        'twitter:title' => [],
        'twitter:image' => [
            'allowedSubProperties' => [
                'alt' => [],
            ]
        ],
        'twitter:player' => [
            'allowedSubProperties' => [
                'width' => [],
                'height' => [],
                'stream' => [],
            ]
        ],
        'twitter:app' => [
            'allowedSubProperties' => [
                'name:iphone' => [],
                'id:iphone' => [],
                'url:iphone' => [],
                'name:ipad' => [],
                'id:ipad' => [],
                'url:ipad' => [],
                'name:googleplay' => [],
                'id:googleplay' => [],
                'url:googleplay' => [],
            ]
        ],
    ];
}
