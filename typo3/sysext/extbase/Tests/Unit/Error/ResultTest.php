<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Error;

/*                                                                        *
 * This script belongs to the Extbase framework.                          *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Test case
 */
class ResultTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Error\Result
     */
    protected $result;

    protected function setUp()
    {
        $this->result = new \TYPO3\CMS\Extbase\Error\Result();
    }

    /**
     * @return array
     */
    public function dataTypes()
    {
        return [
            ['Error', 'Errors'],
            ['Warning', 'Warnings'],
            ['Notice', 'Notices']
        ];
    }

    /**
     * @param string $type
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockMessage($type)
    {
        return $this->getMock('TYPO3\\CMS\\Extbase\\Error\\' . $type, [], [], '', false);
    }

    /**
     * @test
     * @dataProvider dataTypes
     * @param string $dataTypeInSingular
     * @param string $dataTypeInPlural
     */
    public function addedMessagesShouldBeRetrievableAgain($dataTypeInSingular, $dataTypeInPlural)
    {
        $message = $this->getMockMessage($dataTypeInSingular);
        $addMethodName = 'add' . $dataTypeInSingular;
        $this->result->{$addMethodName}($message);
        $getterMethodName = 'get' . $dataTypeInPlural;
        $this->assertEquals([$message], $this->result->{$getterMethodName}());
    }

    /**
     * @test
     * @dataProvider dataTypes
     * @param string $dataTypeInSingular
     * @param string $dataTypeInPlural
     */
    public function getMessageShouldNotBeRecursive($dataTypeInSingular, $dataTypeInPlural)
    {
        $message = $this->getMockMessage($dataTypeInSingular);
        $addMethodName = 'add' . $dataTypeInSingular;
        $this->result->forProperty('foo')->{$addMethodName}($message);
        $getterMethodName = 'get' . $dataTypeInPlural;
        $this->assertEquals([], $this->result->{$getterMethodName}());
    }

    /**
     * @test
     * @dataProvider dataTypes
     * @param string $dataTypeInSingular
     * @param string $dataTypeInPlural
     */
    public function getFirstMessageShouldReturnFirstMessage($dataTypeInSingular, $dataTypeInPlural)
    {
        $message1 = $this->getMockMessage($dataTypeInSingular);
        $message2 = $this->getMockMessage($dataTypeInSingular);
        $addMethodName = 'add' . $dataTypeInSingular;
        $this->result->{$addMethodName}($message1);
        $this->result->{$addMethodName}($message2);
        $getterMethodName = 'getFirst' . $dataTypeInSingular;
        $this->assertSame($message1, $this->result->{$getterMethodName}());
    }

    /**
     * @test
     */
    public function forPropertyShouldReturnSubResult()
    {
        $container2 = $this->result->forProperty('foo.bar');
        $this->assertInstanceOf(\TYPO3\CMS\Extbase\Error\Result::class, $container2);
        $this->assertSame($container2, $this->result->forProperty('foo')->forProperty('bar'));
    }

    /**
     * @test
     */
    public function forPropertyWithEmptyStringShouldReturnSelf()
    {
        $container2 = $this->result->forProperty('');
        $this->assertSame($container2, $this->result);
    }

    /**
     * @test
     */
    public function forPropertyWithNullShouldReturnSelf()
    {
        $container2 = $this->result->forProperty(null);
        $this->assertSame($container2, $this->result);
    }

    /**
     * @test
     * @dataProvider dataTypes
     * @param string $dataTypeInSingular
     * @param string $dataTypeInPlural
     */
    public function hasMessagesShouldReturnTrueIfTopLevelObjectHasMessages($dataTypeInSingular, $dataTypeInPlural)
    {
        $message = $this->getMockMessage($dataTypeInSingular);
        $addMethodName = 'add' . $dataTypeInSingular;
        $this->result->{$addMethodName}($message);
        $methodName = 'has' . $dataTypeInPlural;
        $this->assertTrue($this->result->{$methodName}());
    }

    /**
     * @test
     * @dataProvider dataTypes
     * @param string $dataTypeInSingular
     * @param string $dataTypeInPlural
     */
    public function hasMessagesShouldReturnTrueIfSubObjectHasErrors($dataTypeInSingular, $dataTypeInPlural)
    {
        $addMethodName = 'add' . $dataTypeInSingular;
        $methodName = 'has' . $dataTypeInPlural;
        $message = $this->getMockMessage($dataTypeInSingular);
        $this->result->forProperty('foo.bar')->{$addMethodName}($message);
        $this->assertTrue($this->result->{$methodName}());
    }

    /**
     * @test
     * @dataProvider dataTypes
     * @param string $dataTypeInSingular
     * @param string $dataTypeInPlural
     */
    public function hasMessagesShouldReturnFalseIfSubObjectHasNoErrors($dataTypeInSingular, $dataTypeInPlural)
    {
        $methodName = 'has' . $dataTypeInPlural;
        $this->result->forProperty('foo.baz');
        $this->result->forProperty('foo.bar');
        $this->assertFalse($this->result->{$methodName}());
    }

    /**
     * @test
     * @dataProvider dataTypes
     * @param string $dataTypeInSingular
     * @param string $dataTypeInPlural
     */
    public function getFlattenedMessagesShouldReturnAllSubMessages($dataTypeInSingular, $dataTypeInPlural)
    {
        $message1 = $this->getMockMessage($dataTypeInSingular);
        $message2 = $this->getMockMessage($dataTypeInSingular);
        $message3 = $this->getMockMessage($dataTypeInSingular);
        $message4 = $this->getMockMessage($dataTypeInSingular);
        $message5 = $this->getMockMessage($dataTypeInSingular);
        $addMethodName = 'add' . $dataTypeInSingular;
        $this->result->forProperty('foo.bar')->{$addMethodName}($message1);
        $this->result->forProperty('foo.baz')->{$addMethodName}($message2);
        $this->result->forProperty('foo')->{$addMethodName}($message3);
        $this->result->{$addMethodName}($message4);
        $this->result->{$addMethodName}($message5);
        $getMethodName = 'getFlattened' . $dataTypeInPlural;
        $expected = [
            '' => [$message4, $message5],
            'foo' => [$message3],
            'foo.bar' => [$message1],
            'foo.baz' => [$message2]
        ];
        $this->assertEquals($expected, $this->result->{$getMethodName}());
    }

    /**
     * @test
     * @dataProvider dataTypes
     * @param string $dataTypeInSingular
     * @param string $dataTypeInPlural
     */
    public function getFlattenedMessagesShouldNotContainEmptyResults($dataTypeInSingular, $dataTypeInPlural)
    {
        $message1 = $this->getMockMessage($dataTypeInSingular);
        $message2 = $this->getMockMessage($dataTypeInSingular);
        $addMethodName = 'add' . $dataTypeInSingular;
        $this->result->forProperty('foo.bar')->{$addMethodName}($message1);
        $this->result->forProperty('foo.baz')->{$addMethodName}($message2);
        $getMethodName = 'getFlattened' . $dataTypeInPlural;
        $expected = [
            'foo.bar' => [$message1],
            'foo.baz' => [$message2]
        ];
        $this->assertEquals($expected, $this->result->{$getMethodName}());
    }

    /**
     * @test
     */
    public function mergeShouldMergeTwoResults()
    {
        $notice1 = $this->getMockMessage('Notice');
        $notice2 = $this->getMockMessage('Notice');
        $notice3 = $this->getMockMessage('Notice');
        $warning1 = $this->getMockMessage('Warning');
        $warning2 = $this->getMockMessage('Warning');
        $warning3 = $this->getMockMessage('Warning');
        $error1 = $this->getMockMessage('Error');
        $error2 = $this->getMockMessage('Error');
        $error3 = $this->getMockMessage('Error');
        $otherResult = new \TYPO3\CMS\Extbase\Error\Result();
        $otherResult->addNotice($notice1);
        $otherResult->forProperty('foo.bar')->addNotice($notice2);
        $this->result->forProperty('foo')->addNotice($notice3);
        $otherResult->addWarning($warning1);
        $this->result->addWarning($warning2);
        $this->result->addWarning($warning3);
        $otherResult->forProperty('foo')->addError($error1);
        $otherResult->forProperty('foo')->addError($error2);
        $otherResult->addError($error3);
        $this->result->merge($otherResult);
        $this->assertSame([$notice1], $this->result->getNotices(), 'Notices are not merged correctly without recursion');
        $this->assertSame([$notice3], $this->result->forProperty('foo')->getNotices(), 'Original sub-notices are overridden.');
        $this->assertSame([$notice2], $this->result->forProperty('foo')->forProperty('bar')->getNotices(), 'Sub-notices are not copied.');
        $this->assertSame([$warning2, $warning3, $warning1], $this->result->getWarnings());
        $this->assertSame([$error3], $this->result->getErrors());
        $this->assertSame([$error1, $error2], $this->result->forProperty('foo')->getErrors());
    }
}
