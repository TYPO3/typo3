<?php
namespace TYPO3\CMS\Form\Utility;

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

class ElementCounter implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var int
     */
    protected $elementCounter = 0;

    /**
     * Raise the element counter by one
     *
     * @return int
     */
    public function getElementId()
    {
        $this->elementCounter++;
        return $this->elementCounter;
    }
}
