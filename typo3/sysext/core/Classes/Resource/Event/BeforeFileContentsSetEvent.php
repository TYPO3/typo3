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

namespace TYPO3\CMS\Core\Resource\Event;

use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * This event is fired before the contents of a file gets set / replaced.
 *
 * This allows to further analyze or modify the content of a file before it is written by the driver.
 */
final class BeforeFileContentsSetEvent
{
    /**
     * @var FileInterface
     */
    private $file;

    /**
     * @var string
     */
    private $content;

    public function __construct(FileInterface $file, string $content)
    {
        $this->file = $file;
        $this->content = $content;
    }

    public function getFile(): FileInterface
    {
        return $this->file;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }
}
