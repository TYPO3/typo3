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

namespace TYPO3\CMS\Backend\Dto;

use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * All information that is need to edit a single record.
 *
 * This is mainly used as DTO within FormEngine / EditDocumentController.
 *
 * @internal not part of TYPO3 Core API
 */
final readonly class FormElementData
{
    public function __construct(
        /**  Is loaded with the "title" of the currently "open document"
         * used for the open document toolbar */
        public string $title,
        public string $table,
        public int|string $uid,
        public int $pid,
        public array $record,
        public int $viewId,
        public string $command,
        private int $userPermissionOnPage,
    ) {}

    /**
     * Determine if delete button can be shown
     */
    public function hasDeleteAccess(): bool
    {
        $permission = new Permission($this->userPermissionOnPage);
        return $permission->get($this->table ? Permission::PAGE_DELETE : Permission::CONTENT_EDIT);
    }

    /**
     * True if a record has been saved
     */
    public function isSavedRecord(): bool
    {
        return $this->command !== 'new' && $this->table !== '' && MathUtility::canBeInterpretedAsInteger($this->uid);
    }
}
