<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\FormEngine;

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

use Codeception\Example;
use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests for "elements_basic" simple input fields of ext:styleguide
 */
class ElementsBasicInputCest extends AbstractElementsBasicCest
{
    /**
     * Open list module of styleguide elements basic page
     *
     * @param BackendTester $I
     * @param PageTree $pageTree
     */
    public function _before(BackendTester $I, PageTree $pageTree)
    {
        $I->useExistingSession('admin');
        $I->click('List');
        $pageTree->openPath(['styleguide TCA demo', 'elements basic']);
        $I->switchToContentFrame();

        // Open record and wait until form is ready
        $I->waitForText('elements basic', 20);
        $editRecordLinkCssPath = '#recordlist-tx_styleguide_elements_basic a[data-original-title="Edit record"]';
        $I->click($editRecordLinkCssPath);
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForText('Edit Form', 3, 'h1');
    }

    /**
     * Data provider to check various type=input variants
     */
    protected function simpleInputFieldsDataProvider()
    {
        return [
            [
                'label' => 'input_1',
                'inputValue' => 'This is a demo text',
                'expectedValue' => 'This is a demo text',
                'expectedInternalValue' => 'This is a demo text',
                'expectedValueAfterSave' => 'This is a demo text',
                'comment' => '',
            ],
            [
                'label' => 'input_2, size=10',
                'inputValue' => 'This is a demo text with numbers and other characters 42 #!',
                'expectedValue' => 'This is a demo text with numbers and other characters 42 #!',
                'expectedInternalValue' => 'This is a demo text with numbers and other characters 42 #!',
                'expectedValueAfterSave' => 'This is a demo text with numbers and other characters 42 #!',
                'comment' => '',
            ],
            [
                'label' => 'input_3 max=4',
                'inputValue' => 'Kasper',
                'expectedValue' => 'Kasp',
                'expectedInternalValue' => 'Kasp',
                'expectedValueAfterSave' => 'Kasp',
                'comment' => '',
            ],
            [
                'label' => 'input_4 eval=alpha',
                'inputValue' => 'Kasper = TYPO3',
                'expectedValue' => 'KasperTYPO',
                'expectedInternalValue' => 'KasperTYPO',
                'expectedValueAfterSave' => 'KasperTYPO',
                'comment' => '',
            ],
            [
                'label' => 'input_4 eval=alpha',
                'inputValue' => 'Non-latin characters: ŠĐŽĆČ',
                'expectedValue' => 'Nonlatincharacters',
                'expectedInternalValue' => 'Nonlatincharacters',
                'expectedValueAfterSave' => 'Nonlatincharacters',
                'comment' => '',
            ],
            [
                'label' => 'input_5 eval=alphanum',
                'inputValue' => 'Kasper = TYPO3',
                'expectedValue' => 'KasperTYPO3',
                'expectedInternalValue' => 'KasperTYPO3',
                'expectedValueAfterSave' => 'KasperTYPO3',
                'comment' => '',
            ],
            [
                'label' => 'input_10 eval=is_in, is_in=abc123',
                'inputValue' => 'abcd1234',
                'expectedValue' => 'abc123',
                'expectedInternalValue' => 'abc123',
                'expectedValueAfterSave' => 'abc123',
                'comment' => '',
            ],
            [
                'label' => 'input_10 eval=is_in, is_in=abc123',
                'inputValue' => 'Kasper TYPO3',
                'expectedValue' => 'a3',
                'expectedInternalValue' => 'a3',
                'expectedValueAfterSave' => 'a3',
                'comment' => '',
            ],
            [
                'label' => 'input_11 eval=lower',
                'inputValue' => 'Kasper TYPO3!',
                'expectedValue' => 'kasper typo3!',
                'expectedInternalValue' => 'kasper typo3!',
                'expectedValueAfterSave' => 'kasper typo3!',
                'comment' => '',
            ],
            [
                'label' => 'input_13 eval=nospace',
                'inputValue' => ' Kasper TYPO3! ',
                'expectedValue' => 'KasperTYPO3!',
                'expectedInternalValue' => 'KasperTYPO3!',
                'expectedValueAfterSave' => 'KasperTYPO3!',
                'comment' => '',
            ],
            [
                'label' => 'input_16 eval=password',
                'inputValue' => 'Kasper',
                'expectedValue' => '********',
                'expectedInternalValue' => 'Kasper',
                'expectedValueAfterSave' => 'Kasper',
                'comment' => '',
            ],
            [
                'label' => 'input_19 eval=trim',
                'inputValue' => ' Kasper ',
                'expectedValue' => 'Kasper',
                'expectedInternalValue' => 'Kasper',
                'expectedValueAfterSave' => 'Kasper',
                'comment' => '',
            ],
            [
                'label' => 'input_19 eval=trim',
                'inputValue' => ' Kasper TYPO3 ',
                'expectedValue' => 'Kasper TYPO3',
                'expectedInternalValue' => 'Kasper TYPO3',
                'expectedValueAfterSave' => 'Kasper TYPO3',
                'comment' => '',
            ],
            [
                'label' => 'input_23 eval=upper',
                'inputValue' => 'Kasper TYPO3!',
                'expectedValue' => 'KASPER TYPO3!',
                'expectedInternalValue' => 'KASPER TYPO3!',
                'expectedValueAfterSave' => 'KASPER TYPO3!',
                'comment' => '',
            ],
            [
                'label' => 'input_24 eval=year',
                'inputValue' => '2016',
                'expectedValue' => '2016',
                'expectedInternalValue' => '2016',
                'expectedValueAfterSave' => '2016',
                'comment' => '',
            ],
            [
                'label' => 'input_24 eval=year',
                'inputValue' => '12',
                'expectedValue' => '12',
                'expectedInternalValue' => '12',
                'expectedValueAfterSave' => '12',
                'comment' => '',
            ],
            [
                'label' => 'input_24 eval=year',
                'inputValue' => 'Kasper',
                'expectedValue' => date('Y'),
                'expectedInternalValue' => date('Y'),
                'expectedValueAfterSave' => date('Y'),
                'comment' => 'Invalid character is converted to current year',
            ]
        ];
    }

