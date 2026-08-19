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

namespace TYPO3\CMS\Workspaces\Tests\Functional\Hook;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\Event\ModifyEditFormUserAccessEvent;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Workspaces\Hook\BackendUtilityHook;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BackendUtilityHookTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    /**
     * @return list<string>
     */
    private function dispatchAccessEventsAndGetMessages(int ...$uids): array
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->importCSVDataSet(__DIR__ . '/DataSet/stagedRecordWithInlineChildren.csv');
        $backendUser->workspace = 1;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(1));

        $subject = $this->get(BackendUtilityHook::class);
        foreach ($uids as $uid) {
            $subject->displayEditingStagedElementInformation(
                new ModifyEditFormUserAccessEvent(null, 'tt_content', 'edit', ['uid' => $uid])
            );
        }

        $messages = $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessagesAndFlush();
        return array_map(static fn($message): string => $message->getMessage(), $messages);
    }

    #[Test]
    public function informationOnStagedElementIsAdded(): void
    {
        self::assertSame(
            ['Element is in workspace stage "Ready to publish", modifications will send it back to "Editing".'],
            $this->dispatchAccessEventsAndGetMessages(320)
        );
    }

    #[Test]
    public function informationOnStagedElementIsAddedOnlyOnceForRecordsSharingAStage(): void
    {
        // The event is dispatched for the record being edited and for each of its inline children,
        // which used to add one identical message per child.
        self::assertSame(
            ['Element is in workspace stage "Ready to publish", modifications will send it back to "Editing".'],
            $this->dispatchAccessEventsAndGetMessages(320, 321, 320)
        );
    }
}
