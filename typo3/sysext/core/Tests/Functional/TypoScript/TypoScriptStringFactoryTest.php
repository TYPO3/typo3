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

namespace TYPO3\CMS\Core\Tests\Functional\TypoScript;

use TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher as BackendConditionMatcher;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\TypoScript\AST\AstBuilder;
use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LossyTokenizer;
use TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TypoScriptStringFactoryTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    /**
     * @test
     */
    public function parseFromStringWithIncludesAndConditionsParsesImportAndMatchesCondition(): void
    {
        $expected = new RootNode();
        $fooNode = new ChildNode('foo');
        $fooNode->setValue('fooValue');
        $expected->addChild($fooNode);
        $barNode = new ChildNode('bar');
        $barNode->setValue('barValue');
        $expected->addChild($barNode);
        /** @var TypoScriptStringFactory $subject */
        $subject = $this->get(TypoScriptStringFactory::class);
        $result = $subject->parseFromStringWithIncludesAndConditions(
            'testing',
            '@import \'EXT:core/Tests/Functional/TypoScript/Fixtures/SimpleCondition.typoscript\'',
            new LossyTokenizer(),
            new BackendConditionMatcher()
        );
        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function parseFromStringParsesSimpleString(): void
    {
        $expected = new RootNode();
        $fooNode = new ChildNode('foo');
        $fooNode->setValue('bar');
        $expected->addChild($fooNode);
        /** @var TypoScriptStringFactory $subject */
        $subject = $this->get(TypoScriptStringFactory::class);
        $result = $subject->parseFromString(
            'foo = bar',
            new LossyTokenizer(),
            new AstBuilder(new NoopEventDispatcher())
        );
        self::assertEquals($expected, $result);
    }
}
