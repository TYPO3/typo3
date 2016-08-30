<?php
namespace TYPO3\CMS\Form\Tests\Unit\PostProcess;

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
 * Test case
 */
class MailPostProcessorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Form\PostProcess\MailPostProcessor
     */
    protected $mailPostProcessor;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->mailPostProcessor = $this->getAccessibleMock(
            \TYPO3\CMS\Form\PostProcess\MailPostProcessor::class,
            ['__none'],
            [],
            '',
            false
        );
    }

    /**
     * Data provider for filterValidEmailsReturnsOnlyValidAddresses
     *
     * @return array input string, expected return array
     * @TODO: Add a umlaut domain test case
     */
    public function filterValidEmailsProvider()
    {
        return [
            'empty string' => [
                '',
                [],
            ],
            'string not representing an email' => [
                'notAnAddress',
                [],
            ],
            'simple single valid address' => [
                'someone@example.com',
                [
                    'someone@example.com',
                ],
            ],
            'multiple valid simple addresses' => [
                'someone@example.com, foo@bar.com',
                [
                    'someone@example.com',
                    'foo@bar.com',
                ],
            ],
            'multiple addresses with personal part' => [
                'Foo <foo@example.com>, <bar@example.com>, "Foo, bar" <foo.bar@example.com>',
                [
                    'bar@example.com',
                    'foo@example.com' => 'Foo',
                    'foo.bar@example.com' => '"Foo, bar"',
                ],
            ],
            'list with invalid addresses is filtered' => [
                'invalid, @invalid, someone@example.com',
                [
                    'someone@example.com',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider filterValidEmailsProvider
     */
    public function filterValidEmailsReturnsOnlyValidAddresses($input, $expected)
    {
        $actualResult = $this->mailPostProcessor->_call('filterValidEmails', $input);
        $this->assertEquals($expected, $actualResult);
    }
}
