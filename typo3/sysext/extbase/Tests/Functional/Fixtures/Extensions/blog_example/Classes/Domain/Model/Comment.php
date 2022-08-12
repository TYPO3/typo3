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

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * A blog post comment
 */
class Comment extends AbstractEntity
{
    protected \DateTime $date;

    /**
     * @Extbase\Validate("NotEmpty")
     */
    protected string $author = '';

    /**
     * @Extbase\Validate("EmailAddress")
     */
    protected string $email = '';

    /**
     * @Extbase\Validate("StringLength", options={"maximum": 500})
     */
    protected string $content = '';

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * Sets the author's email for this comment
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * Getter for author's email
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Returns this comment as a formatted string
     */
    public function __toString(): string
    {
        return $this->author . ' (' . $this->email . ') said on ' . $this->date->format('Y-m-d') . ':' . chr(10) .
            $this->content . chr(10);
    }
}
