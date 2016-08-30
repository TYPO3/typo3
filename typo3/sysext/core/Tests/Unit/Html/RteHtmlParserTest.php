<?php
namespace TYPO3\CMS\Core\Tests\Unit\Html;

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
 * Testcase for \TYPO3\CMS\Core\Html\RteHtmlParser
 */
class RteHtmlParserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Html\RteHtmlParser
     */
    protected $subject = null;

    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Core\Html\RteHtmlParser();
        $this->subject->procOptions = [
            'dontConvBRtoParagraph' => '1',
            'preserveDIVSections' => '1',
            'allowTagsOutside' => 'hr, address',
            'disableUnifyLineBreaks' => '0',
            'overruleMode' => 'ts_css'
        ];
    }

    /**
     * Data provider for hrTagCorrectlyTransformedOnWayToDataBase
     */
    public static function hrTagCorrectlyTransformedOnWayToDataBaseDataProvider()
    {
        return [
            'Single hr' => [
                '<hr />',
                '<hr />',
            ],
            'Non-xhtml single hr' => [
                '<hr/>',
                '<hr />',
            ],
            'Double hr' => [
                '<hr /><hr />',
                '<hr />' . CRLF . '<hr />',
            ],
            'Linebreak followed by hr' => [
                CRLF . '<hr />',
                '<hr />',
            ],
            'White space followed by hr' => [
                ' <hr />',
                ' ' . CRLF . '<hr />',
            ],
            'White space followed linebreak and hr' => [
                ' ' . CRLF . '<hr />',
                ' ' . CRLF . '<hr />',
            ],
            'br followed by hr' => [
                '<br /><hr />',
                '<br />' . CRLF . '<hr />',
            ],
            'br followed by linebreak and hr' => [
                '<br />' . CRLF . '<hr />',
                '<br />' . CRLF . '<hr />',
            ],
            'Preserved div followed by hr' => [
                '<div>Some text</div><hr />',
                '<div>Some text</div>' . CRLF . '<hr />',
            ],
            'Preserved div followed by linebreak and hr' => [
                '<div>Some text</div>' . CRLF . '<hr />',
                '<div>Some text</div>' . CRLF . '<hr />',
            ],
            'h1 followed by linebreak and hr' => [
                '<h1>Some text</h1>' . CRLF . '<hr />',
                '<h1>Some text</h1>' . CRLF . '<hr />',
            ],
            'Paragraph followed by linebreak and hr' => [
                '<p>Some text</p>' . CRLF . '<hr />',
                'Some text' . CRLF . '<hr />',
            ],
            'Some text followed by hr' => [
                'Some text<hr />',
                'Some text' . CRLF . '<hr />',
            ],
            'Some text followed by linebreak and hr' => [
                'Some text' . CRLF . '<hr />',
                'Some text' . CRLF . '<hr />',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider hrTagCorrectlyTransformedOnWayToDataBaseDataProvider
     */
    public function hrTagCorrectlyTransformedOnWayToDataBase($content, $expectedResult)
    {
        $thisConfig = ['proc.' => $this->subject->procOptions];
        $this->assertEquals($expectedResult, $this->subject->RTE_transform($content, [], 'db', $thisConfig));
    }

    /**
     * Data provider for hrTagCorrectlyTransformedOnWayToDatabaseAndBackToRteProvider
     */
    public static function hrTagCorrectlyTransformedOnWayToDatabaseAndBackToRteProvider()
    {
        return [
            'Single hr' => [
                '<hr />',
                '<hr />',
            ],
            'Non-xhtml single hr' => [
                '<hr/>',
                '<hr />',
            ],
            'Double hr' => [
                '<hr /><hr />',
                '<hr />' . CRLF . '<hr />',
            ],
            'Linebreak followed by hr' => [
                CRLF . '<hr />',
                '<hr />',
            ],
            'White space followed by hr' => [
                ' <hr />',
                '<p>&nbsp;</p>' . CRLF . '<hr />',
            ],
            'White space followed by linebreak and hr' => [
                ' ' . CRLF . '<hr />',
                '<p>&nbsp;</p>' . CRLF . '<hr />',
            ],
            'br followed by hr' => [
                '<br /><hr />',
                '<p><br /></p>' . CRLF . '<hr />',
            ],
            'br followed by linebreak and hr' => [
                '<br />' . CRLF . '<hr />',
                '<p><br /></p>' . CRLF . '<hr />',
            ],
            'Preserved div followed by hr' => [
                '<div>Some text</div>' . '<hr />',
                '<div><p>Some text</p></div>' . CRLF . '<hr />',
            ],
            'Preserved div followed by linebreak and hr' => [
                '<div>Some text</div>' . CRLF . '<hr />',
                '<div><p>Some text</p></div>' . CRLF . '<hr />',
            ],
            'h1 followed by linebreak and hr' => [
                '<h1>Some text</h1>' . CRLF . '<hr />',
                '<h1>Some text</h1>' . CRLF . '<hr />',
            ],
            'Paragraph followed by linebreak and hr' => [
                '<p>Some text</p>' . CRLF . '<hr />',
                '<p>Some text</p>' . CRLF . '<hr />',
            ],
            'Some text followed by hr' => [
                'Some text<hr />',
                '<p>Some text</p>' . CRLF . '<hr />',
            ],
            'Some text followed by linebreak and hr' => [
                'Some text' . CRLF . '<hr />',
                '<p>Some text</p>' . CRLF . '<hr />',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider hrTagCorrectlyTransformedOnWayToDatabaseAndBackToRteProvider
     */
    public function hrTagCorrectlyTransformedOnWayToDatabaseAndBackToRte($content, $expectedResult)
    {
        $thisConfig = ['proc.' => $this->subject->procOptions];
        $this->assertEquals($expectedResult, $this->subject->RTE_transform($this->subject->RTE_transform($content, [], 'db', $thisConfig), [], 'rte', $thisConfig));
    }

    /**
     * Data provider for linkWithAtSignCorrectlyTransformedOnWayToRTE
     */
    public static function linkWithAtSignCorrectlyTransformedOnWayToRTEProvider()
    {
        return [
            'external url with @ sign' => [
                '<link http://www.example.org/at@sign>link text</link>',
                '<p><a href="http://www.example.org/at@sign" data-htmlarea-external="1">link text</a></p>'
            ],
            'email address with @ sign' => [
                '<link name@example.org - mail "Opens window for sending email">link text</link>',
                '<p><a href="mailto:name@example.org" class="mail" title="Opens window for sending email">link text</a></p>'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider linkWithAtSignCorrectlyTransformedOnWayToRTEProvider
     */
    public function linkWithAtSignCorrectlyTransformedOnWayToRTE($content, $expectedResult)
    {
        $thisConfig = ['proc.' => $this->subject->procOptions];
        $this->assertEquals($expectedResult, $this->subject->RTE_transform($content, [], 'rte', $thisConfig));
    }

    /**
     * Data provider for paragraphCorrectlyTransformedOnWayToDatabase
     */
    public static function paragraphCorrectlyTransformedOnWayToDatabaseProvider()
    {
        return [
            'Empty string' => [
                '',
                '',
            ],
            'Linebreak' => [
                CRLF,
                '',
            ],
            'Double linebreak' => [
                CRLF . CRLF,
                '',
            ],
            'Empty paragraph' => [
                '<p></p>',
                CRLF,
            ],
            'Double empty paragraph' => [
                '<p></p><p></p>',
                CRLF . CRLF,
            ],
            'Spacing paragraph' => [
                '<p>&nbsp;</p>',
                CRLF,
            ],
            'Double spacing paragraph' => [
                '<p>&nbsp;</p>' . '<p>&nbsp;</p>',
                CRLF . CRLF,
            ],
            'Plain text' => [
                'plain text',
                'plain text',
            ],
            'Plain text followed by linebreak' => [
                'plain text' . CRLF,
                'plain text ',
            ],
            'Paragraph' => [
                '<p>paragraph</p>',
                'paragraph',
            ],
            'Paragraph followed by paragraph' => [
                '<p>paragraph1</p>' . '<p>paragraph2</p>',
                'paragraph1' . CRLF . 'paragraph2',
            ],
            'Paragraph followed by paragraph, linebreak-separated' => [
                '<p>paragraph1</p>' . CRLF . '<p>paragraph2</p>',
                'paragraph1' . CRLF . 'paragraph2',
            ],
            'Double spacing paragraph with text' => [
                '<p>&nbsp;</p><p>&nbsp;</p><p>paragraph1</p>',
                CRLF . CRLF . 'paragraph1',
            ],
            'Paragraph followed by linebreak' => [
                '<p>paragraph</p>' . CRLF,
                'paragraph',
            ],
            'Paragraph followed by spacing paragraph' => [
                '<p>paragraph</p>' . '<p>&nbsp;</p>',
                'paragraph' . CRLF . CRLF,
            ],
            'Paragraph followed by spacing paragraph, linebreak-separated' => [
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>',
                'paragraph' . CRLF . CRLF,
            ],
            'Paragraph followed by double spacing paragraph' => [
                '<p>paragraph</p>' . '<p>&nbsp;</p>' . '<p>&nbsp;</p>',
                'paragraph' . CRLF . CRLF . CRLF,
            ],
            'Paragraph followed by double spacing paragraph, linebreak-separated' => [
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
                'paragraph' . CRLF . CRLF . CRLF,
            ],
            'Paragraph followed by spacing paragraph and by paragraph' => [
                '<p>paragraph1</p>' . '<p>&nbsp;</p>' . '<p>paragraph2</p>',
                'paragraph1' . CRLF . CRLF . 'paragraph2',
            ],
            'Paragraph followed by spacing paragraph and by paragraph, linebreak-separated' => [
                '<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
                'paragraph1' . CRLF . CRLF . 'paragraph2',
            ],
            'Paragraph followed by double spacing paragraph and by paragraph' => [
                '<p>paragraph1</p>' . '<p>&nbsp;</p>' . '<p>&nbsp;</p>' . '<p>paragraph2</p>',
                'paragraph1' . CRLF . CRLF . CRLF . 'paragraph2',
            ],
            'Paragraph followed by double spacing paragraph and by paragraph, linebreak-separated' => [
                '<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
                'paragraph1' . CRLF . CRLF . CRLF . 'paragraph2',
            ],
            'Paragraph followed by block' => [
                '<p>paragraph</p>' . '<h1>block</h1>',
                'paragraph' . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by block, linebreak-separated' => [
                '<p>paragraph</p>' . CRLF . '<h1>block</h1>',
                'paragraph' . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by spacing paragraph and block' => [
                '<p>paragraph</p>' . '<p>&nbsp;</p>' . '<h1>block</h1>',
                'paragraph' . CRLF . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by spacing paragraph and block, linebreak-separated' => [
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
                'paragraph' . CRLF . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by double spacing paragraph and block' => [
                '<p>paragraph</p>' . '<p>&nbsp;</p>' . '<p>&nbsp;</p>' . '<h1>block</h1>',
                'paragraph' . CRLF . CRLF . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by double spacing paragraph and block, linebreak-separated' => [
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
                'paragraph' . CRLF . CRLF . CRLF . '<h1>block</h1>',
            ],
            'Block followed by block' => [
                '<h1>block1</h1>' . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by block, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by empty paragraph and block' => [
                '<h1>block1</h1>' . '<p></p>' . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by empty paragraph aand block, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<p></p>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by spacing paragraph' => [
                '<h1>block1</h1>' . '<p>&nbsp;</p>',
                '<h1>block1</h1>' . CRLF . CRLF,
            ],
            'Block followed by spacing paragraph, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>',
                '<h1>block1</h1>' . CRLF . CRLF,
            ],
            'Block followed by spacing paragraph and block' => [
                '<h1>block1</h1>' . '<p>&nbsp;</p>' . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by spacing paragraph and block, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by double spacing paragraph and by block' => [
                '<h1>block1</h1>' . '<p>&nbsp;</p>' . '<p>&nbsp;</p>' . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . CRLF . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by double spacing paragraph and by block, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . CRLF . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by paragraph and block' => [
                '<h1>block1</h1>' . '<p>paragraph</p>' . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . 'paragraph' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by paragraph and block, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<p>paragraph</p>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . 'paragraph' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by paragraph, spacing paragraph and block' => [
                '<h1>block1</h1>' . '<p>paragraph</p>' . '<p>&nbsp;</p>' . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . 'paragraph' . CRLF . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by paragraph, spacing paragraph and block, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . 'paragraph' . CRLF . CRLF . '<h1>block2</h1>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider paragraphCorrectlyTransformedOnWayToDatabaseProvider
     */
    public function paragraphCorrectlyTransformedOnWayToDatabase($content, $expectedResult)
    {
        $thisConfig = ['proc.' => $this->subject->procOptions];
        $this->assertEquals($expectedResult, $this->subject->RTE_transform($content, [], 'db', $thisConfig));
    }

    /**
     * Data provider for lineBreakCorrectlyTransformedOnWayToRte
     */
    public static function lineBreakCorrectlyTransformedOnWayToRteProvider()
    {
        return [
            'Empty string' => [
                '',
                '',
            ],
            'Single linebreak' => [
                CRLF,
                '<p>&nbsp;</p>',
            ],
            'Double linebreak' => [
                CRLF . CRLF,
                '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
            ],
            'Triple linebreak' => [
                CRLF . CRLF . CRLF,
                '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
            ],
            'Paragraph' => [
                'paragraph',
                '<p>paragraph</p>',
            ],
            'Paragraph followed by single linebreak' => [
                'paragraph' . CRLF,
                '<p>paragraph</p>',
            ],
            'Paragraph followed by double linebreak' => [
                'paragraph' . CRLF . CRLF,
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>',
            ],
            'Paragraph followed by triple linebreak' => [
                'paragraph' . CRLF . CRLF . CRLF,
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
            ],
            'Paragraph followed by paragraph' => [
                'paragraph1' . CRLF . 'paragraph2',
                '<p>paragraph1</p>' . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by double linebreak and paragraph' => [
                'paragraph1' . CRLF . CRLF . 'paragraph2',
                '<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by triple linebreak and paragraph' => [
                'paragraph1' . CRLF . CRLF . CRLF . 'paragraph2',
                '<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by block' => [
                'paragraph' . '<h1>block</h1>',
                '<p>paragraph</p>' . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by linebreak and block' => [
                'paragraph' . CRLF . '<h1>block</h1>',
                '<p>paragraph</p>' . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by double linebreak and block' => [
                'paragraph' . CRLF . CRLF . '<h1>block</h1>',
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by triple linebreak and block' => [
                'paragraph' . CRLF . CRLF . CRLF . '<h1>block</h1>',
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
            ],
            'Block followed by block' => [
                '<h1>block1</h1>' . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by single linebreak and block' => [
                '<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by double linebreak and block' => [
                '<h1>block1</h1>' . CRLF . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by triple linebreak and block' => [
                '<h1>block1</h1>' . CRLF . CRLF . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by paragraph and block' => [
                '<h1>block1</h1>' . CRLF . 'paragraph' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<p>paragraph</p>' . CRLF . '<h1>block2</h1>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider lineBreakCorrectlyTransformedOnWayToRTEProvider
     */
    public function lineBreakCorrectlyTransformedOnWayToRTE($content, $expectedResult)
    {
        $thisConfig = ['proc.' => $this->subject->procOptions];
        $this->assertEquals($expectedResult, $this->subject->RTE_transform($content, [], 'rte', $thisConfig));
    }

    /**
     * Data provider for paragraphCorrectlyTransformedOnWayToDatabaseAndBackToRte
     */
    public static function paragraphCorrectlyTransformedOnWayToDatabaseAndBackToRteProvider()
    {
        return [
            'Empty string' => [
                '',
                '',
            ],
            'Empty paragraph' => [
                '<p></p>',
                '<p>&nbsp;</p>',
            ],
            'Double empty paragraph' => [
                '<p></p><p></p>',
                '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
            ],
            'Triple empty paragraph' => [
                '<p></p><p></p><p></p>',
                '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
            ],
            'Plain text' => [
                'plain text',
                '<p>plain text</p>',
            ],
            'Plain text followed by linebreak' => [
                'plain text' . CRLF,
                '<p>plain text </p>',
            ],
            'Plain text followed by paragraph' => [
                'plain text' . '<p>paragraph</p>',
                '<p>plain text</p>' . CRLF . '<p>paragraph</p>',
            ],
            'Spacing paragraph' => [
                '<p>&nbsp;</p>',
                '<p>&nbsp;</p>',
            ],
            'Double spacing paragraph' => [
                '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
                '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
            ],
            'Paragraph' => [
                '<p>paragraph</p>',
                '<p>paragraph</p>',
            ],
            'Paragraph followed by linebreak' => [
                '<p>paragraph</p>' . CRLF,
                '<p>paragraph</p>',
            ],
            'Paragraph followed by spacing paragraph' => [
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>',
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>',
            ],
            'Paragraph followed by double spacing paragraph' => [
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
            ],
            'Paragraph followed by paragraph' => [
                '<p>paragraph1</p>' . '<p>paragraph2</p>',
                '<p>paragraph1</p>' . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by paragraph, linebreak-separated' => [
                '<p>paragraph1</p>' . CRLF . '<p>paragraph2</p>',
                '<p>paragraph1</p>' . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by spacing paragraph and by paragraph' => [
                '<p>paragraph1</p>' . '<p>&nbsp;</p>' . '<p>paragraph2</p>',
                '<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by spacing paragraph and by paragraph, linebreak-separated' => [
                '<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
                '<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by double spacing paragraph and by paragraph' => [
                '<p>paragraph1</p>' . '<p>&nbsp;</p>' . '<p>&nbsp;</p>' . '<p>paragraph2</p>',
                '<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by double spacing paragraph and by paragraph, linebreak-separated' => [
                '<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
                '<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by block' => [
                '<p>paragraph</p>' . '<h1>block</h1>',
                '<p>paragraph</p>' . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by block, linebreak-separated' => [
                '<p>paragraph</p>' . CRLF . '<h1>block</h1>',
                '<p>paragraph</p>' . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by spacing paragraph and by block' => [
                '<p>paragraph</p>' . '<p>&nbsp;</p>' . '<h1>block</h1>',
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by spacing paragraph and by block, linebreak-separated' => [
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by double spacing paragraph and by block' => [
                '<p>paragraph</p>' . '<p>&nbsp;</p>' . '<p>&nbsp;</p>' . '<h1>block</h1>',
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by double spacing paragraph and by block, linebreak-separated' => [
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
            ],
            'Block followed by block' => [
                '<h1>block1</h1>' . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by block, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by empty paragraph and by block' => [
                '<h1>block1</h1>' . '<p></p>' . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by empty paragraph and by block, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<p></p>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by spacing paragraph and by block' => [
                '<h1>block1</h1>' . '<p>&nbsp;</p>' . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by spacing paragraph and by block, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by double spacing paragraph and by block' => [
                '<h1>block1</h1>' . '<p>&nbsp;</p>' . '<p>&nbsp;</p>' . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by double spacing paragraph and by block, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider paragraphCorrectlyTransformedOnWayToDatabaseAndBackToRteProvider
     */
    public function paragraphCorrectlyTransformedOnWayToDatabaseAndBackToRte($content, $expectedResult)
    {
        $thisConfig = ['proc.' => $this->subject->procOptions];
        $this->assertEquals($expectedResult, $this->subject->RTE_transform($this->subject->RTE_transform($content, [], 'db', $thisConfig), [], 'rte', $thisConfig));
    }
}
