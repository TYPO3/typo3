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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\RegularFreeMode\WorkspacesPublish;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\RegularFreeMode\AbstractActionWorkspacesTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\HasRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

final class ActionTest extends AbstractActionWorkspacesTestCase
{
    #[Test]
    public function verifyCleanReferenceIndex(): void
    {
        // Fix refindex, then compare with import csv again to verify nothing changed.
        // This is to make sure the import csv is 'clean' - important for the other tests.
        $this->get(ReferenceIndex::class)->updateIndex(false);
        $this->assertCSVDataSet(self::SCENARIO_DataSet);
    }

    #[Test]
    public function localizeContentAfterMovedContent(): void
    {
        parent::localizeContentAfterMovedContent();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentAfterMovedContent.csv');
    }

    #[Test]
    public function localizeContentAfterMovedInLiveContent(): void
    {
        parent::localizeContentAfterMovedInLiveContent();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentAfterMovedInLiveContent.csv');
    }

    #[Test]
    public function copyPage(): void
    {
        parent::copyPage();
        $this->actionService->publishRecords(
            [
                self::TABLE_Page => [$this->recordIds['newPageId']],
                self::TABLE_Content => [$this->recordIds['newContentIdTenth'], $this->recordIds['newContentIdTenthLocalized'], $this->recordIds['newContentIdTenthLocalized2']],
            ]
        );
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyPage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['newPageId']));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Target'));
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #10'));
    }
}
