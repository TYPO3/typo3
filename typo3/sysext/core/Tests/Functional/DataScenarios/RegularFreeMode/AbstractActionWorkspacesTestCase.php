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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\RegularFreeMode;

abstract class AbstractActionWorkspacesTestCase extends AbstractActionTestCase
{
    protected const VALUE_WorkspaceId = 1;

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefaultWorkspaces.csv';

    protected array $coreExtensionsToLoad = ['workspaces'];

    /**
     * This is an additional workspace related scenario derived from localizeContentAfterMovedContent(), where
     * the moving content element around is done in live only localizations are done in workspace.
     *
     * @see localizeContentAfterMovedContent
     */
    public function localizeContentAfterMovedInLiveContent(): void
    {
        $this->setWorkspaceId(0);
        // Default language element 310 on page 90 that has two 'free mode' localizations is moved to page 89.
        // Note the two localizations are NOT moved along with the default language element, due to free mode.
        // Note l10n_source of first localization 311 is kept and still points to 310, even though 310 is moved to different page.
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFreeMode, self::VALUE_PageId);
        $this->setWorkspaceId(self::VALUE_WorkspaceId);
        // Create new record after (relative to) previously moved one.
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, -self::VALUE_ContentIdFreeMode, ['header' => 'Testing #1']);
        $this->recordIds['newContentIdLast'] = $newTableIds[self::TABLE_Content][0];
        // Localize this new record
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, $newTableIds[self::TABLE_Content][0], self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][$newTableIds[self::TABLE_Content][0]];
    }
}
