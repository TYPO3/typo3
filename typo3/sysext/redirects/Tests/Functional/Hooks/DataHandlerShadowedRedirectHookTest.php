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

namespace TYPO3\CMS\Redirects\Tests\Functional\Hooks;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Utility\RedirectConflict;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DataHandlerShadowedRedirectHookTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['redirects'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../../../core/Tests/Functional/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ShadowedRedirects/sys_redirect.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function creatingDuplicateRedirectMarksItAsShadowed(): void
    {
        $dataHandler = $this->processDatamap([
            'NEW1' => ['pid' => 0, 'source_host' => 'example.com', 'source_path' => '/unique', 'target' => '/new'],
        ]);
        $newUid = (int)$dataHandler->substNEWwithIDs['NEW1'];

        self::assertSame(RedirectConflict::SHADOWED, $this->getIntegrityStatus($newUid));
        $messages = $this->getFlashMessages();
        self::assertCount(1, $messages);
        self::assertSame(ContextualFeedbackSeverity::WARNING, $messages[0]->getSeverity());
        self::assertStringContainsString('#3', $messages[0]->getMessage());
    }

    #[Test]
    public function creatingDuplicateRedirectWithoutLanguageServiceMarksItAsShadowed(): void
    {
        unset($GLOBALS['LANG']);
        $dataHandler = $this->processDatamap([
            'NEW1' => ['pid' => 0, 'source_host' => 'example.com', 'source_path' => '/unique', 'target' => '/new'],
        ]);

        self::assertSame(RedirectConflict::SHADOWED, $this->getIntegrityStatus((int)$dataHandler->substNEWwithIDs['NEW1']));
        self::assertSame([], $this->getFlashMessages());
    }

    #[Test]
    public function creatingDisabledDuplicateRedirectIsNotMarkedAsShadowed(): void
    {
        $dataHandler = $this->processDatamap([
            'NEW1' => ['pid' => 0, 'disabled' => 1, 'source_host' => 'example.com', 'source_path' => '/unique', 'target' => '/new'],
        ]);

        self::assertSame(RedirectConflict::NO_CONFLICT, $this->getIntegrityStatus((int)$dataHandler->substNEWwithIDs['NEW1']));
        self::assertSame([], $this->getFlashMessages());
    }

    #[Test]
    public function changingSourceOfShadowedRedirectResetsItsStatus(): void
    {
        $this->processDatamap([2 => ['source_path' => '/no-longer-duplicate']]);

        self::assertSame(RedirectConflict::NO_CONFLICT, $this->getIntegrityStatus(2));
        self::assertSame([], $this->getFlashMessages());
    }

    #[Test]
    public function changingSourceOfShadowingRedirectResetsStatusOfFormerlyShadowedRedirect(): void
    {
        $this->processDatamap([1 => ['source_path' => '/no-longer-duplicate']]);

        self::assertSame(RedirectConflict::NO_CONFLICT, $this->getIntegrityStatus(2));
    }

    #[Test]
    public function changingSourceHostOfShadowedRedirectResetsItsStatus(): void
    {
        $this->processDatamap([2 => ['source_host' => 'another.example.com']]);

        self::assertSame(RedirectConflict::NO_CONFLICT, $this->getIntegrityStatus(2));
    }

    #[Test]
    public function changingSourceOfExistingRedirectMarksNewerRedirectAsShadowed(): void
    {
        $this->processDatamap([1 => ['source_path' => '/unique']]);

        self::assertSame(RedirectConflict::SHADOWED, $this->getIntegrityStatus(3));
        self::assertSame(RedirectConflict::NO_CONFLICT, $this->getIntegrityStatus(2));
        $messages = $this->getFlashMessages();
        self::assertCount(1, $messages);
        self::assertStringContainsString('#3', $messages[0]->getMessage());
    }

    #[Test]
    public function deletingShadowingRedirectResetsStatusOfShadowedRedirect(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['sys_redirect' => [1 => ['delete' => 1]]]);
        $dataHandler->process_cmdmap();

        self::assertSame([], $dataHandler->errorLog);
        self::assertSame(RedirectConflict::NO_CONFLICT, $this->getIntegrityStatus(2));
    }

    private function processDatamap(array $redirects): DataHandler
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['sys_redirect' => $redirects], []);
        $dataHandler->process_datamap();
        self::assertSame([], $dataHandler->errorLog);
        return $dataHandler;
    }

    private function getIntegrityStatus(int $uid): string
    {
        return (string)BackendUtility::getRecord('sys_redirect', $uid, 'integrity_status', '', false)['integrity_status'];
    }

    /**
     * @return FlashMessage[]
     */
    private function getFlashMessages(): array
    {
        return $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages();
    }
}
