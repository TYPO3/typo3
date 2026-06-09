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

namespace TYPO3\CMS\Form\Tests\Functional\Storage;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Form\Domain\DTO\FormData;
use TYPO3\CMS\Form\Domain\Repository\FormDefinitionRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Verifies that FormDefinitionRepository::add(), update(), and remove()
 * actually persist their changes to the database through DataHandler.
 */
final class FormDefinitionRepositoryTest extends FunctionalTestCase
{
    private FormDefinitionRepository $subject;
    protected array $coreExtensionsToLoad = ['form'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/form_definition.csv');
        $this->subject = $this->get(FormDefinitionRepository::class);
    }

    #[Test]
    public function addPersistsRecordToDatabase(): void
    {
        $uid = $this->subject->add('NEW42', 0, $this->buildFormData('repo-add-form', 'Repository Add Form'));

        self::assertNotNull($uid);
        self::assertGreaterThan(0, $uid);

        $row = $this->subject->findByUid($uid);
        self::assertIsArray($row);
        self::assertSame('repo-add-form', $row['identifier']);
        self::assertSame('Repository Add Form', $row['label']);
        self::assertSame(0, (int)$row['pid']);
    }

    #[Test]
    public function updatePersistsChangesToDatabase(): void
    {
        $result = $this->subject->update(1, $this->buildFormData('crud-test-form', 'Updated Repository Label'));

        self::assertTrue($result);

        $row = $this->subject->findByUid(1);
        self::assertIsArray($row);
        self::assertSame('Updated Repository Label', $row['label']);
        self::assertSame('crud-test-form', $row['identifier']);
    }

    #[Test]
    public function removeSoftDeletesRecordInDatabase(): void
    {
        $result = $this->subject->remove(1);

        self::assertTrue($result);
        // attempt to remove again, which should return null
        self::assertNull($this->subject->findByUid(1));

        // verify the row is still present with deleted=1 (soft delete, not hard delete).
        // Remove all restrictions so the QueryBuilder can see the soft-deleted row.
        $queryBuilder = $this->get(ConnectionPool::class)
            ->getQueryBuilderForTable('form_definition');
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder
            ->select('uid', 'deleted')
            ->from('form_definition')
            ->where($queryBuilder->expr()->eq('uid', 1))
            ->executeQuery()
            ->fetchAssociative();
        self::assertIsArray($row);
        self::assertSame(1, (int)$row['deleted']);
    }

    private function buildFormData(string $identifier, string $label): FormData
    {
        return FormData::fromArray([
            'identifier' => $identifier,
            'type' => 'Form',
            'label' => $label,
            'prototypeName' => 'standard',
            'renderingOptions' => [],
            'finishers' => [],
            'renderables' => [],
            'variants' => [],
        ]);
    }
}
