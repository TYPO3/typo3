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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\FAL;

abstract class AbstractActionWorkspacesTestCase extends AbstractActionTestCase
{
    protected const VALUE_WorkspaceId = 1;

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefaultWorkspaces.csv';

    protected array $coreExtensionsToLoad = ['workspaces'];

    public function localizeLiveModifyWsDefaultLang(): void
    {
        // Localize page and tt_content in live, so we have a localized parent tt_content plus it's children in live.
        $this->setWorkspaceId(0);
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        // Change the default language element in workspaces, which will create overlays and inline children for localized element, too.
        $this->setWorkspaceId(static::VALUE_WorkspaceId);
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, ['header' => 'Testing #1']);
    }

    public function localizeLiveModifyWsLocalization(): void
    {
        // Localize page and tt_content in live, so we have a localized parent tt_content plus it's children in live.
        $this->setWorkspaceId(0);
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $recordIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedLiveContentId'] = $recordIds['tt_content'][self::VALUE_ContentIdLast];
        // Change the localized element in workspaces.
        $this->setWorkspaceId(static::VALUE_WorkspaceId);
        $this->actionService->modifyRecord(self::TABLE_Content, $this->recordIds['localizedLiveContentId'], ['header' => 'Testing #1']);
    }

    public function localizeLiveModifyWsLocalizationAddLive(): void
    {
        // Localize page and tt_content in live, so we have a localized parent tt_content plus it's children in live.
        $this->setWorkspaceId(0);
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $recordIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedLiveContentId'] = $recordIds['tt_content'][self::VALUE_ContentIdLast];
        // Change the localized element in workspaces.
        $this->setWorkspaceId(static::VALUE_WorkspaceId);
        $this->actionService->modifyRecord(self::TABLE_Content, $this->recordIds['localizedLiveContentId'], ['header' => 'Testing #1']);
        // In addition to localizeLiveModifyWsLocalization(), add another image to Live default language record.
        $this->setWorkspaceId(0);
        // @todo: It would be better to not re-use sys_file 1 here, but to have a third image in the import pool that can be attached here.
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['uid' => self::VALUE_ContentIdLast, self::FIELD_ContentImage => self::VALUE_FileReferenceContentLastFileLast . ',' . self::VALUE_FileReferenceContentLastFileFirst . ',__nextUid'],
                self::TABLE_FileReference => ['uid' => '__NEW', 'title' => 'Image #3', self::FIELD_FileReferenceImage => self::VALUE_FileIdFirst],
            ]
        );
        $this->setWorkspaceId(static::VALUE_WorkspaceId);
    }

    public function localizeLiveModifyWsLocalizationAddLiveWsSync(): void
    {
        // Localize page and tt_content in live, so we have a localized parent tt_content plus it's children in live.
        $this->setWorkspaceId(0);
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $recordIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedLiveContentId'] = $recordIds['tt_content'][self::VALUE_ContentIdLast];
        // Change the localized element in workspaces.
        $this->setWorkspaceId(static::VALUE_WorkspaceId);
        $this->actionService->modifyRecord(self::TABLE_Content, $this->recordIds['localizedLiveContentId'], ['header' => 'Testing #1']);
        // In addition to localizeLiveModifyWsLocalization(), add another image to Live default language record.
        $this->setWorkspaceId(0);
        // @todo: It would be better to not re-use sys_file 1 here, but to have a third image in the import pool that can be attached here.
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['uid' => self::VALUE_ContentIdLast, self::FIELD_ContentImage => self::VALUE_FileReferenceContentLastFileLast . ',' . self::VALUE_FileReferenceContentLastFileFirst . ',__nextUid'],
                self::TABLE_FileReference => ['uid' => '__NEW', 'title' => 'Image #3', self::FIELD_FileReferenceImage => self::VALUE_FileIdFirst],
            ]
        );
        // In addition to localizeLiveModifyWsLocalizationAddLive(), "synchronize" the new live default language image to the localized content element in workspaces.
        $this->setWorkspaceId(static::VALUE_WorkspaceId);
        $this->actionService->invoke(
            [],
            [
                'tt_content' => [
                    $this->recordIds['localizedLiveContentId'] => [
                        'inlineLocalizeSynchronize' => [
                            'field' => 'image',
                            'language' => 1,
                            'ids' => [
                                // Hardcoded source uid here since above modifyRecords() does not return the uid of the new attached image.
                                134,
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    public function modifyContentLocalize(): void
    {
        // Localize page so we can localize content elements later.
        $this->setWorkspaceId(0);
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        // Modify the content element in workspaces to create a workspace overlay of this one, including overlays of attached images
        $this->setWorkspaceId(static::VALUE_WorkspaceId);
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, ['header' => 'Testing #1']);
        // Now localize that default language content element in workspace
        // Note we're using the live uid as source here, which is what page module translation wizard and list module submit to DH as well
        $recordIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedWsContentId'] = $recordIds['tt_content'][self::VALUE_ContentIdLast];
    }

    public function modifyContentLocalizeAddDefaultLangRelation(): void
    {
        // Localize page so we can localize content elements later.
        $this->setWorkspaceId(0);
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        // Modify the content element in workspaces to create a workspace overlay of this one, including overlays of attached images
        $this->setWorkspaceId(static::VALUE_WorkspaceId);
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, ['header' => 'Testing #1']);
        // Now localize that default language content element in workspace
        // Note we're using the live uid as source here, which is what page module translation wizard and list module submit to DH as well
        $recordIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedWsContentId'] = $recordIds['tt_content'][self::VALUE_ContentIdLast];
        // In addition to modifyContentLocalize(), add an image to the default language content element in workspaces.
        // @todo: It would be better to not re-use sys_file 1 here, but to have a third image in the import pool that can be attached here.
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['uid' => self::VALUE_ContentIdLast, self::FIELD_ContentImage => self::VALUE_FileReferenceContentLastFileLast . ',' . self::VALUE_FileReferenceContentLastFileFirst . ',__nextUid'],
                self::TABLE_FileReference => ['uid' => '__NEW', 'title' => 'Image #3', self::FIELD_FileReferenceImage => self::VALUE_FileIdFirst],
            ]
        );
    }

    public function modifyContentLocalizeAddDefaultLangRelationSynchronize(): void
    {
        // Localize page so we can localize content elements later.
        $this->setWorkspaceId(0);
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        // Modify the content element in workspaces to create a workspace overlay of this one, including overlays of attached images
        $this->setWorkspaceId(static::VALUE_WorkspaceId);
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, ['header' => 'Testing #1']);
        // Now localize that default language content element in workspace
        // Note we're using the live uid as source here, which is what page module translation wizard and list module submit to DH as well
        $recordIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedWsContentId'] = $recordIds['tt_content'][self::VALUE_ContentIdLast];
        // In addition to modifyContentLocalize(), add an image to the default language content element in workspaces.
        // @todo: It would be better to not re-use sys_file 1 here, but to have a third image in the import pool that can be attached here.
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['uid' => self::VALUE_ContentIdLast, self::FIELD_ContentImage => self::VALUE_FileReferenceContentLastFileLast . ',' . self::VALUE_FileReferenceContentLastFileFirst . ',__nextUid'],
                self::TABLE_FileReference => ['uid' => '__NEW', 'title' => 'Image #3', self::FIELD_FileReferenceImage => self::VALUE_FileIdFirst],
            ]
        );
        // In addition to modifyContentLocalizeAddDefaultLangRelation(), "synchronize" the new default language image to the localized content element in workspaces.
        $this->actionService->invoke(
            [],
            [
                'tt_content' => [
                    $this->recordIds['localizedWsContentId'] => [
                        'inlineLocalizeSynchronize' => [
                            'field' => 'image',
                            'language' => 1,
                            'ids' => [
                                // Hardcoded source uid here since above modifyRecords() does not return the uid of the new attached image.
                                134,
                            ],
                        ],
                    ],
                ],
            ]
        );
    }
}
