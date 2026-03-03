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

namespace TYPO3\CMS\Form\Tests\Functional\Domain\Finishers;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Platform\SQLitePlatform;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Finishers\SaveToDatabaseFinisher;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Domain\Runtime\FormState;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SaveToDatabaseFinisherTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    #[Test]
    public function insertIntoTableWithoutUidColumnCreatesRow(): void
    {
        // sys_category_record_mm has no auto-increment UID column; lastInsertId()
        // throws in that case. The finisher must handle this gracefully and still
        // write the row to the database.
        $formRuntime = $this->createMock(FormRuntime::class);
        $formRuntime->method('getFormState')->willReturn(new FormState());
        $formRuntime->method('getFormDefinition')->willReturn(
            $this->createMock(FormDefinition::class)
        );
        $formRuntime->method('getRenderingOptions')->willReturn([
            'translation' => ['translationFiles' => ['EXT:form/Resources/Private/Language/locallang.xlf']],
        ]);
        $finisherContext = new FinisherContext($formRuntime, $this->createMock(Request::class));

        $subject = new SaveToDatabaseFinisher();
        $subject->setFinisherIdentifier('SaveToDatabase');
        $subject->injectTranslationService($this->get(TranslationService::class));
        $subject->setOptions([
            'table' => 'sys_category_record_mm',
            'mode' => 'insert',
            'databaseColumnMappings' => [
                'uid_local'   => ['value' => 47],
                'uid_foreign' => ['value' => 11],
            ],
        ]);

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category_record_mm');
        self::assertSame(0, (int)$queryBuilder->count('*')->from('sys_category_record_mm')->executeQuery()->fetchOne());

        $subject->execute($finisherContext);

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category_record_mm');
        self::assertSame(1, (int)$queryBuilder->count('*')->from('sys_category_record_mm')->executeQuery()->fetchOne());

        // These assertions only work for non-SQLite DBMSs since SQLite returns an unexpected value for lastInsertId
        $databasePlatform = $this->getConnectionPool()->getConnectionForTable('sys_category_record_mm')->getDatabasePlatform();
        if (!$databasePlatform instanceof SQLitePlatform) {
            self::assertTrue($finisherContext->getFinisherVariableProvider()->exists('SaveToDatabase', 'insertedUids.0'));
            self::assertSame(0, $finisherContext->getFinisherVariableProvider()->get('SaveToDatabase', 'insertedUids.0'));
        }
    }
}
