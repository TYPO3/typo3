<?php
namespace ExtbaseTeam\BlogExample\Domain\Model;

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
 * A blog post comment
 */
class Comment extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * @var string
     * @validate NotEmpty
     */
    protected $author = '';

    /**
     * @var string
     * @validate EmailAddress
     */
    protected $email = '';

    /**
     * @var string
     * @validate StringLength(maximum = 500)
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
     * @return void
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * Getter for date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Sets the author for this comment
     *
     * @param string $author
     * @return void
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * Getter for author
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Sets the authors email for this comment
     *
     * @param string $email email of the author
     * @return void
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Getter for authors email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Sets the content for this comment
     *
     * @param string $content
     * @return void
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Getter for content
     *
     * @return string
     */
    public function getContent()
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
