<?php
namespace TYPO3\CMS\Documentation\Domain\Model;

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
 * An extension helper model to be used in ext:documentation context
 *
 * @entity
 */
class DocumentTranslation extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * language
     * 2 char language identifier (or "" for default)
     *
     * @var string
     * @validate NotEmpty
     */
    protected $language;

    /**
     * title
     *
     * @var string
     */
    protected $title;

    /**
     * description
     *
     * @var string
     */
    protected $description;

    /**
     * formats
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Documentation\Domain\Model\DocumentFormat>
     */
    protected $formats;

    /**
     * Default constructor.
     */
    public function __construct()
    {
        // Do not remove the next line: It would break the functionality
        $this->initStorageObjects();
    }

    /**
     * Initializes all ObjectStorage properties.
     *
     * @return void
     */
    protected function initStorageObjects()
    {
        /**
         * Do not modify this method!
         * It will be rewritten on each save in the extension builder
         * You may modify the constructor of this class instead
         */
        $this->formats = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Returns the language.
     *
     * @return string $language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Sets the language.
     *
     * @param string $language
     * @return DocumentTranslation
     */
    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Returns the title.
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the title.
     *
     * @param string $title
     * @return DocumentTranslation
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Returns the description.
     *
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the description.
     *
     * @param string $description
     * @return DocumentTranslation
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Adds a documentation format.
     *
     * @param \TYPO3\CMS\Documentation\Domain\Model\DocumentFormat $format
     * @return DocumentTranslation
     */
    public function addFormat(\TYPO3\CMS\Documentation\Domain\Model\DocumentFormat $format)
    {
        $this->formats->attach($format);
        return $this;
    }

    /**
     * Removes a documentation format.
     *
     * @param \TYPO3\CMS\Documentation\Domain\Model\DocumentFormat $formatToRemove The DocumentFormat to be removed
     * @return DocumentTranslation
     */
    public function removeFormat(\TYPO3\CMS\Documentation\Domain\Model\DocumentFormat $formatToRemove)
    {
        $this->formats->detach($formatToRemove);
        return $this;
    }

    /**
     * Returns the formats.
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Documentation\Domain\Model\DocumentFormat> $formats
     */
    public function getFormats()
    {
        return $this->formats;
    }

    /**
     * Sets the formats.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Documentation\Domain\Model\DocumentFormat> $formats
     * @return DocumentTranslation
     */
    public function setFormats(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $formats)
    {
        $this->formats = $formats;
        return $this;
    }
}
