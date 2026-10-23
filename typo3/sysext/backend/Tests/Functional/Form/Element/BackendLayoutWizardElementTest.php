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

namespace TYPO3\CMS\Backend\Tests\Functional\Form\Element;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\Element\BackendLayoutWizardElement;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BackendLayoutWizardElementTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('en');
    }

    #[Test]
    public function renderPassesContentTypeRestrictionsOfColumnsToGridEditor(): void
    {
        $rows = $this->renderAndGetGridEditorRows(
            'backend_layout {
                colCount = 2
                rowCount = 1
                rows {
                    1 {
                        columns {
                            1 {
                                name = Main
                                colPos = 0
                                allowedContentTypes = header, textmedia
                            }
                            2 {
                                name = Aside
                                colPos = 1
                                disallowedContentTypes = html
                            }
                        }
                    }
                }
            }'
        );

        self::assertSame('header, textmedia', $rows[0][0]['allowedContentTypes']);
        self::assertArrayNotHasKey('disallowedContentTypes', $rows[0][0]);
        self::assertSame('html', $rows[0][1]['disallowedContentTypes']);
        self::assertArrayNotHasKey('allowedContentTypes', $rows[0][1]);
    }

    #[Test]
    public function renderMapsContentDefenderSyntaxToContentTypeRestrictions(): void
    {
        $rows = $this->renderAndGetGridEditorRows(
            'backend_layout {
                colCount = 2
                rowCount = 1
                rows {
                    1 {
                        columns {
                            1 {
                                name = Main
                                colPos = 0
                                allowed.CType = header, textmedia
                                disallowed.CType = html
                            }
                            2 {
                                name = Aside
                                colPos = 1
                                allowed.CType = header
                                allowedContentTypes = textmedia
                            }
                        }
                    }
                }
            }'
        );

        self::assertSame('header, textmedia', $rows[0][0]['allowedContentTypes']);
        self::assertSame('html', $rows[0][0]['disallowedContentTypes']);
        self::assertSame('textmedia', $rows[0][1]['allowedContentTypes']);
    }

    private function renderAndGetGridEditorRows(string $configuration): array
    {
        $subject = $this->get(BackendLayoutWizardElement::class);
        $subject->setData([
            'tableName' => 'backend_layout',
            'fieldName' => 'config',
            'databaseRow' => ['uid' => 1],
            'processedTca' => ['ctrl' => [], 'columns' => ['config' => ['config' => []]]],
            'parameterArray' => [
                'itemFormElValue' => $configuration,
                'itemFormElName' => 'data[backend_layout][1][config]',
                'fieldConf' => ['label' => 'Configuration', 'config' => []],
            ],
        ]);
        $html = $subject->render()['html'];
        self::assertSame(1, preg_match('/<typo3-backend-grid-editor\s+data="([^"]*)"/', $html, $matches));
        return json_decode(htmlspecialchars_decode($matches[1]), true, 512, JSON_THROW_ON_ERROR);
    }
}
