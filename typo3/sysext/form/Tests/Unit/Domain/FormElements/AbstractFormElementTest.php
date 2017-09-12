<?php
namespace TYPO3\CMS\Form\Tests\Unit\Domain\FormElements;

use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement;

/**
 * Test TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement class
 *
 * Class AbstractFormElementTest
 */
class AbstractFormElementTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    protected static $IDENTIFIER = 'an_id';
    protected static $TYPE = 'a_type';

    /**
     * An accessible instance of abstract class under test
     * @var AbstractFormElement
     */
    protected $abstractFormElementInstance = null;

    /**
     * @before
     */
    public function setUp()
    {
        parent::setUp();
        $this->abstractFormElementInstance = new class(self::$IDENTIFIER, self::$TYPE) extends AbstractFormElement {
        };
    }

    /**
     * @test
     */
    public function newInstanceHasNoProperties()
    {
        $this->assertNotNull($this->abstractFormElementInstance);
        $this->assertCount(0, $this->abstractFormElementInstance->getProperties());
    }

    /**
     * @test
     */
    public function setSimpleProperties()
    {
        $this->abstractFormElementInstance->setProperty('foo', 'bar');
        $this->abstractFormElementInstance->setProperty('buz', 'qax');
        $properties = $this->abstractFormElementInstance->getProperties();

        $this->assertCount(2, $properties);
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
        $this->abstractFormElementInstance->setProperty('foo', 'bar');
        $this->abstractFormElementInstance->setProperty('foo', 'buz');

        $properties = $this->abstractFormElementInstance->getProperties();
        $this->assertEquals(1, count($properties));
        $this->assertTrue(array_key_exists('foo', $properties));
        $this->assertEquals('buz', $properties['foo']);
    }

    /**
     * @test
     */
    public function setArrayProperties()
    {
        $this->abstractFormElementInstance->setProperty('foo', ['bar' => 'baz', 'bla' => 'blubb']);
        $properties = $this->abstractFormElementInstance->getProperties();

        $this->assertCount(1, $properties);
        $this->assertTrue(array_key_exists('foo', $properties));

        //check arrays details
        $this->assertTrue(is_array($properties['foo']));
        $this->assertCount(2, $properties['foo']);
        $this->assertTrue(array_key_exists('bar', $properties['foo']));
        $this->assertEquals('baz', $properties['foo']['bar']);
    }
}
