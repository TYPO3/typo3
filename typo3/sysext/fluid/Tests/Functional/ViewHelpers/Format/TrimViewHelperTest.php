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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Format;

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\View\TemplateView;

class TrimViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public function renderConvertsAValueDataProvider(): array
    {
        return [
            'empty value' => [
                '<f:format.trim value="" />',
                '',
            ],
            'simple' => [
                '<f:format.trim value="  foo  " />',
                'foo',
            ],
            'trim both' => [
                '<f:format.trim value="  foo  " side="both" />',
                'foo',
            ],
            'trim left' => [
                '<f:format.trim value="  foo  " side="left" />',
                'foo  ',
            ],
            'trim right' => [
                '<f:format.trim value="  foo  " side="right" />',
                '  foo',
            ],
            'trim start' => [
                '<f:format.trim value="  foo  " side="start" />',
                'foo  ',
            ],
            'trim end' => [
                '<f:format.trim value="  foo  " side="end" />',
                '  foo',
            ],
            'simple content' => [
                '<f:format.trim>  foo  </f:format.trim>',
                'foo',
            ],
            'trim content both' => [
                '<f:format.trim side="both">  foo  </f:format.trim>',
                'foo',
            ],
            'trim content left' => [
                '<f:format.trim side="left">  foo  </f:format.trim>',
                'foo  ',
            ],
            'trim content right' => [
                '<f:format.trim side="right">  foo  </f:format.trim>',
                '  foo',
            ],
            'trim content start' => [
                '<f:format.trim side="start">  foo  </f:format.trim>',
                'foo  ',
            ],
            'trim content end' => [
                '<f:format.trim side="end">  foo  </f:format.trim>',
                '  foo',
            ],
            'trim content multiline' => [
                '<f:format.trim>
                    foo
                </f:format.trim>',
                'foo',
            ],
            'trim content characters' => [
                '<f:format.trim characters="bac">abc</f:format.trim>',
                '',
            ],
            'do not trim middle characters' => [
                '<f:format.trim characters="b">abc</f:format.trim>',
                'abc',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderConvertsAValueDataProvider
     */
    public function renderTrimAValue(string $src, string $expected): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($src);
        self::assertSame($expected, (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function viewHelperThrowsExceptionIfIncorrectModeIsGiven(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1669191560);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.trim value="foo" side="invalid" />');
        (new TemplateView($context))->render();
    }
}
