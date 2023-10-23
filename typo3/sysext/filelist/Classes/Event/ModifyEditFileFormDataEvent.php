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

namespace TYPO3\CMS\Filelist\Event;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * Listeners to this event are be able to modify the form data,
 * used to render the edit file form in the filelist module.
 */
final class ModifyEditFileFormDataEvent
{
    public function __construct(
        private array $formData,
        private readonly FileInterface $file,
        private readonly ServerRequestInterface $request
    ) {}

    public function getFormData(): array
    {
        return $this->formData;
    }

    public function setFormData(array $formData): void
    {
        $this->formData = $formData;
    }

    public function getFile(): FileInterface
    {
        return $this->file;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
