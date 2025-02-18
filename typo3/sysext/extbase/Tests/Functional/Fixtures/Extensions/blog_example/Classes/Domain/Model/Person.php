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

namespace TYPO3Tests\BlogExample\Domain\Model;

use TYPO3\CMS\Core\Country\Country;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * A person - acting as author
 */
class Person extends AbstractEntity
{
    protected string $firstname = '';

    protected string $lastname = '';

    protected string $email = '';

    protected ?Country $country = null;

    protected Enum\Salutation $salutation = Enum\Salutation::NONE;

    /**
     * @var ObjectStorage<Tag>
     */
    protected ObjectStorage $tags;

    /**
     * @var ObjectStorage<Tag>
     */
    protected ObjectStorage $tagsSpecial;

    public function __construct(string $firstname = '', string $lastname = '', string $email = '')
    {
        $this->setFirstname($firstname);
        $this->setLastname($lastname);
        $this->setEmail($email);

        $this->tags = new ObjectStorage();
        $this->tagsSpecial = new ObjectStorage();
    }

    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setLastname(string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function getFullName(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getSalutation(): Enum\Salutation
    {
        return $this->salutation;
    }

    public function setSalutation(Enum\Salutation $salutation): void
    {
        $this->salutation = $salutation;
    }

    /**
     * @return ObjectStorage<Tag>
     */
    public function getTags(): ObjectStorage
    {
        return $this->tags;
    }

    /**
     * @param ObjectStorage<Tag> $tags
     */
    public function setTags(ObjectStorage $tags): void
    {
        $this->tags = $tags;
    }

    public function addTag(Tag $tag): void
    {
        $this->tags->attach($tag);
    }

    public function removeTag(Tag $tag): void
    {
        $this->tags->detach($tag);
    }

    /**
     * @return ObjectStorage<Tag>
     */
    public function getTagsSpecial(): ObjectStorage
    {
        return $this->tagsSpecial;
    }

    /**
     * @param ObjectStorage<Tag> $tagsSpecial
     */
    public function setTagsSpecial(ObjectStorage $tagsSpecial): void
    {
        $this->tagsSpecial = $tagsSpecial;
    }

    public function addTagSpecial(Tag $tag): void
    {
        $this->tagsSpecial->attach($tag);
    }

    public function removeTagSpecial(Tag $tag): void
    {
        $this->tagsSpecial->detach($tag);
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): void
    {
        $this->country = $country;
    }
}
