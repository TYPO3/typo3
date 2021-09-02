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

use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Resource\ResourceInterface;

/**
 * Event that is triggered when a file should be dumped to the browser, allowing to perform custom
 * security/access checks when accessing a file through a direct link, and returning an alternative
 * Response.
 *
 * It is also possible to replace the file during this event, but not setting a response.
 *
 * As soon as a custom Response is added, the propagation is stopped.
 */
final class ModifyFileDumpEvent implements StoppableEventInterface
{
    private ResourceInterface $file;
    private ServerRequestInterface $request;
    private ?ResponseInterface $response = null;

    public function __construct(ResourceInterface $file, ServerRequestInterface $request)
    {
        $this->file = $file;
        $this->request = $request;
    }

    public function getFile(): ResourceInterface
    {
        return $this->file;
    }

    public function setFile(ResourceInterface $file): void
    {
        $this->file = $file;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function isPropagationStopped(): bool
    {
        return $this->response !== null;
    }
}
