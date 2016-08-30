<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\ViewHelper;

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
 * Test case
 */
class TemplateVariableContainerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer
     */
    protected $variableContainer;

    protected function setUp()
    {
        $this->variableContainer = new \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer();
    }

    /**
     * @test
     */
    public function addedObjectsCanBeRetrievedAgain()
    {
        $object = 'StringObject';
        $this->variableContainer->add('variable', $object);
        $this->assertSame($this->variableContainer->get('variable'), $object, 'The retrieved object from the context is not the same as the stored object.');
    }

    /**
     * @test
     */
    public function addedObjectsCanBeRetrievedAgainUsingArrayAccess()
    {
        $object = 'StringObject';
        $this->variableContainer['variable'] = $object;
        $this->assertSame($this->variableContainer->get('variable'), $object);
        $this->assertSame($this->variableContainer['variable'], $object);
    }

    /**
     * @test
     */
    public function addedObjectsExistInArray()
    {
        $object = 'StringObject';
        $this->variableContainer->add('variable', $object);
        $this->assertTrue($this->variableContainer->exists('variable'));
        $this->assertTrue(isset($this->variableContainer['variable']));
    }

    /**
     * @test
     */
    public function addedObjectsExistInAllIdentifiers()
    {
        $object = 'StringObject';
        $this->variableContainer->add('variable', $object);
        $this->assertEquals($this->variableContainer->getAllIdentifiers(), ['variable'], 'Added key is not visible in getAllIdentifiers');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException
     */
    public function duplicateIdentifiersThrowException()
    {
        $this->variableContainer->add('variable', 'string1');
        $this->variableContainer['variable'] = 'string2';
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException
     */
    public function addingReservedIdentifiersThrowException()
    {
        $this->variableContainer->add('TrUe', 'someValue');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException
     */
    public function gettingNonexistentValueThrowsException()
    {
        $this->variableContainer->get('nonexistent');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException
     */
    public function deletingNonexistentValueThrowsException()
    {
        $this->variableContainer->remove('nonexistent');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException
     */
    public function removeReallyRemovesVariables()
    {
        $this->variableContainer->add('variable', 'string1');
        $this->variableContainer->remove('variable');
        $this->variableContainer->get('variable');
    }

    /**
     * @test
     */
    public function whenVariablesAreEmpty_getAll_shouldReturnEmptyArray()
    {
        $this->assertSame([], $this->variableContainer->get('_all'));
    }

    /**
     * @test
     */
    public function getAllShouldReturnAllVariables()
    {
        $this->variableContainer->add('name', 'Simon');
        $this->assertSame(['name' => 'Simon'], $this->variableContainer->get('_all'));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException
     */
    public function addingVariableNamedAllShouldThrowException()
    {
        $this->variableContainer->add('_all', 'foo');
    }
}
