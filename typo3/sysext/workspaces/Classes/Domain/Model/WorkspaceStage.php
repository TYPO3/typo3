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

namespace TYPO3\CMS\Workspaces\Domain\Model;

/**
 * Represents a workspace stage. This data object is tailored for the
 * workspaces BE module and is merged from data of the "upper" workspace
 * data object, the stage record and the current backend user.
 *
 * @internal
 */
final readonly class WorkspaceStage
{
    public function __construct(
        public int $uid,
        public bool $isEditStage,
        public bool $isExecuteStage,
        public string $title,
        public bool $isDialogEnabled,
        public bool $isPreselectionChangeable,
        public string $defaultComment,
        public bool $isAllowed,
        public array $allRecipients,
        public array $preselectedRecipients,
    ) {}
}
