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
    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * @var string
     * Extbase\Validate("NotEmpty")
     */
    protected $author = '';

    /**
     * @var string
     * @Extbase\Validate("EmailAddress")
     */
    protected $email = '';

    /**
     * @var string
     * @Extbase\Validate("StringLength", options={"maximum": 500})
     */
    protected $content = '';

    /**
     * Constructs this post
     */
    public function __construct()
    {
        $this->date = new \DateTime();
    }

    /**
     * Setter for date
     *
     * @param \DateTime $date
     */
    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * Getter for date
     *
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * Sets the author for this comment
     *
     * @param string $author
     */
    public function setAuthor($author): void
    {
        $this->author = $author;
    }

    /**
     * Getter for author
     *
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * Sets the authors email for this comment
     *
     * @param string $email email of the author
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * Getter for authors email
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Sets the content for this comment
     *
     * @param string $content
     */
    public function setContent($content): void
    {
        $this->content = $content;
    }

    /**
     * Getter for content
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Returns this comment as a formatted string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->author . ' (' . $this->email . ') said on ' . $this->date->format('Y-m-d') . ':' . chr(10) .
            $this->content . chr(10);
    }
}
