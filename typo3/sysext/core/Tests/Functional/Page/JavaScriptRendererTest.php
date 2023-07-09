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

namespace TYPO3\CMS\Core\Tests\Functional\Page;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\JavaScriptRenderer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class JavaScriptRendererTest extends FunctionalTestCase
{
    /**
     * Ensures closing comment block cannot be injected.
     */
    #[Test]
    public function textContentIsEncoded(): void
    {
        $subject = JavaScriptRenderer::create('anything.js');
        $subject->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/test/module.js')
                ->invoke('test*/', 'arg*/')
        );
        $subject->addGlobalAssignment(['section*/' => ['key*/' => 'value*/']]);
        self::assertSame(
            '<script>Object.assign(globalThis, {"section*\/":{"key*\/":"value*\/"}})</script>'
                . PHP_EOL
                . '<script src="anything.js" async="async">/* [{"type":"javaScriptModuleInstruction","payload":{"name":"@typo3\/test\/module.js","exportName":null,"flags":2,"items":[{"type":"invoke","method":"test*\/","args":["arg*\/"]}]}}] */</script>',
            trim($subject->render(null, '/'))
        );
    }

    #[Test]
    public function globalVariablesAreMerged(): void
    {
        $subject = JavaScriptRenderer::create('');
        $subject->addGlobalAssignment([
            'test' => 'test1',
            'bar' => 'overwrite',
            'window' => [
                'window' => 'foo',
                'bar' => 'baz',
            ],
        ]);
        self::assertSame(
            '<script>Object.assign(globalThis, {"test":"test1","bar":"baz"})</script>',
            trim($subject->render(null, '/'))
        );
    }
}
