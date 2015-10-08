<?php
namespace TYPO3\CMS\Core\Type\Icon;

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
 * A class providing constants for icon states
 */
class IconState extends \TYPO3\CMS\Core\Type\Enumeration
{
    const __default = self::STATE_DEFAULT;

    /**
     * @var string the default state identifier
     */
    const STATE_DEFAULT = 'default';

    /**
     * @var string the disabled state identifier
     */
    const STATE_DISABLED = 'disabled';
}
