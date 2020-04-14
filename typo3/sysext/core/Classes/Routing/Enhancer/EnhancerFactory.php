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

namespace TYPO3\CMS\Core\Routing\Enhancer;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Creates enhancers
 */
class EnhancerFactory
{
    /**
     * @var array of all class names that need to be EnhancerInterfaces when instantiated.
     */
    protected $availableEnhancers;

    /**
     * EnhancerFactory constructor.
     */
    public function __construct()
    {
        $this->availableEnhancers = $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['enhancers'] ?? [];
    }

    /**
     * @param string $type
     * @param array $settings
     * @return EnhancerInterface
     * @throws \InvalidArgumentException
     * @throws \OutOfRangeException
     */
    public function create(string $type, array $settings): EnhancerInterface
    {
        if (empty($type)) {
            throw new \InvalidArgumentException(
                'Enhancer type cannot be empty',
                1537298284
            );
        }
        if (!isset($this->availableEnhancers[$type])) {
            throw new \OutOfRangeException(
                sprintf('No enhancer found for %s', $type),
                1537277222
            );
        }
        unset($settings['type']);
        $className = $this->availableEnhancers[$type];
        /** @var EnhancerInterface $enhancer */
        $enhancer = GeneralUtility::makeInstance($className, $settings);
        return $enhancer;
    }
}
