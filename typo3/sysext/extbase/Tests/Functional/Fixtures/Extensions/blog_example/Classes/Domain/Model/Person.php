<?php

declare(strict_types=1);

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

namespace ExtbaseTeam\BlogExample\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * A person - acting as author
 */
class Person extends AbstractEntity
{
    /**
     * @var string
     */
    protected $firstname = '';

    /**
     * @var string
     */
    protected $lastname = '';

    /**
     * @var string
     */
    protected $email = '';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\ExtbaseTeam\BlogExample\Domain\Model\Tag>
     */
    protected $tags;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\ExtbaseTeam\BlogExample\Domain\Model\Tag>
     */
    protected $tagsSpecial;

    /**
     * Constructs a new Person
     */
    public function __construct($firstname, $lastname, $email)
    {
        $this->setFirstname($firstname);
        $this->setLastname($lastname);
        $this->setEmail($email);

        $this->tags = new ObjectStorage();
        $this->tagsSpecial = new ObjectStorage();
    }

    /**
     * Sets this persons's firstname
     *
     * @param string $firstname The person's firstname
     */
    public function setFirstname($firstname): void
    {
        $this->firstname = $firstname;
    }

    /**
     * Returns the person's firstname
     *
     * @return string The persons's firstname
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * Sets this persons's lastname
     *
     * @param string $lastname The person's lastname
     */
    public function setLastname($lastname): void
    {
        $this->lastname = $lastname;
    }

    /**
     * Returns the person's lastname
     *
     * @return string The persons's lastname
     */
    public function getLastname(): string
    {
        return $this->lastname;
    }

    /**
     * Returns the person's full name
     *
     * @return string The persons's lastname
     */
    public function getFullName(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    /**
     * Sets this persons's email address
     *
     * @param string $email The person's email address
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * Returns the person's email address
     *
     * @return string The persons's email address
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage|\ExtbaseTeam\BlogExample\Domain\Model\Tag[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $tags
     */
    public function setTags(ObjectStorage $tags): void
    {
        $this->tags = $tags;
    }

    /**
     * @param Tag $tag
     */
    public function addTag(Tag $tag): void
    {
        $this->tags->attach($tag);
    }

    /**
     * @param Tag $tag
     */
    public function removeTag(Tag $tag): void
    {
        $this->tags->detach($tag);
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage|\ExtbaseTeam\BlogExample\Domain\Model\Tag[]
     */
    public function getTagsSpecial()
    {
        return $this->tagsSpecial;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $tagsSpecial
     */
    public function setTagsSpecial(ObjectStorage $tagsSpecial): void
    {
        $this->tagsSpecial = $tagsSpecial;
    }

    /**
     * @param Tag $tag
     */
    public function addTagSpecial(Tag $tag): void
    {
        $this->tagsSpecial->attach($tag);
    }

    /**
     * @param Tag $tag
     */
    public function removeTagSpecial(Tag $tag): void
    {
        $this->tagsSpecial->detach($tag);
    }
}
