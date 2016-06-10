<?php
namespace TYPO3\CMS\Form\Tests\Unit\Utility;

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

use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Form\Domain\Model\Json\FormJsonElement;
use TYPO3\CMS\Form\Utility\TypoScriptToJsonConverter;

/**
 * Test case for \TYPO3\CMS\Form\Utility\TypoScriptToJsonConverter
 */
class TypoScriptToJsonConverterTest extends UnitTestCase
{
    /**
     * Checks if calling protected method getChildElementsByIntegerKey with different data
     * calls the addMethod in the mocked FormJsonElement for an expected method count.
     *
     * @dataProvider getChildElementsByIntegerKeyCallsAddElementDataProvider
     * @param array $typoScript
     * @param int $methodCount
     * @test
     */
    public function getChildElementsByIntegerKeyCallsAddElement(array $typoScript, $methodCount)
    {
        /** @var FormJsonElement|\PHPUnit_Framework_MockObject_MockObject $mockSubject */
        $parentElement = $this->getMock(FormJsonElement::class, ['addElement']);
        // check if method gets called exactly X times
        $parentElement->expects($this->exactly($methodCount))->method('addElement');

        /** @var TypoScriptToJsonConverter|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface $subjectAccessible */
        $accessibleSubject = $this->getAccessibleMock(TypoScriptToJsonConverter::class, ['dummy']);
        $accessibleSubject->_call('getChildElementsByIntegerKey', $parentElement, $typoScript);
    }

    /**
     * Data provider for test method getChildElementsByIntegerKeyCallsAddElement.
     *
     * @return array
     */
    public function getChildElementsByIntegerKeyCallsAddElementDataProvider()
    {
        return [
            [
                'typoscript' => [
                    'prefix' => 'tx_form',
                    'confirmation' => '1',
                    'postProcessor.' => [
                        '1' => 'mail',
                        '1.' => [
                            'recipientEmail' => '',
                            'senderEmail' => '',
                        ],
                    ],
                    '10' => 'FILEUPLOAD',
                    '10.' => [
                        'name' => 'foo',
                        'type' => 'file',
                        'label.' => [
                            'value' => 'Edit this label',
                        ],
                    ],
                ],
                1
            ],
        ];
    }
}
