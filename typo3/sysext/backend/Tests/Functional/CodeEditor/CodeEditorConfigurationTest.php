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

namespace TYPO3\CMS\Backend\Tests\Functional\CodeEditor;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\CodeEditor\CodeEditorConfiguration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CodeEditorConfigurationTest extends FunctionalTestCase
{
    #[Test]
    public function modesOfActivePackagesAreProvided(): void
    {
        $subject = $this->get(CodeEditorConfiguration::class);

        self::assertSame('html', $subject->getDefaultMode()->formatCode);
        self::assertSame('typoscript', $subject->getModeByFileExtension('tsconfig')->formatCode);
        self::assertTrue($subject->hasMode('json'));
        self::assertSame(
            '@codemirror/lang-json',
            $subject->getModeByFormatCode('json')->module->getName()
        );
    }

    #[Test]
    public function addonsOfActivePackagesAreProvided(): void
    {
        $subject = $this->get(CodeEditorConfiguration::class);

        $identifiers = [];
        foreach ($subject->getAddons() as $addon) {
            $identifiers[] = $addon->identifier;
        }

        self::assertContains('history', $identifiers);
        self::assertContains('autocompletion', $identifiers);
    }
}
