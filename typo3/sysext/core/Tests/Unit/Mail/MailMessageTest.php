<?php
namespace TYPO3\CMS\Core\Tests\Unit\Mail;

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

/**
 * Testcase for the TYPO3\CMS\Core\Mail\MailMessage class.
 */
class MailMessageTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Mail\MailMessage
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);
    }

    /**
     * @returns array
     */
    public function returnPathEmailAddressDataProvider()
    {
        return [
            'string with ascii email address' => [
                'john.doe@example.com',
                'john.doe@example.com'
            ],
            'string with utf8 email address' => [
                'john.doe@☺example.com',
                'john.doe@xn--example-tc7d.com'
            ]
        ];
    }

    /**
     * @test
     * @param string $address
     * @param string $expected
     * @dataProvider returnPathEmailAddressDataProvider
     */
    public function setReturnPathIdnaEncodesAddresses($address, $expected)
    {
        $this->subject->setReturnPath($address);

        $this->assertSame($expected, $this->subject->getReturnPath());
    }

    /**
     * @returns array
     */
    public function senderEmailAddressDataProvider()
    {
        return [
            'string with ascii email address' => [
                'john.doe@example.com',
                [
                    'john.doe@example.com' => null,
                ]
            ],
            'string with utf8 email address' => [
                'john.doe@☺example.com',
                [
                    'john.doe@xn--example-tc7d.com' => null,
                ]
            ]
        ];
    }

    /**
     * @test
     * @param string $address
     * @param array $expected
     * @dataProvider senderEmailAddressDataProvider
     */
    public function setSenderIdnaEncodesAddresses($address, $expected)
    {
        $this->subject->setSender($address);

        $this->assertSame($expected, $this->subject->getSender());
    }

    /**
     * @returns array
     */
    public function emailAddressesDataProvider()
    {
        return [
            'string with ascii email address' => [
                'john.doe@example.com',
                [
                    'john.doe@example.com' => null
                ]
            ],
            'string with utf8 email address' => [
                'john.doe@☺example.com',
                [
                    'john.doe@xn--example-tc7d.com' => null
                ]
            ],
            'array with ascii email addresses' => [
                [
                    'john.doe@example.com' => 'John Doe',
                    'jane.doe@example.com'
                ],
                [
                    'john.doe@example.com' => 'John Doe',
                    'jane.doe@example.com' => null,
                ],
            ],
            'array with utf8 email addresses' => [
                [
                    'john.doe@☺example.com' => 'John Doe',
                    'jane.doe@äöu.com' => 'Jane Doe',
                ],
                [
                    'john.doe@xn--example-tc7d.com' => 'John Doe',
                    'jane.doe@xn--u-zfa8c.com' => 'Jane Doe',
                ],
            ],
            'array with mixed email addresses' => [
                [
                    'john.doe@☺example.com' => 'John Doe',
                    'jane.doe@example.com' => 'Jane Doe',
                ],
                [
                    'john.doe@xn--example-tc7d.com' => 'John Doe',
                    'jane.doe@example.com' => 'Jane Doe',
                ],
            ],
        ];
    }

    /**
     * @test
     * @param string|array $addresses
     * @param string|array $expected
     * @dataProvider emailAddressesDataProvider
     */
    public function setFromIdnaEncodesAddresses($addresses, $expected)
    {
        $this->subject->setFrom($addresses);

        $this->assertSame($expected, $this->subject->getFrom());
    }

    /**
     * @test
     * @param string|array $addresses
     * @param string|array $expected
     * @dataProvider emailAddressesDataProvider
     */
    public function setReplyToIdnaEncodesAddresses($addresses, $expected)
    {
        $this->subject->setReplyTo($addresses);

        $this->assertSame($expected, $this->subject->getReplyTo());
    }

    /**
     * @test
     * @param string|array $addresses
     * @param string|array $expected
     * @dataProvider emailAddressesDataProvider
     */
    public function setToIdnaEncodesAddresses($addresses, $expected)
    {
        $this->subject->setTo($addresses);

        $this->assertSame($expected, $this->subject->getTo());
    }

    /**
     * @test
     * @param string|array $addresses
     * @param string|array $expected
     * @dataProvider emailAddressesDataProvider
     */
    public function setCcIdnaEncodesAddresses($addresses, $expected)
    {
        $this->subject->setCc($addresses);

        $this->assertSame($expected, $this->subject->getCc());
    }

    /**
     * @test
     * @param string|array $addresses
     * @param string|array $expected
     * @dataProvider emailAddressesDataProvider
     */
    public function setBccIdnaEncodesAddresses($addresses, $expected)
    {
        $this->subject->setBcc($addresses);

        $this->assertSame($expected, $this->subject->getBcc());
    }

    /**
     * @test
     * @param string|array $addresses
     * @param string|array $expected
     * @dataProvider emailAddressesDataProvider
     */
    public function setReadReceiptToIdnaEncodesAddresses($addresses, $expected)
    {
        $this->subject->setReadReceiptTo($addresses);

        $this->assertSame($expected, $this->subject->getReadReceiptTo());
    }
}
