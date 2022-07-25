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

namespace TYPO3\CMS\Core\Tests\Functional\TypoScript\AST;

use TYPO3\CMS\Core\TypoScript\AST\AstBuilder;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LosslessTokenizer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class AstBuilderTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_typoscript_ast_function_event',
    ];

    /**
     * @test
     */
    public function notModifiedValueKeepsNullValue(): void
    {
        $tokens = (new LosslessTokenizer())->tokenize('foo := doesNotExistFunction()');
        /** @var AstBuilder $astBuilder */
        $astBuilder = $this->get(AstBuilder::class);
        $ast = $astBuilder->build($tokens, new RootNode());
        self::assertNull($ast->getChildByName('foo')->getValue());
    }

    /**
     * @test
     */
    public function notModifiedValueKeepsOriginalValue(): void
    {
        $tokens = (new LosslessTokenizer())->tokenize(
            "foo = originalValue\n" .
            'foo := doesNotExistFunction()'
        );
        /** @var AstBuilder $astBuilder */
        $astBuilder = $this->get(AstBuilder::class);
        $ast = $astBuilder->build($tokens, new RootNode());
        self::assertSame('originalValue', $ast->getChildByName('foo')->getValue());
    }

    /**
     * @test
     */
    public function modifiedValueUpdatesOriginalValue(): void
    {
        $tokens = (new LosslessTokenizer())->tokenize(
            "foo = originalValue\n" .
            'foo := testFunction(modifierArgument)'
        );
        /** @var AstBuilder $astBuilder */
        $astBuilder = $this->get(AstBuilder::class);
        $ast = $astBuilder->build($tokens, new RootNode());
        self::assertSame('originalValue modifierArgument', $ast->getChildByName('foo')->getValue());
    }
}
