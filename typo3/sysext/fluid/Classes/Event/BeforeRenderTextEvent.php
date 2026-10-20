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

use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Schema\Field\InputFieldType;
use TYPO3\CMS\Core\Schema\Field\TextFieldType;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3Fluid\Fluid\Core\Parser\UnsafeHTML;

/**
 * Allows listeners to take over rendering of a validated text field before
 * escaping or rich-text processing. Providing a result stops propagation
 * and skips the ViewHelper's default rendering.
 */
final class BeforeRenderTextEvent implements StoppableEventInterface
{
    private ?UnsafeHTML $renderedText = null;

    public function __construct(
        private readonly RecordInterface|DomainObjectInterface $record,
        private readonly string $table,
        private readonly string $fullType,
        private readonly InputFieldType|TextFieldType $fieldSchema,
        private readonly string $value,
        private readonly ?ServerRequestInterface $request,
    ) {}

    public function getRecord(): RecordInterface|DomainObjectInterface
    {
        return $this->record;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getFullType(): string
    {
        return $this->fullType;
    }

    public function getFieldName(): string
    {
        return $this->fieldSchema->getName();
    }

    public function getFieldSchema(): InputFieldType|TextFieldType
    {
        return $this->fieldSchema;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    public function getRenderedText(): ?UnsafeHTML
    {
        return $this->renderedText;
    }

    /**
     * The result must contain appropriately escaped or sanitized HTML.
     */
    public function setRenderedText(UnsafeHTML $renderedText): void
    {
        $this->renderedText = $renderedText;
    }

    public function isPropagationStopped(): bool
    {
        return $this->renderedText !== null;
    }
}
