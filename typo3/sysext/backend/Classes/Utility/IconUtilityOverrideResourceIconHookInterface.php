<?php
namespace TYPO3\CMS\Backend\Utility;

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

/**
 * Interface for classes which hook into IconUtility::getSpriteIconForResource()
 *
 * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
 */
interface IconUtilityOverrideResourceIconHookInterface
{
    /**
     * Influence the choice of icon and overlays for a ResourceIcon
     *
     * The $iconName, $options and $overlays are passed as references
     * in order to be modified within the hook
     *
     * @param \TYPO3\CMS\Core\Resource\ResourceInterface $resource
     * @param string $iconName
     * @param array $options
     * @param array $overlays
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function overrideResourceIcon(\TYPO3\CMS\Core\Resource\ResourceInterface $resource, &$iconName, array &$options, array &$overlays);
}
