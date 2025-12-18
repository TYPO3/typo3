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

namespace TYPO3\CMS\Backend\Form;

use Psr\Http\Message\ServerRequestInterface;

/**
 * This is a helper class to understand when FormEngine should be doing something.
 *
 * @internal do not use this outside of TYPO3, as this will be cleaned up further at a later stage
 */
final readonly class FormAction
{
    private const DOCUMENT_CLOSE_MODE_DEFAULT = 0;
    // works like DOCUMENT_CLOSE_MODE_DEFAULT
    private const DOCUMENT_CLOSE_MODE_REDIRECT = 1;
    public const DOCUMENT_CLOSE_MODE_CLEAR_ALL = 3;

    private function __construct(
        private array $parsedBody,
        public bool $isPostRequest,
        // Close document command. One of the DOCUMENT_CLOSE_MODE_* constants above
        private int $closeDoc,
    ) {}

    public static function createFromRequest(ServerRequestInterface $request): FormAction
    {
        return new self(
            $request->getParsedBody() ?? [],
            $request->getMethod() === 'POST',
            (int)($request->getParsedBody()['closeDoc'] ?? $request->getQueryParams()['closeDoc'] ?? self::DOCUMENT_CLOSE_MODE_DEFAULT)
        );
    }

    /**
     * If true, the processing of incoming data will be performed as if a save-button is pressed.
     * Used in the forms as a hidden field which can be set through
     * JavaScript if the form is somehow submitted by JavaScript.
     */
    public function doSave(): bool
    {
        return ($this->parsedBody['doSave'] ?? false) && $this->isPostRequest;
    }

    /**
     * Is true when
     *  - the incoming data should be persisted
     *  - and the form should be shown for editing
     */
    private function savedok(): bool
    {
        return (bool)($this->parsedBody['_savedok'] ?? false);
    }

    /**
     * Is true when
     *  - the incoming data should be persisted
     *  - and the editing form should not be shown anymore (= redirect to the redirect URL or close screen)
     */
    private function saveandclosedok(): bool
    {
        return (bool)($this->parsedBody['_saveandclosedok'] ?? false);
    }

    /**
     * Is true when
     *  - the incoming data should be persisted
     *  - and the record should be shown in frontend (via new tab)
     *  @todo: WHAT HAPPENS IF I EDIT MULTIPLE RECORDS?
     */
    public function savedokview(): bool
    {
        return ($this->parsedBody['_savedokview'] ?? false) && $this->isPostRequest;
    }

    /**
     * Is true when
     *  - the incoming data should be persisted
     *  - and a new for of the same type should be shown to create the next entry
     * @todo: WHAT HAPPENS IF I EDIT MULTIPLE RECORDS?
     */
    public function savedoknew(): bool
    {
        return (bool)($this->parsedBody['_savedoknew'] ?? false);
    }

    /**
     * Is true when
     *  - the incoming data should be persisted
     *  - and the data should be used to show a form with the same data in it as a new record
     * @todo: WHAT HAPPENS IF I EDIT MULTIPLE RECORDS?
     */
    public function duplicatedoc(): bool
    {
        return (bool)($this->parsedBody['_duplicatedoc'] ?? false);
    }

    public function shouldProcessData(): bool
    {
        if (!$this->isPostRequest) {
            return false;
        }
        return $this->doSave() || $this->savedok() || $this->saveandclosedok() || $this->savedokview() || $this->savedoknew() || $this->duplicatedoc();
    }

    public function shouldHandleDocumentClosing(): bool
    {
        return $this->closeDoc > self::DOCUMENT_CLOSE_MODE_DEFAULT;
    }

    public function shouldCloseAfterSave(): bool
    {
        return $this->closeDoc < self::DOCUMENT_CLOSE_MODE_DEFAULT || $this->saveandclosedok();
    }

    public function getAbsoluteCloseDocValue(): int
    {
        return abs($this->closeDoc);
    }

    /**
     * If mode is NOT set (means 0) OR set to 1, then make a header location redirect to $this->retUrl
     */
    public function shouldCloseWithARedirect(): bool
    {
        return $this->closeDoc === self::DOCUMENT_CLOSE_MODE_DEFAULT || $this->closeDoc === self::DOCUMENT_CLOSE_MODE_REDIRECT;
    }
}
