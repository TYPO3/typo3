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

namespace TYPO3\CMS\Backend\Tests\Functional\Form\Container;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\Container\FileReferenceContainer;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileReferenceContainerTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('en');
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] = false;
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
    }

    #[Test]
    public function collapseToggleUsesAttributeSelectorForDottedFlexFormIdentifier(): void
    {
        $domObjectId = 'data-1-tt_content-13-pi_flexform---data---sDEF---lDEF---settings.image---vDEF';
        $html = $this->renderFileReference($domObjectId);

        self::assertStringContainsString(
            'id="' . $domObjectId . '-sys_file_reference-42_fields"',
            $html
        );
        self::assertStringContainsString(
            'data-bs-target="[id=&quot;' . $domObjectId . '-sys_file_reference-42_fields&quot;]"',
            $html
        );
        self::assertStringNotContainsString('data-bs-target="#', $html);
    }

    private function renderFileReference(string $domObjectId): string
    {
        $inlineStructure = $this->get(InlineStackProcessor::class)->getStructureFromString(
            $domObjectId . '-sys_file_reference-42',
            $this->get(TcaSchemaFactory::class)->all()
        );

        $subject = $this->get(FileReferenceContainer::class);
        $subject->setData([
            'command' => 'edit',
            'tableName' => 'sys_file_reference',
            'databaseRow' => [
                'uid' => 42,
                'pid' => 1,
                'sys_language_uid' => 0,
                'uid_local' => [],
            ],
            'processedTca' => ['ctrl' => []],
            'recordTitle' => 'someFile.jpg',
            'parentPageRow' => ['uid' => 1],
            'inlineData' => [],
            'inlineStructure' => $inlineStructure,
            'inlineFirstPid' => 1,
            'inlineParentConfig' => ['inline' => ['first' => 0, 'last' => 0]],
            'inlineTopMostParentTableName' => 'tt_content',
            'inlineTopMostParentUid' => 13,
            'isInlineChildExpanded' => false,
            'isInlineDefaultLanguageRecordInLocalizedParentContext' => false,
            'tcaSchemata' => $this->get(TcaSchemaFactory::class)->all(),
            'returnUrl' => '',
            'request' => new ServerRequest()
                ->withAttribute('route', new Route('', []))
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE),
        ]);

        return $subject->render()['html'];
    }
}
