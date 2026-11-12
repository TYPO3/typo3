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
use TYPO3\CMS\Backend\Form\Element\SelectCheckBoxElement;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SelectCheckBoxElementTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_core.csv');

        $GLOBALS['BE_USER'] = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->enablecolumns = ['deleted' => true];
        $GLOBALS['BE_USER']->setBeUserByUid(1);
        $GLOBALS['BE_USER']->initializeUserSessionManager();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('en');
    }

    #[Test]
    public function renderProvidesHelpTextAsFocusableButtonWithAccessibleName(): void
    {
        $html = $this->renderWithItems([
            [
                'value' => 'pages',
                'label' => 'Page',
                'description' => ['title' => 'Page help title', 'description' => 'Page help description'],
            ],
        ])['html'];

        self::assertMatchesRegularExpression('#<button type="button" class="[^"]*help-link[^"]*"[^>]*>.*<span class="visually-hidden">Page help title: Page help description</span></button>#s', $html);
    }

    #[Test]
    public function renderDoesNotRenderHelpForItemsWithoutDescription(): void
    {
        $html = $this->renderWithItems([
            ['value' => 'pages', 'label' => 'Page'],
        ])['html'];

        self::assertStringNotContainsString('help-link', $html);
    }

    private function renderWithItems(array $items): array
    {
        $node = $this->get(SelectCheckBoxElement::class);
        $node->setData([
            'tableName' => 'be_groups',
            'fieldName' => 'tables_select',
            'databaseRow' => ['uid' => 1],
            'processedTca' => ['columns' => ['tables_select' => ['config' => ['type' => 'select', 'renderType' => 'selectCheckBox', 'items' => $items]]]],
            'parameterArray' => [
                'itemFormElName' => 'data[be_groups][1][tables_select]',
                'itemFormElValue' => [],
                'fieldConf' => [
                    'label' => 'Tables',
                    'config' => ['type' => 'select', 'renderType' => 'selectCheckBox', 'items' => $items],
                ],
            ],
        ]);
        return $node->render();
    }
}
