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

namespace TYPO3\CMS\Form\Tests\Functional\Upgrades;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Storage\JsonObjectKeyOrderPreserver;
use TYPO3\CMS\Form\Upgrades\FinisherTranslationLanguageUpgradeWizard;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FinisherTranslationLanguageUpgradeWizardTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'form',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/form_definition.csv');
    }

    #[Test]
    public function translatedDefaultLanguageValuesAreMigrated(): void
    {
        $subject = $this->get(FinisherTranslationLanguageUpgradeWizard::class);

        self::assertTrue($subject->updateNecessary());
        self::assertTrue($subject->executeUpdate());
        self::assertFalse($subject->updateNecessary());

        self::assertSame('default', $this->getFieldValue(1, 'settings.finishers.EmailToSender.translation.language'));
        self::assertSame('default', $this->getFieldValue(2, 'settings.finishers.EmailToReceiver.translation.language'));
        self::assertSame('default', $this->getFieldValue(8, 'settings.finishers.EmailToSender.translation.language'));
        self::assertSame('1:/form_definitions/contact.form.yaml', $this->getFieldValue(1, 'settings.persistenceIdentifier'));
    }

    #[Test]
    public function otherValuesAndRecordsAreNotMigrated(): void
    {
        $this->get(FinisherTranslationLanguageUpgradeWizard::class)->executeUpdate();

        self::assertSame('', $this->getFieldValue(3, 'settings.finishers.EmailToSender.translation.language'));
        self::assertSame('default', $this->getFieldValue(4, 'settings.finishers.EmailToSender.translation.language'));
        self::assertSame('Default', $this->getFieldValue(5, 'settings.finishers.EmailToSender.subject'));
        self::assertSame('Default', $this->getFieldValue(6, 'settings.finishers.EmailToSender.translation.language'));
        self::assertSame('Default', $this->getFieldValue(7, 'settings.finishers.EmailToSender.translation.language'));
        self::assertSame('de-CH', $this->getFieldValue(9, 'settings.finishers.EmailToSender.translation.language'));
    }

    #[Test]
    public function translatedDefaultLanguageValuesInFormDefinitionsAreMigrated(): void
    {
        $subject = $this->get(FinisherTranslationLanguageUpgradeWizard::class);

        self::assertTrue($subject->executeUpdate());
        self::assertFalse($subject->updateNecessary());

        $formDefinition = $this->getFormDefinition(1);
        self::assertSame(
            ['subject' => 'Default', 'translation' => ['language' => 'default']],
            $formDefinition['finishers'][0]['options'],
        );
        self::assertSame('de', $formDefinition['finishers'][1]['options']['translation']['language']);
        self::assertSame(
            ['translation' => ['language' => 'default']],
            $this->getFormDefinition(2)['finishers'][0]['options'],
        );
    }

    #[Test]
    public function translatedDefaultLanguageValuesInFormDefinitionsRequireUpdate(): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable('tt_content')->truncate('tt_content');

        self::assertTrue($this->get(FinisherTranslationLanguageUpgradeWizard::class)->updateNecessary());
    }

    #[Test]
    public function otherFormDefinitionsAreNotMigrated(): void
    {
        $this->get(FinisherTranslationLanguageUpgradeWizard::class)->executeUpdate();

        $formDefinition = $this->getFormDefinition(3);
        self::assertSame('default', $formDefinition['finishers'][0]['options']['translation']['language']);
        self::assertSame('', $formDefinition['finishers'][1]['options']['translation']['language']);
        self::assertSame('de_CH', $formDefinition['finishers'][2]['options']['translation']['language']);
        self::assertSame('Default', $this->getFormDefinition(4)['finishers'][0]['options']['translation']['language']);
    }

    private function getFormDefinition(int $uid): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('form_definition');
        $queryBuilder->getRestrictions()->removeAll();
        $configuration = (string)$queryBuilder
            ->select('configuration')
            ->from('form_definition')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid)))
            ->executeQuery()
            ->fetchOne();
        return (new JsonObjectKeyOrderPreserver())->restore(
            json_decode($configuration, true, 512, JSON_THROW_ON_ERROR)
        );
    }

    private function getFieldValue(int $uid, string $field): string
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $flexForm = (string)$queryBuilder
            ->select('pi_flexform')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid)))
            ->executeQuery()
            ->fetchOne();
        foreach (GeneralUtility::xml2array($flexForm)['data'] as $sheet) {
            if (isset($sheet['lDEF'][$field]['vDEF'])) {
                return (string)$sheet['lDEF'][$field]['vDEF'];
            }
        }
        self::fail('Field ' . $field . ' not found in record ' . $uid);
    }
}
