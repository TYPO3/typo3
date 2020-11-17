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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers;

use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FormViewHelperTest extends FunctionalTestCase
{
    public function isRenderedDataProvider(): array
    {
        return [
            '#1' => [
                '<f:form action="{action}" method="{method}" fieldNamePrefix="{fieldNamePrefix}" />',
                [
                    'action' => 'fieldNamePrefix<>&"\'',
                    'method' => 'fieldNamePrefix<>&"\'',
                    'fieldNamePrefix' => 'fieldNamePrefix<>&"\'',
                ],
                // first element having "@extension" in name attribute
                '<input type="hidden" name="fieldNamePrefix&lt;&gt;&amp;&quot;\'[__referrer][@extension]" value="" />',
            ],
            '#2' => [
                '{f:form(action:action, method:method, fieldNamePrefix:fieldNamePrefix)}',
                [
                    'action' => 'fieldNamePrefix<>&"\'',
                    'method' => 'fieldNamePrefix<>&"\'',
                    'fieldNamePrefix' => 'fieldNamePrefix<>&"\'',
                ],
                // first element having "@extension" in name attribute
                '<input type="hidden" name="fieldNamePrefix&lt;&gt;&amp;&quot;\'[__referrer][@extension]" value="" />',
            ],
        ];
    }

    /**
     * @param string $source
     * @param array $variables
     * @param string $expectation
     *
     * @test
     * @dataProvider isRenderedDataProvider
     */
    public function isRendered(string $source, array $variables, string $expectation): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource($source);
        $view->assignMultiple($variables);
        $body = $view->render();

        $actual = null;
        if (preg_match('#<input[^>]+name=".+\[@extension\]"[^>]+>#m', $body, $matches)) {
            $actual = $matches[0];
        }

        self::assertSame($expectation, $actual);
    }
}
