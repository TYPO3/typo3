<?php
namespace TYPO3\CMS\Form\Domain\Model;

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

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * The ValidationElement Domain Model represents the low-level
 * view on the user submitted data in a flat hierarchy.
 */
class ValidationElement extends AbstractEntity
{
    /**
     * This array holds all the field from the request
     *
     * @var array
     */
    protected $incomingFields;

    /**
     * Return a array with all the fields from the request
     *
     * @return array
     */
    public function getIncomingFields()
    {
        return $this->incomingFields;
    }

    /**
     * Sets a array with all the fields from the request
     *
     * @param array $incomingFields
     * @return void
     */
    public function setIncomingFields($incomingFields = [])
    {
        $this->incomingFields = $incomingFields;
    }

    /**
     * Get a single fields from the request
     *
     * @param string $key
     * @return mixed
     */
    public function getIncomingField($key = '')
    {
        return $this->incomingFields[$key];
    }

    /**
     * Set a single fields from the request
     *
     * @param string $key
     * @param mixed $value
     * @return array
     */
    public function setIncomingField($key = '', $value = null)
    {
        $this->incomingFields[$key] = $value;
    }

    /**
     * Determines whether a field is part of the incoming fields.
     *
     * @param string $key The key of the field to be looked up
     * @return bool
     */
    public function hasIncomingField($key)
    {
        return
            isset($this->incomingFields[$key])
            || array_key_exists($key, $this->incomingFields)
        ;
    }
}
