<?php
namespace TYPO3\CMS\Form\Domain\Model\Json;

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
 * JSON container abstract
 */
class ContainerJsonElement extends \TYPO3\CMS\Form\Domain\Model\Json\AbstractJsonElement
{
    /**
     * The items within this container
     *
     * @var array
     */
    public $elementContainer = [
        'hasDragAndDrop' => true,
        'items' => []
    ];

    /**
     * Add an element to this container
     *
     * @param \TYPO3\CMS\Form\Domain\Model\Json\AbstractJsonElement $element The element to add
     * @return void
     */
    public function addElement(\TYPO3\CMS\Form\Domain\Model\Json\AbstractJsonElement $element)
    {
        $this->elementContainer['items'][] = $element;
    }
}
