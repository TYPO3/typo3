<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Tests\Unit\Html;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RteHtmlParserTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    protected array $procOptions = ['overruleMode' => 'default'];

    /**
     * Data provider for hrTagCorrectlyTransformedOnWayToDataBase
     */
    public static function hrTagCorrectlyTransformedOnWayToDataBaseDataProvider(): array
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
                '<p>Some text</p>' . CRLF . '<hr />',
            ],
            'Some text without HTML tags' => [
                'Some text',
                'Some text',
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
    public function hrTagCorrectlyTransformedOnWayToDataBase($content, $expectedResult): void
    {
        // @todo Explicitly disabled HTML Sanitizer (since it is based on HTML5)
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.backend.htmlSanitizeRte'] = false;
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $subject = new RteHtmlParser($eventDispatcher);
        self::assertEquals($expectedResult, $subject->transformTextForPersistence($content, $this->procOptions));
    }

    /**
     * Data provider for hrTagCorrectlyTransformedOnWayToDatabaseAndBackToRteProvider
     */
    public static function hrTagCorrectlyTransformedOnWayToDatabaseAndBackToRteProvider(): array
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
                '<div>Some text</div><hr />',
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
    public function hrTagCorrectlyTransformedOnWayToDatabaseAndBackToRte($content, $expectedResult): void
    {
        // @todo Explicitly disabled HTML Sanitizer (since it is based on HTML5)
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.backend.htmlSanitizeRte'] = false;
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $subject = new RteHtmlParser($eventDispatcher);
        self::assertEquals($expectedResult, $subject->transformTextForRichTextEditor($subject->transformTextForPersistence($content, $this->procOptions), $this->procOptions));
    }

    /**
     * Data provider for paragraphCorrectlyTransformedOnWayToDatabase
     */
    public static function paragraphCorrectlyTransformedOnWayToDatabaseProvider(): array
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
                '<p>&nbsp;</p><p>&nbsp;</p>',
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
                '<p>paragraph</p>',
            ],
            'Paragraph followed by paragraph' => [
                '<p>paragraph1</p><p>paragraph2</p>',
                '<p>paragraph1</p>' . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by paragraph, linebreak-separated' => [
                '<p>paragraph1</p>' . CRLF . '<p>paragraph2</p>',
                '<p>paragraph1</p>' . CRLF . '<p>paragraph2</p>',
            ],
            'Double spacing paragraph with text' => [
                '<p>&nbsp;</p><p>&nbsp;</p><p>paragraph1</p>',
                CRLF . CRLF . '<p>paragraph1</p>',
            ],
            'Paragraph followed by linebreak' => [
                '<p>paragraph</p>' . CRLF,
                '<p>paragraph</p>',
            ],
            'Paragraph followed by spacing paragraph' => [
                '<p>paragraph</p><p>&nbsp;</p>',
                '<p>paragraph</p>' . CRLF . CRLF,
            ],
            'Paragraph followed by spacing paragraph, linebreak-separated' => [
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>',
                '<p>paragraph</p>' . CRLF . CRLF,
            ],
            'Paragraph followed by double spacing paragraph' => [
                '<p>paragraph</p><p>&nbsp;</p><p>&nbsp;</p>',
                '<p>paragraph</p>' . CRLF . CRLF . CRLF,
            ],
            'Paragraph followed by double spacing paragraph, linebreak-separated' => [
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
                '<p>paragraph</p>' . CRLF . CRLF . CRLF,
            ],
            'Paragraph followed by spacing paragraph and by paragraph' => [
                '<p>paragraph1</p><p>&nbsp;</p><p>paragraph2</p>',
                '<p>paragraph1</p>' . CRLF . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by spacing paragraph and by paragraph, linebreak-separated' => [
                '<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
                '<p>paragraph1</p>' . CRLF . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by double spacing paragraph and by paragraph' => [
                '<p>paragraph1</p><p>&nbsp;</p><p>&nbsp;</p><p>paragraph2</p>',
                '<p>paragraph1</p>' . CRLF . CRLF . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by double spacing paragraph and by paragraph, linebreak-separated' => [
                '<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
                '<p>paragraph1</p>' . CRLF . CRLF . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by block' => [
                '<p>paragraph</p><h1>block</h1>',
                '<p>paragraph</p>' . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by block, linebreak-separated' => [
                '<p>paragraph</p>' . CRLF . '<h1>block</h1>',
                '<p>paragraph</p>' . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by spacing paragraph and block' => [
                '<p>paragraph</p><p>&nbsp;</p><h1>block</h1>',
                '<p>paragraph</p>' . CRLF . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by spacing paragraph and block, linebreak-separated' => [
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
                '<p>paragraph</p>' . CRLF . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by double spacing paragraph and block' => [
                '<p>paragraph</p><p>&nbsp;</p><p>&nbsp;</p><h1>block</h1>',
                '<p>paragraph</p>' . CRLF . CRLF . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by double spacing paragraph and block, linebreak-separated' => [
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
                '<p>paragraph</p>' . CRLF . CRLF . CRLF . '<h1>block</h1>',
            ],
            'Block followed by block' => [
                '<h1>block1</h1><h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by block, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by empty paragraph and block' => [
                '<h1>block1</h1><p></p><h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by empty paragraph and block, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<p></p>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by spacing paragraph' => [
                '<h1>block1</h1><p>&nbsp;</p>',
                '<h1>block1</h1>' . CRLF . CRLF,
            ],
            'Block followed by spacing paragraph, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>',
                '<h1>block1</h1>' . CRLF . CRLF,
            ],
            'Block followed by spacing paragraph and block' => [
                '<h1>block1</h1><p>&nbsp;</p><h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by spacing paragraph and block, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by double spacing paragraph and by block' => [
                '<h1>block1</h1><p>&nbsp;</p><p>&nbsp;</p><h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . CRLF . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by double spacing paragraph and by block, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . CRLF . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by paragraph and block' => [
                '<h1>block1</h1><p>paragraph</p><h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<p>paragraph</p>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by paragraph and block, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<p>paragraph</p>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<p>paragraph</p>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by paragraph, spacing paragraph and block' => [
                '<h1>block1</h1><p>paragraph</p><p>&nbsp;</p><h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<p>paragraph</p>' . CRLF . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by paragraph, spacing paragraph and block, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<p>paragraph</p>' . CRLF . CRLF . '<h1>block2</h1>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider paragraphCorrectlyTransformedOnWayToDatabaseProvider
     */
    public function paragraphCorrectlyTransformedOnWayToDatabase($content, $expectedResult): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $subject = new RteHtmlParser($eventDispatcher);
        self::assertEquals($expectedResult, $subject->transformTextForPersistence($content, $this->procOptions));
    }

    /**
     * Data provider for lineBreakCorrectlyTransformedOnWayToRte
     */
    public static function lineBreakCorrectlyTransformedOnWayToRteProvider(): array
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
                'paragraph<h1>block</h1>',
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
                '<h1>block1</h1><h1>block2</h1>',
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
    public function lineBreakCorrectlyTransformedOnWayToRTE($content, $expectedResult): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $subject = new RteHtmlParser($eventDispatcher);
        self::assertEquals($expectedResult, $subject->transformTextForRichTextEditor($content, $this->procOptions));
    }

    /**
     * Data provider for paragraphCorrectlyTransformedOnWayToDatabaseAndBackToRte
     */
    public static function paragraphCorrectlyTransformedOnWayToDatabaseAndBackToRteProvider(): array
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
                'plain text<p>paragraph</p>',
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
                '<p>paragraph1</p><p>paragraph2</p>',
                '<p>paragraph1</p>' . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by paragraph, linebreak-separated' => [
                '<p>paragraph1</p>' . CRLF . '<p>paragraph2</p>',
                '<p>paragraph1</p>' . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by spacing paragraph and by paragraph' => [
                '<p>paragraph1</p><p>&nbsp;</p><p>paragraph2</p>',
                '<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by spacing paragraph and by paragraph, linebreak-separated' => [
                '<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
                '<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by double spacing paragraph and by paragraph' => [
                '<p>paragraph1</p><p>&nbsp;</p><p>&nbsp;</p><p>paragraph2</p>',
                '<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by double spacing paragraph and by paragraph, linebreak-separated' => [
                '<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
                '<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
            ],
            'Paragraph followed by block' => [
                '<p>paragraph</p><h1>block</h1>',
                '<p>paragraph</p>' . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by block, linebreak-separated' => [
                '<p>paragraph</p>' . CRLF . '<h1>block</h1>',
                '<p>paragraph</p>' . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by spacing paragraph and by block' => [
                '<p>paragraph</p><p>&nbsp;</p><h1>block</h1>',
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by spacing paragraph and by block, linebreak-separated' => [
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by double spacing paragraph and by block' => [
                '<p>paragraph</p><p>&nbsp;</p><p>&nbsp;</p><h1>block</h1>',
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
            ],
            'Paragraph followed by double spacing paragraph and by block, linebreak-separated' => [
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
                '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
            ],
            'Block followed by block' => [
                '<h1>block1</h1><h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by block, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by empty paragraph and by block' => [
                '<h1>block1</h1><p></p><h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by empty paragraph and by block, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<p></p>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by spacing paragraph and by block' => [
                '<h1>block1</h1><p>&nbsp;</p><h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by spacing paragraph and by block, linebreak-separated' => [
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
                '<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
            ],
            'Block followed by double spacing paragraph and by block' => [
                '<h1>block1</h1><p>&nbsp;</p><p>&nbsp;</p><h1>block2</h1>',
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
    public function paragraphCorrectlyTransformedOnWayToDatabaseAndBackToRte($content, $expectedResult): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $subject = new RteHtmlParser($eventDispatcher);
        self::assertEquals($expectedResult, $subject->transformTextForRichTextEditor($subject->transformTextForPersistence($content, $this->procOptions), $this->procOptions));
    }

    /**
     * Data provider for anchorCorrectlyTransformedOnWayToDatabase
     */
    public static function anchorCorrectlyTransformedOnWayToDatabaseProvider(): array
    {
        return [
            [
                '<p><a name="some_anchor"></a></p>' . CRLF . '<h3>Some headline here</h3>',
                '<p><a name="some_anchor"></a></p>' . CRLF . '<h3>Some headline here</h3>',
            ],
            [
                '<p><a id="some_anchor"></a></p>' . CRLF . '<h3>Some headline here</h3>',
                '<p><a id="some_anchor"></a></p>' . CRLF . '<h3>Some headline here</h3>',
            ],
            [
                '<p><a name="some_anchor" id="some_anchor"></a></p>' . CRLF . '<h3>Some headline here</h3>',
                '<p><a name="some_anchor" id="some_anchor"></a></p>' . CRLF . '<h3>Some headline here</h3>',
            ],
            [
                '<p><a id="some_anchor">Some text inside the anchor</a></p>',
                '<p><a id="some_anchor">Some text inside the anchor</a></p>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider anchorCorrectlyTransformedOnWayToDatabaseProvider
     * @param $content
     * @param $expectedResult
     */
    public function anchorCorrectlyTransformedOnWayToDatabase($content, $expectedResult): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $subject = new RteHtmlParser($eventDispatcher);
        self::assertEquals($expectedResult, $subject->transformTextForPersistence($content, $this->procOptions));
    }

    /**
     * Data provider for anchorCorrectlyTransformedOnWayToDatabaseAndBackToRTE
     */
    public static function anchorCorrectlyTransformedOnWayToDatabaseAndBackToRTEProvider(): array
    {
        return [
            [
                '<p><a name="some_anchor"></a></p>' . CRLF . '<h3>Some headline here</h3>',
                '<p><a name="some_anchor"></a></p>' . CRLF . '<h3>Some headline here</h3>',
            ],
            [
                '<p><a id="some_anchor"></a></p>' . CRLF . '<h3>Some headline here</h3>',
                '<p><a id="some_anchor"></a></p>' . CRLF . '<h3>Some headline here</h3>',
            ],
            [
                '<p><a name="some_anchor" id="some_anchor"></a></p>' . CRLF . '<h3>Some headline here</h3>',
                '<p><a name="some_anchor" id="some_anchor"></a></p>' . CRLF . '<h3>Some headline here</h3>',
            ],
            [
                '<p><a id="some_anchor">Some text inside the anchor</a></p>',
                '<p><a id="some_anchor">Some text inside the anchor</a></p>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider anchorCorrectlyTransformedOnWayToDatabaseAndBackToRTEProvider
     * @param $content
     * @param $expectedResult
     */
    public function anchorCorrectlyTransformedOnWayToDatabaseAndBackToRTE($content, $expectedResult): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $subject = new RteHtmlParser($eventDispatcher);
        self::assertEquals($expectedResult, $subject->transformTextForRichTextEditor($subject->transformTextForPersistence($content, $this->procOptions), $this->procOptions));
    }
}
