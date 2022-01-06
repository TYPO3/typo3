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

use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\JavaScriptRenderer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class JavaScriptRendererTest extends FunctionalTestCase
{
    /**
     * Ensures closing comment block cannot be injected.
     *
     * @test
     */
    public function textContentIsEncoded(): void
    {
        $subject = JavaScriptRenderer::create('anything.js');
        $subject->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Test*/')
                ->invoke('test*/', 'arg*/')
        );
        $subject->addGlobalAssignment(['section*/' => ['key*/' => 'value*/']]);
        self::assertSame(
            '<script src="anything.js" async="async">'
                . '/* [{"type":"globalAssignment","payload":{"section*\/":{"key*\/":"value*\/"}}},'
                . '{"type":"javaScriptModuleInstruction","payload":{"name":"TYPO3\/CMS\/Test*\/","exportName":null,'
                . '"flags":1,"items":[{"type":"invoke","method":"test*\/","args":["arg*\/"]}]}}] */</script>',
            trim($subject->render())
        );
    }
}
