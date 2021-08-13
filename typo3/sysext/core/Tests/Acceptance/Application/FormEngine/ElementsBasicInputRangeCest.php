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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\FormEngine;

use Codeception\Example;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\UnknownServerException;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests for "elements_basic" range input fields of ext:styleguide
 */
class ElementsBasicInputRangeCest extends AbstractElementsBasicCest
{
    /**
     * Open list module of styleguide elements basic page
     *
     * @param ApplicationTester $I
     * @param PageTree $pageTree
     */
    public function _before(ApplicationTester $I, PageTree $pageTree)
    {
        $I->useExistingSession('admin');
        $I->click('List');
        $I->waitForElement('svg .nodes .node');
        $pageTree->openPath(['styleguide TCA demo', 'elements basic']);
        $I->switchToContentFrame();

        // Open record and wait until form is ready
        $I->waitForText('elements basic', 20);
        $editRecordLinkCssPath = '#recordlist-tx_styleguide_elements_basic a[data-bs-original-title="Edit record"]';
        $I->click($editRecordLinkCssPath);
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForText('Edit Form', 3, 'h1');

        try {
            // make sure the test operates on the input tab
            $I->click('input');
            $I->waitForText('input', 3);
        } catch (ElementClickInterceptedException|UnknownServerException $exception) {
            // nothing to do, the tab is already active
        }
    }

    /**
     * type=input range and md5 field tests
     */
    protected function simpleRangeAndMd5FieldsDataProvider()
    {
        return [
            /**
            [
                // @todo this one probably broke with the type="number" patch
                'label' => 'input_25',
                'inputValue' => 'Kasper TYPO3',
                'expectedValue' => '0',
                'expectedInternalValue' => '0',
                'expectedValueAfterSave' => '0',
                'comment' => '',
            ],
             */
            [
                'label' => 'input_25',
                'inputValue' => '2',
                'expectedValue' => '2',
                'expectedInternalValue' => '2',
                'expectedValueAfterSave' => '2',
                'comment' => '',
            ],
            [
                'label' => 'input_25',
                'inputValue' => '-1',
                'expectedValue' => '-1',
                'expectedInternalValue' => '-1',
                'expectedValueAfterSave' => '-1',
                'comment' => '',
            ],
            [
                'label' => 'input_12',
                'inputValue' => 'Kasper TYPO3!',
                'expectedValue' => '748469dd64911af8df8f9a3dcb2c9378',
                'expectedInternalValue' => '748469dd64911af8df8f9a3dcb2c9378',
                'expectedValueAfterSave' => '748469dd64911af8df8f9a3dcb2c9378',
                'comment' => '',
            ],
            [
                'label' => 'input_12',
                'inputValue' => ' Kasper TYPO3! ',
                'expectedValue' => '792a085606250c47d6ebb8c98804d5b0',
                'expectedInternalValue' => '792a085606250c47d6ebb8c98804d5b0',
                'expectedValueAfterSave' => '792a085606250c47d6ebb8c98804d5b0',
                'comment' => 'Check whitespaces are not trimmed.',
            ],
        ];
    }

    /**
     * @dataProvider simpleRangeAndMd5FieldsDataProvider
     * @param ApplicationTester $I
     * @param Example $testData
     */
    public function simpleRangeAndMd5Fields(ApplicationTester $I, Example $testData)
    {
        $this->runInputFieldTest($I, $testData);
    }
}
