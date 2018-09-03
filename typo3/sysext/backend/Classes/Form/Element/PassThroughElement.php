<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Form\Element;

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
 * PassThroughElement is the dummy element for type="passthrough".
 * It does not render anything.
 */
class PassThroughElement extends AbstractFormElement
{
    /**
     * Return the empty initialized result array
     *
     * @return array
     */
    public function render(): array
    {
        return $this->initializeResultArray();
    }
}