    /**
     * @dataProvider simpleInputFieldsDataProvider
     * @param BackendTester $I
     * @param Example $testData
     */
    public function simpleInputFields(BackendTester $I, Example $testData)
    {
        $this->runInputFieldTest($I, $testData);
    }

    /**
     * Test various type=input fields having eval
     */
    protected function simpleEvalInputFieldsDataProvider()
    {
        return [
            [
                'label' => 'input_8 eval=double2',
                'inputValue' => '12.335',
                'expectedValue' => '12.34',
                'expectedInternalValue' => '12.34',
                'expectedValueAfterSave' => '12.34',
                'comment' => '',
            ],
            [
                'label' => 'input_8 eval=double2',
                'inputValue' => '12,335', // comma as delimiter
                'expectedValue' => '12.34',
                'expectedInternalValue' => '12.34',
                'expectedValueAfterSave' => '12.34',
                'comment' => '',
            ],
            [
                'label' => 'input_8 eval=double2',
                'inputValue' => '1.1', // dot as delimiter
                'expectedValue' => '1.10',
                'expectedInternalValue' => '1.10',
                'expectedValueAfterSave' => '1.10',
                'comment' => '',
            ],
            [
                'label' => 'input_8 eval=double2',
                'inputValue' => 'TYPO3', // word having a number at end
                'expectedValue' => '3.00',
                'expectedInternalValue' => '3.00',
                'expectedValueAfterSave' => '3.00',
                'comment' => '',
            ],
            [
                'label' => 'input_8 eval=double2',
                'inputValue' => '3TYPO', // word having a number in front
                'expectedValue' => '3.00',
                'expectedInternalValue' => '3.00',
                'expectedValueAfterSave' => '3.00',
                'comment' => '',
            ],
            [
                'label' => 'input_9 eval=int',
                'inputValue' => '12.335',
                'expectedValue' => '12',
                'expectedInternalValue' => '12',
                'expectedValueAfterSave' => '12',
                'comment' => '',
            ],
            [
                'label' => 'input_9 eval=int',
                'inputValue' => '12,9',
                'expectedValue' => '129',
                'expectedInternalValue' => '129',
                'expectedValueAfterSave' => '129',
                'comment' => '',
            ],
            /**
            [
                // @todo this one probably broke with the html type="number" patch
                'label' => 'input_9 eval=int',
                'inputValue' => 'TYPO3',
                'expectedValue' => '0',
                'expectedInternalValue' => '0',
                'expectedValueAfterSave' => '0',
                'comment' => '',
            ],
            */
            [
                'label' => 'input_9 eval=int',
                'inputValue' => '3TYPO',
                'expectedValue' => '3',
                'expectedInternalValue' => '3',
                'expectedValueAfterSave' => '3',
                'comment' => '',
            ],
            [
                'label' => 'input_15 eval=num',
                'inputValue' => '12.335',
                'expectedValue' => '12335',
                'expectedInternalValue' => '12335',
                'expectedValueAfterSave' => '12335',
                'comment' => '',
            ],
            [
                'label' => 'input_15 eval=num',
                'inputValue' => '12,9',
                'expectedValue' => '129',
                'expectedInternalValue' => '129',
                'expectedValueAfterSave' => '129',
                'comment' => '',
            ],
            [
                'label' => 'input_15 eval=num',
                'inputValue' => 'TYPO3',
                'expectedValue' => '3',
                'expectedInternalValue' => '3',
                'expectedValueAfterSave' => '3',
                'comment' => '',
            ],
            [
                'label' => 'input_15 eval=num',
                'inputValue' => '3TYPO',
                'expectedValue' => '3',
                'expectedInternalValue' => '3',
                'expectedValueAfterSave' => '3',
                'comment' => '',
            ],
        ];
    }

