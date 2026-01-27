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

namespace TYPO3\CMS\Fluid\Event;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Domain\RecordInterface;

/**
 * Event to modify the rendered record output.
 * This can be used to alter the final HTML of a record (eg. content element),
 * for example to render a debug wrapper around it.
 */
final class ModifyRenderedRecordEvent
{
    public function __construct(
        private string $renderedRecord,
        private readonly RecordInterface $record,
        private readonly ServerRequestInterface $request,
    ) {}

    public function getRenderedRecord(): string
    {
        return $this->renderedRecord;
    }

    /**
     * Set the rendered record's HTML.
     * Make sure to return escaped content if necessary.
     */
    public function setRenderedRecord(string $renderedRecord): void
    {
        $this->renderedRecord = $renderedRecord;
    }

    public function getRecord(): RecordInterface
    {
        return $this->record;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
