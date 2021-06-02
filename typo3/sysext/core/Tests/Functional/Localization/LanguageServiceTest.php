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

namespace TYPO3\CMS\Core\Tests\Functional\Localization;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class LanguageServiceTest extends FunctionalTestCase
{
    protected LanguageService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getContainer()->get(LanguageServiceFactory::class)->create('default');
    }

    /**
     * @test
     * @dataProvider splitLabelTestDataProvider
     */
    public function splitLabelTest(string $input, string $expected): void
    {
        self::assertEquals($expected, $this->subject->sL($input));
    }

    public function splitLabelTestDataProvider(): \Generator
    {
        yield 'String without whitespace' => [
            'Edit content',
            'Edit content'
        ];
        yield 'String with leading whitespace' => [
            '  Edit content',
            '  Edit content'
        ];
        yield 'String with trailing whitespace' => [
            'Edit content   ',
            'Edit content   '
        ];
        yield 'String with outer whitespace' => [
            '    Edit content   ',
            '    Edit content   '
        ];
        yield 'String with inner whitespace' => [
            'Edit    content',
            'Edit    content'
        ];
        yield 'String with inner and outer whitespace' => [
            '    Edit    content    ',
            '    Edit    content    '
        ];
        yield 'String containing the LLL: key' => [
            'You can use LLL: to ...',
            'You can use LLL: to ...'
        ];
        yield 'String starting with the LLL: key' => [
            'LLL: can be used to ...',
            '' // @todo Should this special case be handled to return the input string?
        ];
        yield 'Locallang label without whitespace' => [
            'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editcontent',
            'Edit content'
        ];
        yield 'Locallang label with leading whitespace' => [
            '    LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editcontent',
            'Edit content'
        ];
        yield 'Locallang label with trailing whitespace' => [
            'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editcontent    ',
            'Edit content'
        ];
        yield 'Locallang label with outer whitespace' => [
            '    LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editcontent    ',
            'Edit content'
        ];
        yield 'Locallang label with inner whitespace' => [
            'LLL:    EXT:    core/Resources/Private/Language/locallang_core.xlf:cm.editcontent',
            'Edit content'
        ];
        yield 'Locallang label with inner and outer whitespace' => [
            '    LLL:    EXT:    core/Resources/Private/Language/locallang_core.xlf:cm.editcontent    ',
            'Edit content'
        ];
    }
}
