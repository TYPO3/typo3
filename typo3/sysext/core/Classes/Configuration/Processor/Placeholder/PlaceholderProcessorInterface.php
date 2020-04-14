<?php

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

namespace TYPO3\CMS\Core\Configuration\Processor\Placeholder;

interface PlaceholderProcessorInterface
{
    /**
     * @param string $placeholder
     * @param array $referenceArray
     * @return bool
     */
    public function canProcess(string $placeholder, array $referenceArray): bool;

    /**
     * @param string $value
     * @param array $referenceArray
     * @return mixed
     */
    public function process(string $value, array $referenceArray);
}
