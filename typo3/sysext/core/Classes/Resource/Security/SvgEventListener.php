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

namespace TYPO3\CMS\Core\Resource\Security;

use TYPO3\CMS\Core\Resource\Event\AfterFileContentsSetEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFileAddedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFileReplacedEvent;

class SvgEventListener
{
    /**
     * @var SvgSanitizer
     */
    protected $sanitizer;

    /**
     * @var SvgTypeCheck
     */
    protected $typeCheck;

    public function __construct(SvgSanitizer $sanitizer, SvgTypeCheck $typeCheck)
    {
        $this->sanitizer = $sanitizer;
        $this->typeCheck = $typeCheck;
    }

    public function beforeFileAdded(BeforeFileAddedEvent $event): void
    {
        $filePath = $event->getSourceFilePath();
        if ($this->typeCheck->forFilePath($filePath)) {
            $this->sanitizer->sanitizeFile($filePath);
        }
    }

    public function beforeFileReplaced(BeforeFileReplacedEvent $event): void
    {
        $filePath = $event->getLocalFilePath();
        if ($this->typeCheck->forFilePath($filePath)) {
            $this->sanitizer->sanitizeFile($filePath);
        }
    }

    public function afterFileContentsSet(AfterFileContentsSetEvent $event): void
    {
        $file = $event->getFile();
        if (!$this->typeCheck->forResource($file)) {
            return;
        }
        $content = $event->getContent();
        $sanitizedContent = $this->sanitizer->sanitizeContent($content);
        // cave: setting content will trigger calling this handler again
        // (having custom-flags on `FileInterface` would allow to mark it as "processed")
        if ($sanitizedContent !== $content) {
            $file->setContents($sanitizedContent);
        }
    }
}
