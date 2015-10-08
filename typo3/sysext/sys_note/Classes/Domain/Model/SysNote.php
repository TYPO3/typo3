<?php
namespace TYPO3\CMS\SysNote\Domain\Model;

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
 * SysNote model
 */
class SysNote extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var \DateTime
     */
    protected $creationDate;

    /**
     * @var \DateTime
     */
    protected $modificationDate;

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\BackendUser
     */
    protected $author;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var bool
     */
    protected $personal;

    /**
     * @var int
     */
    protected $category;

    /**
     * @return \DateTime $creationDate
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param \DateTime $creationDate
     * @return void
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return \DateTime $modificationDate
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param \DateTime $modificationDate
     * @return void
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Domain\Model\BackendUser $author
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Domain\Model\BackendUser $author
     * @return void
     */
    public function setAuthor(\TYPO3\CMS\Extbase\Domain\Model\BackendUser $author)
    {
        $this->author = $author;
    }

    /**
     * @return string $subject
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     * @return void
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return string $message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return void
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return bool $personal
     */
    public function getPersonal()
    {
        return $this->personal;
    }

    /**
     * @param bool $personal
     * @return void
     */
    public function setPersonal($personal)
    {
        $this->personal = $personal;
    }

    /**
     * @return int $category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param int $category
     * @return void
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }
}
