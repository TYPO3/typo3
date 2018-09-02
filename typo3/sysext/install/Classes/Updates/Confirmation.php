<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\Updates;

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

class Confirmation
{
    /**
     * @var bool
     */
    protected $defaultValue = false;

    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $message = '';

    /**
     * @param string $title
     * @param string $message
     * @param bool $defaultValue
     */
    public function __construct(string $title, string $message, bool $defaultValue = false)
    {
        $this->title = $title;
        $this->message = $message;
        $this->defaultValue = $defaultValue;
    }

    /**
     * @return bool
     */
    public function getDefaultValue(): bool
    {
        return $this->defaultValue;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
