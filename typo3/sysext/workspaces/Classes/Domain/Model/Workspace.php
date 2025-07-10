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
 * Represents a workspace definition tailored for the TYPO3 Workspaces BE module.
 *
 * @internal
 */
final readonly class Workspace
{
    public function __construct(
        public int $uid,
        public string $title,
        public string $owners,
        public string $members,
        public bool $isEditStageDialogEnabled,
        public bool $isEditStagePreselectionChangeable,
        public bool $areEditStageOwnersPreselected,
        public bool $areEditStageMembersPreselected,
        public string $editStageDefaultRecipients,
        public bool $isPublishStageDialogEnabled,
        public bool $isPublishStagePreselectionChangeable,
        public bool $arePublishStageOwnersPreselected,
        public bool $arePublishStageMembersPreselected,
        public string $publishStageDefaultRecipients,
        public bool $isExecuteStageDialogEnabled,
        public bool $isExecuteStagePreselectionChangeable,
        public bool $areExecuteStageOwnersPreselected,
        public bool $areExecuteStageMembersPreselected,
        public string $executeStageDefaultRecipients,
    ) {}
}
