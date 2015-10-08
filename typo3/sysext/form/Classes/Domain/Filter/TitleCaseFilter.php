<?php
namespace TYPO3\CMS\Form\Domain\Filter;

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
 * Title filter
 */
class TitleCaseFilter extends AbstractFilter implements FilterInterface
{
    /**
     * Convert alphabetic characters to title case
     *
     * @param string $value
     * @return string
     */
    public function filter($value)
    {
        $lower = $this->convertCase($value, 'toLower');
        return ucwords($lower);
    }
}
