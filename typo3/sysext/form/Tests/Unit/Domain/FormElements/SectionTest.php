<?php
namespace TYPO3\CMS\Form\Tests\Unit\Domain\FormElements;

use TYPO3\CMS\Form\Domain\Model\FormElements\Section;

/**
 * Test TYPO3\CMS\Form\Domain\Model\FormElements\Section class
 *
 * Class AbstractFormElementTest
 */
class SectionTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    protected static $IDENTIFIER = 'an_id';
    protected static $TYPE = 'a_type';

    /**
     * An instance of section
     * @var Section
     */
    protected $sectionInstance = null;

    /**
     * @before
     */
    public function setUp()
    {
        parent::setUp();
        $this->sectionInstance = new Section(self::$IDENTIFIER, self::$TYPE);
    }

    /**
     * @test
     */
    public function newInstanceHasNoProperties()
    {
        $this->assertNotNull($this->sectionInstance);
        $this->assertCount(0, $this->sectionInstance->getProperties());
    }

    /**
     * @test
     */
    public function setSimpleProperties()
    {
        $this->sectionInstance->setProperty('foo', 'bar');
        $this->sectionInstance->setProperty('buz', 'qax');
        $properties = $this->sectionInstance->getProperties();

        $this->assertCount(2, $properties, json_encode($properties));
        $this->assertTrue(array_key_exists('foo', $properties));
        $this->assertEquals('bar', $properties['foo']);
        $this->assertTrue(array_key_exists('buz', $properties));
        $this->assertEquals('qax', $properties['buz']);
    }

    /**
     * @test
     */
    public function overrideProperties()
    {
        $this->sectionInstance->setProperty('foo', 'bar');
        $this->sectionInstance->setProperty('foo', 'buz');

        $properties = $this->sectionInstance->getProperties();
        $this->assertEquals(1, count($properties));
        $this->assertTrue(array_key_exists('foo', $properties));
        $this->assertEquals('buz', $properties['foo']);
    }

    /**
     * @test
     */
    public function setArrayProperties()
    {
        $this->sectionInstance->setProperty('foo', ['bar' => 'baz', 'bla' => 'blubb']);
        $properties = $this->sectionInstance->getProperties();

        $this->assertCount(1, $properties);
        $this->assertTrue(array_key_exists('foo', $properties));

        //check arrays details
        $this->assertTrue(is_array($properties['foo']));
        $this->assertCount(2, $properties['foo']);
        $this->assertTrue(array_key_exists('bar', $properties['foo']));
        $this->assertEquals('baz', $properties['foo']['bar']);
    }
}
