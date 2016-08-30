<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for GroupedForViewHelper.
 */
class GroupedForViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\GroupedForViewHelper
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMock(\TYPO3\CMS\Fluid\ViewHelpers\GroupedForViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderReturnsEmptyStringIfObjectIsNull()
    {
        $this->assertEquals('', $this->viewHelper->render(null, 'foo', 'bar'));
    }

    /**
     * @test
     */
    public function renderReturnsEmptyStringIfObjectIsEmptyArray()
    {
        $this->assertEquals('', $this->viewHelper->render([], 'foo', 'bar'));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionWhenPassingObjectsToEachThatAreNotTraversable()
    {
        $object = new \stdClass();

        $this->viewHelper->render($object, 'innerVariable', 'someKey');
    }

    /**
     * @test
     */
    public function renderGroupsMultidimensionalArrayAndPreservesKeys()
    {
        $photoshop = ['name' => 'Adobe Photoshop', 'license' => 'commercial'];
        $typo3 = ['name' => 'TYPO3', 'license' => 'GPL'];
        $office = ['name' => 'Microsoft Office', 'license' => 'commercial'];
        $drupal = ['name' => 'Drupal', 'license' => 'GPL'];
        $wordpress = ['name' => 'Wordpress', 'license' => 'GPL'];

        $products = ['photoshop' => $photoshop, 'typo3' => $typo3, 'office' => $office, 'drupal' => $drupal, 'wordpress' => $wordpress];

        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('myGroupKey', 'commercial');
        $this->templateVariableContainer->expects($this->at(1))->method('add')->with('products', ['photoshop' => $photoshop, 'office' => $office]);
        $this->templateVariableContainer->expects($this->at(2))->method('remove')->with('myGroupKey');
        $this->templateVariableContainer->expects($this->at(3))->method('remove')->with('products');
        $this->templateVariableContainer->expects($this->at(4))->method('add')->with('myGroupKey', 'GPL');
        $this->templateVariableContainer->expects($this->at(5))->method('add')->with('products', ['typo3' => $typo3, 'drupal' => $drupal, 'wordpress' => $wordpress]);
        $this->templateVariableContainer->expects($this->at(6))->method('remove')->with('myGroupKey');
        $this->templateVariableContainer->expects($this->at(7))->method('remove')->with('products');

        $this->viewHelper->render($products, 'products', 'license', 'myGroupKey');
    }

    /**
     * @test
     */
    public function renderGroupsMultidimensionalArrayObjectAndPreservesKeys()
    {
        $photoshop = new \ArrayObject(['name' => 'Adobe Photoshop', 'license' => 'commercial']);
        $typo3 = new \ArrayObject(['name' => 'TYPO3', 'license' => 'GPL']);
        $office = new \ArrayObject(['name' => 'Microsoft Office', 'license' => 'commercial']);
        $drupal = new \ArrayObject(['name' => 'Drupal', 'license' => 'GPL']);
        $wordpress = new \ArrayObject(['name' => 'Wordpress', 'license' => 'GPL']);

        $products = new \ArrayObject(['photoshop' => $photoshop, 'typo3' => $typo3, 'office' => $office, 'drupal' => $drupal, 'wordpress' => $wordpress]);

        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('myGroupKey', 'commercial');
        $this->templateVariableContainer->expects($this->at(1))->method('add')->with('products', ['photoshop' => $photoshop, 'office' => $office]);
        $this->templateVariableContainer->expects($this->at(2))->method('remove')->with('myGroupKey');
        $this->templateVariableContainer->expects($this->at(3))->method('remove')->with('products');
        $this->templateVariableContainer->expects($this->at(4))->method('add')->with('myGroupKey', 'GPL');
        $this->templateVariableContainer->expects($this->at(5))->method('add')->with('products', ['typo3' => $typo3, 'drupal' => $drupal, 'wordpress' => $wordpress]);
        $this->templateVariableContainer->expects($this->at(6))->method('remove')->with('myGroupKey');
        $this->templateVariableContainer->expects($this->at(7))->method('remove')->with('products');

        $this->viewHelper->render($products, 'products', 'license', 'myGroupKey');
    }

    /**
     * @test
     */
    public function renderGroupsArrayOfObjectsAndPreservesKeys()
    {
        $photoshop = new \stdClass();
        $photoshop->name = 'Adobe Photoshop';
        $photoshop->license = 'commercial';
        $typo3 = new \stdClass();
        $typo3->name = 'TYPO3';
        $typo3->license = 'GPL';
        $office = new \stdClass();
        $office->name = 'Microsoft Office';
        $office->license = 'commercial';
        $drupal = new \stdClass();
        $drupal->name = 'Drupal';
        $drupal->license = 'GPL';
        $wordpress = new \stdClass();
        $wordpress->name = 'Wordpress';
        $wordpress->license = 'GPL';

        $products = ['photoshop' => $photoshop, 'typo3' => $typo3, 'office' => $office, 'drupal' => $drupal, 'wordpress' => $wordpress];

        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('myGroupKey', 'commercial');
        $this->templateVariableContainer->expects($this->at(1))->method('add')->with('products', ['photoshop' => $photoshop, 'office' => $office]);
        $this->templateVariableContainer->expects($this->at(2))->method('remove')->with('myGroupKey');
        $this->templateVariableContainer->expects($this->at(3))->method('remove')->with('products');
        $this->templateVariableContainer->expects($this->at(4))->method('add')->with('myGroupKey', 'GPL');
        $this->templateVariableContainer->expects($this->at(5))->method('add')->with('products', ['typo3' => $typo3, 'drupal' => $drupal, 'wordpress' => $wordpress]);
        $this->templateVariableContainer->expects($this->at(6))->method('remove')->with('myGroupKey');
        $this->templateVariableContainer->expects($this->at(7))->method('remove')->with('products');

        $this->viewHelper->render($products, 'products', 'license', 'myGroupKey');
    }

    /**
     * @test
     */
    public function renderGroupsMultidimensionalArrayByObjectKey()
    {
        $customer1 = new \stdClass();
        $customer1->name = 'Anton Abel';

        $customer2 = new \stdClass();
        $customer2->name = 'Balthasar Bux';

        $invoice1 = ['date' => new \DateTime('1980-12-13'), 'customer' => $customer1];
        $invoice2 = ['date' => new \DateTime('2010-07-01'), 'customer' => $customer1];
        $invoice3 = ['date' => new \DateTime('2010-07-04'), 'customer' => $customer2];

        $invoices = ['invoice1' => $invoice1, 'invoice2' => $invoice2, 'invoice3' => $invoice3];

        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('myGroupKey', $customer1);
        $this->templateVariableContainer->expects($this->at(1))->method('add')->with('invoices', ['invoice1' => $invoice1, 'invoice2' => $invoice2]);
        $this->templateVariableContainer->expects($this->at(2))->method('remove')->with('myGroupKey');
        $this->templateVariableContainer->expects($this->at(3))->method('remove')->with('invoices');
        $this->templateVariableContainer->expects($this->at(4))->method('add')->with('myGroupKey', $customer2);
        $this->templateVariableContainer->expects($this->at(5))->method('add')->with('invoices', ['invoice3' => $invoice3]);
        $this->templateVariableContainer->expects($this->at(6))->method('remove')->with('myGroupKey');
        $this->templateVariableContainer->expects($this->at(7))->method('remove')->with('invoices');

        $this->viewHelper->render($invoices, 'invoices', 'customer', 'myGroupKey');
    }

    /**
     * @test
     */
    public function renderGroupsMultidimensionalArrayByPropertyPath()
    {
        $customer1 = new \stdClass();
        $customer1->name = 'Anton Abel';

        $customer2 = new \stdClass();
        $customer2->name = 'Balthasar Bux';

        $invoice1 = new \stdClass();
        $invoice1->customer = $customer1;

        $invoice2 = new \stdClass();
        $invoice2->customer = $customer1;

        $invoice3 = new \stdClass();
        $invoice3->customer = $customer2;

        $invoices = ['invoice1' => $invoice1, 'invoice2' => $invoice2, 'invoice3' => $invoice3];
        $groupBy = 'customer.name';
        /** @var \TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\TYPO3\CMS\Fluid\ViewHelpers\GroupedForViewHelper $accessibleMock */
        $accessibleMock = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\GroupedForViewHelper::class, ['dummy']);
        $expectedResult = [
            'keys' => [
                'Anton Abel' => 'Anton Abel',
                'Balthasar Bux' => 'Balthasar Bux'
            ],
            'values' => [
                'Anton Abel' => [
                    'invoice1' => $invoice1,
                    'invoice2' => $invoice2
                ],
                'Balthasar Bux' => [
                    'invoice3' => $invoice3
                ]
            ]
        ];
        $this->assertSame($expectedResult, $accessibleMock->_callRef('groupElements', $invoices, $groupBy));
    }

    /**
     * @test
     */
    public function renderGroupsMultidimensionalObjectByObjectKey()
    {
        $customer1 = new \stdClass();
        $customer1->name = 'Anton Abel';

        $customer2 = new \stdClass();
        $customer2->name = 'Balthasar Bux';

        $invoice1 = new \stdClass();
        $invoice1->date = new \DateTime('1980-12-13');
        $invoice1->customer = $customer1;

        $invoice2 = new \stdClass();
        $invoice2->date = new \DateTime('2010-07-01');
        $invoice2->customer = $customer1;

        $invoice3 = new \stdClass();
        $invoice3->date = new \DateTime('2010-07-04');
        $invoice3->customer = $customer2;

        $invoices = ['invoice1' => $invoice1, 'invoice2' => $invoice2, 'invoice3' => $invoice3];

        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('myGroupKey', $customer1);
        $this->templateVariableContainer->expects($this->at(1))->method('add')->with('invoices', ['invoice1' => $invoice1, 'invoice2' => $invoice2]);
        $this->templateVariableContainer->expects($this->at(2))->method('remove')->with('myGroupKey');
        $this->templateVariableContainer->expects($this->at(3))->method('remove')->with('invoices');
        $this->templateVariableContainer->expects($this->at(4))->method('add')->with('myGroupKey', $customer2);
        $this->templateVariableContainer->expects($this->at(5))->method('add')->with('invoices', ['invoice3' => $invoice3]);
        $this->templateVariableContainer->expects($this->at(6))->method('remove')->with('myGroupKey');
        $this->templateVariableContainer->expects($this->at(7))->method('remove')->with('invoices');

        $this->viewHelper->render($invoices, 'invoices', 'customer', 'myGroupKey');
    }

    /**
     * @test
     */
    public function renderGroupsMultidimensionalObjectByDateTimeObject()
    {
        $date1 = new \DateTime('2010-07-01');
        $date2 = new \DateTime('2010-07-04');

        $invoice1 = new \stdClass();
        $invoice1->date = $date1;
        $invoice1->id = 12340;

        $invoice2 = new \stdClass();
        $invoice2->date = $date1;
        $invoice2->id = 12341;

        $invoice3 = new \stdClass();
        $invoice3->date = $date2;
        $invoice3->id = 12342;

        $invoices = ['invoice1' => $invoice1, 'invoice2' => $invoice2, 'invoice3' => $invoice3];

        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('myGroupKey', $date1);
        $this->templateVariableContainer->expects($this->at(1))->method('add')->with('invoices', ['invoice1' => $invoice1, 'invoice2' => $invoice2]);
        $this->templateVariableContainer->expects($this->at(2))->method('remove')->with('myGroupKey');
        $this->templateVariableContainer->expects($this->at(3))->method('remove')->with('invoices');
        $this->templateVariableContainer->expects($this->at(4))->method('add')->with('myGroupKey', $date2);
        $this->templateVariableContainer->expects($this->at(5))->method('add')->with('invoices', ['invoice3' => $invoice3]);
        $this->templateVariableContainer->expects($this->at(6))->method('remove')->with('myGroupKey');
        $this->templateVariableContainer->expects($this->at(7))->method('remove')->with('invoices');

        $this->viewHelper->render($invoices, 'invoices', 'date', 'myGroupKey');
    }

    /**
     * @test
     */
    public function groupingByAKeyThatDoesNotExistCreatesASingleGroup()
    {
        $photoshop = ['name' => 'Adobe Photoshop', 'license' => 'commercial'];
        $typo3 = ['name' => 'TYPO3', 'license' => 'GPL'];
        $office = ['name' => 'Microsoft Office', 'license' => 'commercial'];

        $products = ['photoshop' => $photoshop, 'typo3' => $typo3, 'office' => $office];

        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('groupKey', null);
        $this->templateVariableContainer->expects($this->at(1))->method('add')->with('innerKey', $products);
        $this->templateVariableContainer->expects($this->at(2))->method('remove')->with('groupKey');
        $this->templateVariableContainer->expects($this->at(3))->method('remove')->with('innerKey');

        $this->viewHelper->render($products, 'innerKey', 'NonExistingKey');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionWhenPassingOneDimensionalArraysToEach()
    {
        $values = ['some', 'simple', 'array'];

        $this->viewHelper->render($values, 'innerVariable', 'someKey');
    }
}
