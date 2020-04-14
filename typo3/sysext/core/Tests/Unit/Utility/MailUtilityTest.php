<?php

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

namespace TYPO3\CMS\Core\Tests\Unit\Utility;

use TYPO3\CMS\Core\Utility\MailUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the \TYPO3\CMS\Core\Utility\MailUtility class.
 */
class MailUtilityTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function breakLinesForEmailReturnsEmptyStringIfEmptyStringIsGiven()
    {
        self::assertEmpty(MailUtility::breakLinesForEmail(''));
    }

    /**
     * @test
     */
    public function breakLinesForEmailReturnsOneLineIfCharWithIsNotExceeded()
    {
        $newlineChar = LF;
        $lineWidth = 76;
        $str = 'This text is not longer than 76 chars and therefore will not be broken.';
        $returnString = MailUtility::breakLinesForEmail($str, $newlineChar, $lineWidth);
        self::assertEquals(1, count(explode($newlineChar, $returnString)));
    }

    /**
     * @test
     */
    public function breakLinesForEmailBreaksTextIfCharWithIsExceeded()
    {
        $newlineChar = LF;
        $lineWidth = 50;
        $str = 'This text is longer than 50 chars and therefore will be broken.';
        $returnString = MailUtility::breakLinesForEmail($str, $newlineChar, $lineWidth);
        self::assertEquals(2, count(explode($newlineChar, $returnString)));
    }

    /**
     * @test
     */
    public function breakLinesForEmailBreaksTextWithNoSpaceFoundBeforeLimit()
    {
        $newlineChar = LF;
        $lineWidth = 10;
        // first space after 20 chars (more than $lineWidth)
        $str = 'abcdefghijklmnopqrst uvwxyz 123456';
        $returnString = MailUtility::breakLinesForEmail($str, $newlineChar, $lineWidth);
        self::assertEquals($returnString, 'abcdefghijklmnopqrst' . LF . 'uvwxyz' . LF . '123456');
    }

    /**
     * @test
     */
    public function breakLinesForEmailBreaksTextIfLineIsLongerThanTheLineWidth()
    {
        $str = 'Mein Link auf eine News (Link: http://zzzzzzzzzzzzz.xxxxxxxxx.de/index.php?id=10&tx_ttnews%5Btt_news%5D=1&cHash=66f5af320da29b7ae1cda49047ca7358)';
        $returnString = MailUtility::breakLinesForEmail($str);
        self::assertEquals($returnString, 'Mein Link auf eine News (Link:' . LF . 'http://zzzzzzzzzzzzz.xxxxxxxxx.de/index.php?id=10&tx_ttnews%5Btt_news%5D=1&cHash=66f5af320da29b7ae1cda49047ca7358)');
    }

    /**
     * Data provider for parseAddressesTest
     *
     * @return array Data sets
     */
    public function parseAddressesProvider()
    {
        return [
            'name &ltemail&gt;' => ['name <email@example.org>', ['email@example.org' => 'name']],
            '&lt;email&gt;' => ['<email@example.org>', ['email@example.org']],
            '@localhost' => ['@localhost', []],
            '000@example.com' => ['000@example.com', ['000@example.com']],
            'email' => ['email@example.org', ['email@example.org']],
            'email1,email2' => ['email1@example.org,email2@example.com', ['email1@example.org', 'email2@example.com']],
            'name &ltemail&gt;,email2' => ['name <email1@example.org>,email2@example.com', ['email1@example.org' => 'name', 'email2@example.com']],
            '"last, first" &lt;name@example.org&gt;' => ['"last, first" <email@example.org>', ['email@example.org' => '"last, first"']],
            'email,name &ltemail&gt;,"last, first" &lt;name@example.org&gt;' => [
                'email1@example.org, name <email2@example.org>, "last, first" <email3@example.org>',
                [
                    'email1@example.org',
                    'email2@example.org' => 'name',
                    'email3@example.org' => '"last, first"'
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider parseAddressesProvider
     */
    public function parseAddressesTest($source, $addressList)
    {
        $returnArray = MailUtility::parseAddresses($source);
        self::assertEquals($addressList, $returnArray);
    }

    /**
     * @return array
     */
    public function replyToProvider(): array
    {
        return [
            'only address' => [
                ['defaultMailReplyToAddress' => 'noreply@example.org', 'defaultMailReplyToName' => ''],
                ['noreply@example.org'],
            ],
            'name and address' => [
                ['defaultMailReplyToAddress' => 'noreply@example.org', 'defaultMailReplyToName' => 'John Doe'],
                ['noreply@example.org' => 'John Doe'],
            ],
            'no address' => [
                ['defaultMailReplyToAddress' => '', 'defaultMailReplyToName' => ''],
                [],
            ],
            'invalid address' => [
                ['defaultMailReplyToAddress' => 'foo', 'defaultMailReplyToName' => ''],
                [],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider replyToProvider
     * @param array $configuration
     * @param array $expectedReplyTo
     */
    public function getSystemReplyToTest(array $configuration, array $expectedReplyTo)
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL'] = $configuration;
        $returnArray = MailUtility::getSystemReplyTo();
        self::assertSame($expectedReplyTo, $returnArray);
    }
}
