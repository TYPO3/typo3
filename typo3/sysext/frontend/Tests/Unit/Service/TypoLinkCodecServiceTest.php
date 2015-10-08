<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\Service;

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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;

/**
 * Test case
 */
class TypoLinkCodecServiceTest extends UnitTestCase
{
    /**
     * @var TypoLinkCodecService
     */
    protected $subject;

    /**
     * Set up test subject
     */
    protected function setUp()
    {
        $this->subject = new TypoLinkCodecService();
    }

    /**
     * @test
     * @dataProvider encodeReturnsExpectedResultDataProvider
     * @param array $parts
     * @param string$expected
     */
    public function encodeReturnsExpectedResult(array $parts, $expected)
    {
        $this->assertSame($expected, $this->subject->encode($parts));
    }

    /**
     * @return array
     */
    public function encodeReturnsExpectedResultDataProvider()
    {
        return [
            'empty input' => [
                [
                    'url' => '',
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => ''
                ],
                ''
            ],
            'full parameter usage' => [
                [
                    'url' => '19',
                    'target' => '_blank',
                    'class' => 'css-class',
                    'title' => 'testtitle with whitespace',
                    'additionalParams' => '&x=y'
                ],
                '19 _blank css-class "testtitle with whitespace" &x=y'
            ],
            'crazy title and partial items only' => [
                [
                    'url' => 'foo',
                    'title' => 'a "link\\ ti\\"tle',
                ],
                'foo - - "a \\"link\\\\ ti\\\\\\"tle"'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider decodeReturnsExpectedResultDataProvider
     * @param string $typoLink
     * @param array $expected
     */
    public function decodeReturnsExpectedResult($typoLink, array $expected)
    {
        $this->assertSame($expected, $this->subject->decode($typoLink));
    }

    /**
     * @return array
     */
    public function decodeReturnsExpectedResultDataProvider()
    {
        return [
            'empty input' => [
                '',
                [
                    'url' => '',
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => ''
                ],
            ],
            'simple id input' => [
                '19',
                [
                    'url' => '19',
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => ''
                ],
            ],
            'external url with target' => [
                'www.web.de _blank',
                [
                    'url' => 'www.web.de',
                    'target' => '_blank',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => ''
                ],
            ],
            'page with class' => [
                '42 - css-class',
                [
                    'url' => '42',
                    'target' => '',
                    'class' => 'css-class',
                    'title' => '',
                    'additionalParams' => ''
                ],
            ],
            'page with title' => [
                '42 - - "a link title"',
                [
                    'url' => '42',
                    'target' => '',
                    'class' => '',
                    'title' => 'a link title',
                    'additionalParams' => ''
                ],
            ],
            'page with crazy title' => [
                '42 - - "a \\"link\\\\ ti\\\\\\"tle"',
                [
                    'url' => '42',
                    'target' => '',
                    'class' => '',
                    'title' => 'a "link\\ ti\\"tle',
                    'additionalParams' => ''
                ],
            ],
            'page with title and parameters' => [
                '42 - - "a link title" &x=y',
                [
                    'url' => '42',
                    'target' => '',
                    'class' => '',
                    'title' => 'a link title',
                    'additionalParams' => '&x=y'
                ],
            ],
            'page with complex title' => [
                '42 - - "a \\"link\\" title with \\\\" &x=y',
                [
                    'url' => '42',
                    'target' => '',
                    'class' => '',
                    'title' => 'a "link" title with \\',
                    'additionalParams' => '&x=y'
                ],
            ],
            'full parameter usage' => [
                '19 _blank css-class "testtitle with whitespace" &X=y',
                [
                    'url' => '19',
                    'target' => '_blank',
                    'class' => 'css-class',
                    'title' => 'testtitle with whitespace',
                    'additionalParams' => '&X=y'
                ],
            ],
        ];
    }
}
