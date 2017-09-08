<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Tests\Unit\Domain\FormElements;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Exception\IdentifierNotValidException;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AbstractFormElementTest extends UnitTestCase
{
    /**
     * @test
     */
    public function newInstanceHasNoProperties()
    {
        /** @var AbstractFormElement $subject */
        $subject = $this->getMockForAbstractClass(AbstractFormElement::class, ['an_id', 'a_type']);
        $this->assertNotNull($subject);
        $this->assertCount(0, $subject->getProperties());
    }

    /**
     * @test
     */
    public function setSimpleProperties()
    {
        /** @var AbstractFormElement $subject */
        $subject = $this->getMockForAbstractClass(AbstractFormElement::class, ['an_id', 'a_type']);

        $subject->setProperty('foo', 'bar');
        $subject->setProperty('buz', 'qax');
        $properties = $subject->getProperties();

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
        /** @var AbstractFormElement $subject */
        $subject = $this->getMockForAbstractClass(AbstractFormElement::class, ['an_id', 'a_type']);

        $subject->setProperty('foo', 'bar');
        $subject->setProperty('foo', 'buz');

        $properties = $subject->getProperties();
        $this->assertEquals(1, count($properties));
        $this->assertTrue(array_key_exists('foo', $properties));
        $this->assertEquals('buz', $properties['foo']);
    }

    /**
     * @test
     */
    public function setArrayProperties()
    {
        /** @var AbstractFormElement $subject */
        $subject = $this->getMockForAbstractClass(AbstractFormElement::class, ['an_id', 'a_type']);

        $subject->setProperty('foo', ['bar' => 'baz', 'bla' => 'blubb']);
        $properties = $subject->getProperties();

        $this->assertCount(1, $properties);
        $this->assertTrue(array_key_exists('foo', $properties));

        //check arrays details
        $this->assertTrue(is_array($properties['foo']));
        $this->assertCount(2, $properties['foo']);
        $this->assertTrue(array_key_exists('bar', $properties['foo']));
        $this->assertEquals('baz', $properties['foo']['bar']);
    }

    /**
     * @test
     */
    public function constructThrowsExceptionWhenIdentifierIsEmpty()
    {
        $this->expectException(IdentifierNotValidException::class);
        $this->expectExceptionCode(1477082502);

        $this->getAccessibleMockForAbstractClass(
            AbstractFormElement::class,
            ['', 'a_type'],
            '',
            true,
            true,
            true,
            []
        );
    }

    /**
     * @test
     */
    public function constructMustNotThrowExceptionWhenIdentifierIsNonEmptyString()
    {
        $mock = $this->getAccessibleMockForAbstractClass(
            AbstractFormElement::class,
            ['is_in', 'a_type'],
            '',
            true,
            true,
            true,
            []
        );
        self::assertInstanceOf(AbstractFormElement::class, $mock);
    }

    /**
     * @test
     */
    public function initializeFormElementExpectedCallInitializeFormObjectHooks()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|AbstractFormElement $abstractFormElementMock */
        $abstractFormElementMock = $this->getAccessibleMockForAbstractClass(
            AbstractFormElement::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|AbstractFormElement $secondMock */
        $secondMock = $this->getAccessibleMockForAbstractClass(
            AbstractFormElement::class,
            [],
            '',
            false,
            false,
            true,
            [
                'initializeFormElement'
            ]
        );

        $secondMock->
        expects($this->once())
            ->method('initializeFormElement')
            ->with($abstractFormElementMock);

        GeneralUtility::addInstance(get_class($secondMock), $secondMock);

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['initializeFormElement'] = [
            get_class($secondMock)
        ];

        $abstractFormElementMock->initializeFormElement();
    }

    /**
     * @test
     */
    public function getUniqueIdentifierExpectedUnique()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|AbstractFormElement $abstractFormElementMock1 */
        $abstractFormElementMock1 = $this->getAccessibleMockForAbstractClass(
            AbstractFormElement::class,
            [],
            '',
            false,
            false,
            true,
            [
                'getRootForm'
            ]
        );

        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|AbstractFormElement $abstractFormElementMock2 */
        $abstractFormElementMock2 = $this->getAccessibleMockForAbstractClass(
            AbstractFormElement::class,
            [],
            '',
            false,
            false,
            true,
            [
                'getRootForm'
            ]
        );

        $formDefinition1 = $this->createMock(FormDefinition::class);
        $formDefinition1
            ->method('getIdentifier')
            ->willReturn('c');

        $abstractFormElementMock1
            ->method('getRootForm')
            ->willReturn($formDefinition1);

        $formDefinition2 = $this->createMock(FormDefinition::class);
        $formDefinition2
            ->method('getIdentifier')
            ->willReturn('d');

        $abstractFormElementMock2
            ->method('getRootForm')
            ->willReturn($formDefinition2);

        self::assertNotSame(
            $abstractFormElementMock1->getUniqueIdentifier(),
            $abstractFormElementMock2->getUniqueIdentifier()
        );
    }
}
