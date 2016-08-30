<?php
namespace TYPO3\CMS\Lang\Domain\Model;

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
 * Language model
 */
class Language extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var string
     */
    protected $locale = '';

    /**
     * @var string
     */
    protected $label = '';

    /**
     * @var bool
     */
    protected $selected = false;

    /**
     * @var int
     */
    protected $lastUpdate;

    /**
     * Constructor of the language model
     *
     * @param string $locale
     * @param string $label
     * @param bool $selected
     * @param int $lastUpdate
     */
    public function __construct($locale = '', $label = '', $selected = false, $lastUpdate = null)
    {
        $this->locale = $locale;
        $this->label = $label;
        $this->selected = $selected;
        $this->lastUpdate = $lastUpdate;
    }

    /**
     * @return int
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * @param int $lastUpdate
     */
    public function setLastUpdate($lastUpdate)
    {
        $this->lastUpdate = $lastUpdate;
    }

    /**
     * Setter for the language
     *
     * @param string $language the label of the language
     * @return void
     */
    public function setLabel($language)
    {
        $this->label = $language;
    }

    /**
     * Getter for the language
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Setter for the locale
     *
     * @param string $locale the locale for the language like da, nl or de
     * @return void
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Getter for the locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Setter for the selected
     *
     * @param bool $selected whether the language is available or not
     * @return void
     */
    public function setSelected($selected)
    {
        $this->selected = (bool)$selected;
    }

    /**
     * Getter for the selected
     *
     * @return bool
     */
    public function getSelected()
    {
        return $this->selected;
    }

    /**
     * Returns an array represetation of current model
     *
     * @return array The properties
     */
    public function toArray()
    {
        return [
            'locale'   => $this->getLocale(),
            'label' => $this->getLabel(),
            'selected' => $this->getSelected(),
            'lastUpdate' => $this->getLastUpdate(),
        ];
    }
}
