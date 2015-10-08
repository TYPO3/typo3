<?php
namespace TYPO3\CMS\Backend\Template\Components;

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
 * Control used by various components
 */
class AbstractControl
{
    /**
     * HTML tag attribute for class
     *
     * @var string
     */
    protected $classes = '';

    /**
     * HTML tag attribute for title
     *
     * @var string
     */
    protected $title = '';

    /**
     * HTML tag attributes for data-*
     * Use key => value pairs
     *
     * @var array
     */
    protected $dataAttributes = [];

    /**
     * HTML tag attribute onClick
     * Outdated, use sparingly
     *
     * @var string
     */
    protected $onClick = '';

    /**
     * Get classes
     *
     * @return string
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * Get Title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get Data attributes
     *
     * @return array
     */
    public function getDataAttributes()
    {
        return $this->dataAttributes;
    }

    /**
     * Get Onclick Attribute
     *
     * @return string
     */
    public function getOnClick()
    {
        return $this->onClick;
    }

    /**
     * Set classes
     *
     * @param string $classes HTML class attribute to set
     *
     * @return $this
     */
    public function setClasses($classes)
    {
        $this->classes = $classes;
        return $this;
    }

    /**
     * Set title attribute
     *
     * @param string $title HTML title attribute to set
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set Data attributes
     *
     * @param array $dataAttributes HTML data attributes to set
     *
     * @return $this
     */
    public function setDataAttributes(array $dataAttributes)
    {
        $this->dataAttributes = $dataAttributes;
        return $this;
    }

    /**
     * Set OnClick
     *
     * @param string $onClick HTML onClick attribute to set
     *
     * @return $this
     */
    public function setOnClick($onClick)
    {
        $this->onClick = $onClick;
        return $this;
    }
}