    /**
     * @dataProvider simpleEvalInputFieldsDataProvider
     * @param BackendTester $I
     * @param Example $testData
     */
    public function simpleEvalInputFields(BackendTester $I, Example $testData)
    {
        $this->runInputFieldTest($I, $testData);
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
                'label' => 'input_25 eval=int, default=0, range lower=-2, range upper=2',
                'inputValue' => 'Kasper TYPO3',
                'expectedValue' => '0',
                'expectedInternalValue' => '0',
                'expectedValueAfterSave' => '0',
                'comment' => '',
            ],
             */
            [
                'label' => 'input_25 eval=int, default=0, range lower=-2, range upper=2',
                'inputValue' => '2',
                'expectedValue' => '2',
                'expectedInternalValue' => '2',
                'expectedValueAfterSave' => '2',
                'comment' => '',
            ],
            [
                'label' => 'input_25 eval=int, default=0, range lower=-2, range upper=2',
                'inputValue' => '-1',
                'expectedValue' => '-1',
                'expectedInternalValue' => '-1',
                'expectedValueAfterSave' => '-1',
                'comment' => '',
            ],
            [
                'label' => 'input_12 eval=md5',
                'inputValue' => 'Kasper TYPO3!',
                'expectedValue' => '748469dd64911af8df8f9a3dcb2c9378',
                'expectedInternalValue' => '748469dd64911af8df8f9a3dcb2c9378',
                'expectedValueAfterSave' => '748469dd64911af8df8f9a3dcb2c9378',
                'comment' => '',
            ],
            [
                'label' => 'input_12 eval=md5',
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
     * @param BackendTester $I
     * @param Example $testData
     */
    public function simpleRangeAndMd5Fields(BackendTester $I, Example $testData)
    {
        $this->runInputFieldTest($I, $testData);
    }
}
