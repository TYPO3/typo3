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

namespace TYPO3\CMS\Seo\MetaTag;

use TYPO3\CMS\Core\MetaTag\AbstractMetaTagManager;

/**
 * @internal this class is not part of TYPO3's Core API.
 */
class OpenGraphMetaTagManager extends AbstractMetaTagManager
{
    /**
     * The default attribute that defines the name of the property
     *
     * This creates tags like <meta property="" /> by default
     *
     * @var string
     */
    protected $defaultNameAttribute = 'property';

    /**
     * Array of properties that can be handled by this manager
     *
     * @var array
     */
    protected $handledProperties = [
        'og:type' => [],
        'og:title' => [],
        'og:description' => [],
        'og:site_name' => [],
        'og:url' => [],
        'og:audio' => [],
        'og:video' => [],
        'og:determiner' => [],
        'og:locale' => [
            'allowedSubProperties' => [
                'alternate' => [
                    'allowMultipleOccurrences' => true,
                ],
            ],
        ],
        'og:image' => [
            'allowMultipleOccurrences' => true,
            'allowedSubProperties' => [
                'url' => [],
                'secure_url' => [],
                'type' => [],
                'width' => [],
                'height' => [],
                'alt' => [],
            ],
        ],
    ];
}
