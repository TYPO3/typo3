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

namespace TYPO3\CMS\Core\Tests\Functional\TypoScript\IncludeTree;

use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\AtImportInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\ConditionElseInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\ConditionInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\ConditionIncludeTyposcriptInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\DefaultTypoScriptMagicKeyInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\FileInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeTyposcriptInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\SegmentInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\TreeFromLineStreamBuilder;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\LineStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LosslessTokenizer;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\Token;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenType;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TreeFromLineStreamBuilderTest extends FunctionalTestCase
{
    protected array $pathsToLinkInTestInstance = [
        'typo3/sysext/core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/FileadminImport/Scenario1' => 'fileadmin/Scenario1',
        'typo3/sysext/core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/FileadminImport/Scenario2' => 'fileadmin/Scenario2',
        'typo3/sysext/core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/FileadminImport/Scenario3' => 'fileadmin/Scenario3',
        'typo3/sysext/core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/FileadminImport/Scenario4' => 'fileadmin/Scenario4',
        'typo3/sysext/core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/FileadminImport/Scenario5' => 'fileadmin/Scenario5',
    ];

    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected bool $initializeDatabase = false;

    public function buildTreeConditionDataProvider(): iterable
    {
        $typoScript = '[foo = bar]';
        $typoScriptLineStream = (new LosslessTokenizer())->tokenize($typoScript);
        $tree = new FileInclude();
        $tree->setLineStream($typoScriptLineStream);
        $tree->setIdentifier('foo');
        $tree->setName('foo');
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($typoScriptLineStream);
        $expectedTree->setIdentifier('foo');
        $expectedTree->setName('foo');
        $expectedTree->setSplit();
        $subNode = new ConditionInclude();
        $subNode->setIdentifier('foo-condition');
        $subNode->setName('foo Condition');
        $subNode->setConditionToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 1));
        $subNode->setSplit();
        $subNode->setLineStream((new LosslessTokenizer())->tokenize($typoScript));
        $expectedTree->addChild($subNode);
        yield 'condition without body' => [
            $tree,
            $expectedTree,
        ];

        $typoScript = '[END]';
        $typoScriptLineStream = (new LosslessTokenizer())->tokenize($typoScript);
        $tree = new FileInclude();
        $tree->setLineStream($typoScriptLineStream);
        $tree->setIdentifier('foo');
        $tree->setName('foo');
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($typoScriptLineStream);
        $expectedTree->setIdentifier('foo');
        $expectedTree->setName('foo');
        yield 'condition end not in condition context is just kept' => [
            $tree,
            $expectedTree,
        ];

        $typoScript = '[GLOBAL]';
        $typoScriptLineStream = (new LosslessTokenizer())->tokenize($typoScript);
        $tree = new FileInclude();
        $tree->setLineStream($typoScriptLineStream);
        $tree->setIdentifier('foo');
        $tree->setName('foo');
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($typoScriptLineStream);
        $expectedTree->setIdentifier('foo');
        $expectedTree->setName('foo');
        yield 'condition global not in condition context is just kept' => [
            $tree,
            $expectedTree,
        ];

        $typoScript = "[foo = bar]\n" .
            'foo';
        $typoScriptLineStream = (new LosslessTokenizer())->tokenize($typoScript);
        $typoScriptLineStreamArray = iterator_to_array($typoScriptLineStream->getNextLine());
        $tree = new FileInclude();
        $tree->setLineStream($typoScriptLineStream);
        $tree->setIdentifier('foo');
        $tree->setName('foo');
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($typoScriptLineStream);
        $expectedTree->setIdentifier('foo');
        $expectedTree->setName('foo');
        $expectedTree->setSplit();
        $subNode = new ConditionInclude();
        $subNode->setIdentifier('foo-condition');
        $subNode->setName('foo Condition');
        $subNode->setConditionToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 1));
        $subNode->setSplit();
        $subNode->setLineStream((new LineStream())->append($typoScriptLineStreamArray[0]));
        $expectedTree->addChild($subNode);
        $subSubNode = new SegmentInclude();
        $subSubNode->setIdentifier('foo-condition-segment');
        $subSubNode->setName('foo Condition Segment');
        $subSubNode->setLineStream((new LineStream())->append($typoScriptLineStreamArray[1]));
        $subNode->addChild($subSubNode);
        yield 'condition with body without end or global' => [
            $tree,
            $expectedTree,
        ];

        $typoScript = "[foo = bar]\n" .
            "foo = fooValue\n" .
            "[END]\n" .
            'bar = barValue';
        $typoScriptLineStream = (new LosslessTokenizer())->tokenize($typoScript);
        $typoScriptLineStreamArray = iterator_to_array($typoScriptLineStream->getNextLine());
        $tree = new FileInclude();
        $tree->setLineStream($typoScriptLineStream);
        $tree->setIdentifier('foo');
        $tree->setName('foo');
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($typoScriptLineStream);
        $expectedTree->setIdentifier('foo');
        $expectedTree->setName('foo');
        $expectedTree->setSplit();
        $subNode = new ConditionInclude();
        $subNode->setIdentifier('foo-condition');
        $subNode->setName('foo Condition');
        $subNode->setConditionToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 1));
        $subNode->setSplit();
        $subNode->setLineStream((new LineStream())->append($typoScriptLineStreamArray[0]));
        $expectedTree->addChild($subNode);
        $subSubNode = new SegmentInclude();
        $subSubNode->setIdentifier('foo-condition-segment');
        $subSubNode->setName('foo Condition Segment');
        $subSubNode->setLineStream((new LineStream())->append($typoScriptLineStreamArray[1]));
        $subNode->addChild($subSubNode);
        $subNode = new SegmentInclude();
        $subNode->setIdentifier('foo-segment');
        $subNode->setName('foo Segment');
        $subNode->setLineStream((new LineStream())->append($typoScriptLineStreamArray[2])->append($typoScriptLineStreamArray[3]));
        $expectedTree->addChild($subNode);
        yield 'condition with body with end' => [
            $tree,
            $expectedTree,
        ];

        $typoScript = "[foo = bar]\n" .
            "foo\n" .
            "[GLOBAL]\n" .
            'bar';
        $typoScriptLineStream = (new LosslessTokenizer())->tokenize($typoScript);
        $typoScriptLineStreamArray = iterator_to_array($typoScriptLineStream->getNextLine());
        $tree = new FileInclude();
        $tree->setLineStream($typoScriptLineStream);
        $tree->setIdentifier('foo');
        $tree->setName('foo');
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($typoScriptLineStream);
        $expectedTree->setIdentifier('foo');
        $expectedTree->setName('foo');
        $expectedTree->setSplit();
        $subNode = new ConditionInclude();
        $subNode->setIdentifier('foo-condition');
        $subNode->setName('foo Condition');
        $subNode->setConditionToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 1));
        $subNode->setSplit();
        $subNode->setLineStream((new LineStream())->append($typoScriptLineStreamArray[0]));
        $expectedTree->addChild($subNode);
        $subSubNode = new SegmentInclude();
        $subSubNode->setIdentifier('foo-condition-segment');
        $subSubNode->setName('foo Condition Segment');
        $subSubNode->setLineStream((new LineStream())->append($typoScriptLineStreamArray[1]));
        $subNode->addChild($subSubNode);
        $subNode = new SegmentInclude();
        $subNode->setIdentifier('foo-segment');
        $subNode->setName('foo Segment');
        $subNode->setLineStream((new LineStream())->append($typoScriptLineStreamArray[2])->append($typoScriptLineStreamArray[3]));
        $expectedTree->addChild($subNode);
        yield 'condition with body with global' => [
            $tree,
            $expectedTree,
        ];

        $typoScript = "[foo = bar]\n" .
            "foo\n" .
            "[ELSE]\n" .
            "bar\n" .
            "[END]\n" .
            'baz';
        $typoScriptLineStream = (new LosslessTokenizer())->tokenize($typoScript);
        $typoScriptLineStreamArray = iterator_to_array($typoScriptLineStream->getNextLine());
        $tree = new FileInclude();
        $tree->setLineStream($typoScriptLineStream);
        $tree->setIdentifier('foo');
        $tree->setName('foo');
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($typoScriptLineStream);
        $expectedTree->setIdentifier('foo');
        $expectedTree->setName('foo');
        $expectedTree->setSplit();
        $subNode = new ConditionInclude();
        $subNode->setIdentifier('foo-condition');
        $subNode->setName('foo Condition');
        $subNode->setConditionToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 1));
        $subNode->setSplit();
        $subNode->setLineStream((new LineStream())->append($typoScriptLineStreamArray[0]));
        $expectedTree->addChild($subNode);
        $subSubNode = new SegmentInclude();
        $subSubNode->setIdentifier('foo-condition-segment');
        $subSubNode->setName('foo Condition Segment');
        $subSubNode->setLineStream((new LineStream())->append($typoScriptLineStreamArray[1]));
        $subNode->addChild($subSubNode);
        $subNode = new ConditionElseInclude();
        $subNode->setIdentifier('foo-condition-else');
        $subNode->setName('foo Condition Else');
        $subNode->setConditionToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 1));
        $subNode->setSplit();
        $subNode->setLineStream((new LineStream())->append($typoScriptLineStreamArray[2]));
        $expectedTree->addChild($subNode);
        $subSubNode = new SegmentInclude();
        $subSubNode->setIdentifier('foo-condition-else-segment');
        $subSubNode->setName('foo Condition Else Segment');
        $subSubNode->setLineStream((new LineStream())->append($typoScriptLineStreamArray[3]));
        $subNode->addChild($subSubNode);
        $subNode = new SegmentInclude();
        $subNode->setIdentifier('foo-segment');
        $subNode->setName('foo Segment');
        $subNode->setLineStream((new LineStream())->append($typoScriptLineStreamArray[4])->append($typoScriptLineStreamArray[5]));
        $expectedTree->addChild($subNode);
        yield 'condition else with end' => [
            $tree,
            $expectedTree,
        ];

        $typoScript = "[foo = bar]\n" .
            "foo\n" .
            "[foo = baz]\n" .
            "bar\n" .
            "[END]\n";
        $typoScriptLineStream = (new LosslessTokenizer())->tokenize($typoScript);
        $typoScriptLineStreamArray = iterator_to_array($typoScriptLineStream->getNextLine());
        $tree = new FileInclude();
        $tree->setLineStream($typoScriptLineStream);
        $tree->setIdentifier('foo');
        $tree->setName('foo');
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($typoScriptLineStream);
        $expectedTree->setIdentifier('foo');
        $expectedTree->setName('foo');
        $expectedTree->setSplit();
        $subNode = new ConditionInclude();
        $subNode->setIdentifier('foo-condition');
        $subNode->setName('foo Condition');
        $subNode->setConditionToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 1));
        $subNode->setSplit();
        $subNode->setLineStream((new LineStream())->append($typoScriptLineStreamArray[0]));
        $expectedTree->addChild($subNode);
        $subSubNode = new SegmentInclude();
        $subSubNode->setIdentifier('foo-condition-segment');
        $subSubNode->setName('foo Condition Segment');
        $subSubNode->setLineStream((new LineStream())->append($typoScriptLineStreamArray[1]));
        $subNode->addChild($subSubNode);
        $subNode = new ConditionInclude();
        $subNode->setIdentifier('foo-condition');
        $subNode->setName('foo Condition');
        $subNode->setConditionToken(new Token(TokenType::T_VALUE, 'foo = baz', 2, 1));
        $subNode->setSplit();
        $subNode->setLineStream((new LineStream())->append($typoScriptLineStreamArray[2]));
        $expectedTree->addChild($subNode);
        $subSubNode = new SegmentInclude();
        $subSubNode->setIdentifier('foo-condition-segment');
        $subSubNode->setName('foo Condition Segment');
        $subSubNode->setLineStream((new LineStream())->append($typoScriptLineStreamArray[3]));
        $subNode->addChild($subSubNode);
        $subNode = new SegmentInclude();
        $subNode->setIdentifier('foo-segment');
        $subNode->setName('foo Segment');
        $subNode->setLineStream((new LineStream())->append($typoScriptLineStreamArray[4]));
        $expectedTree->addChild($subNode);
        yield 'second condition finishes first condition' => [
            $tree,
            $expectedTree,
        ];
    }

    /**
     * @test
     * @dataProvider buildTreeConditionDataProvider
     */
    public function buildTreeCondition(IncludeInterface $tree, IncludeInterface $expectedTree): void
    {
        $this->get(TreeFromLineStreamBuilder::class)->buildTree($tree, 'setup');
        self::assertEquals($expectedTree, $tree);
    }

    public function buildTreeAtImportDataProvider(): iterable
    {
        $atImportStatement = '@import \'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript\'';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $subNode->setOriginalLine(iterator_to_array($atImportLineStream->getNextLine())[0]);
        $expectedTree->addChild($subNode);
        yield 'atImport single file with ticks' => [
            $atImportLineStream,
            $expectedTree,
        ];

        $atImportStatement = '@import "EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript"';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $subNode->setOriginalLine(iterator_to_array($atImportLineStream->getNextLine())[0]);
        $expectedTree->addChild($subNode);
        yield 'atImport single file with doubleticks' => [
            $atImportLineStream,
            $expectedTree,
        ];

        $atImportStatement = '@import "EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/notFoundFile.typoscript"';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        yield 'atImport with not found file' => [
            $atImportLineStream,
            $expectedTree,
        ];

        $atImportStatement = '@import "EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1"';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $atImportLine = iterator_to_array($atImportLineStream->getNextLine())[0];
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $subNode->setOriginalLine($atImportLine);
        $expectedTree->addChild($subNode);
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup2.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup2.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup2.typoscript\n"));
        $subNode->setOriginalLine($atImportLine);
        $expectedTree->addChild($subNode);
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/subInclude.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/subInclude.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript'\n"));
        $subNode->setOriginalLine($atImportLine);
        $subNode->setSplit();
        $subSubNode = new AtImportInclude();
        $subSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript');
        $subSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("SubDirectory.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript'\n")->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        $expectedTree->addChild($subNode);
        yield 'atImport with directory' => [
            $atImportLineStream,
            $expectedTree,
        ];

        $atImportStatement = '@import "EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/"';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $atImportLine = iterator_to_array($atImportLineStream->getNextLine())[0];
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $subNode->setOriginalLine($atImportLine);
        $expectedTree->addChild($subNode);
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup2.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup2.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup2.typoscript\n"));
        $subNode->setOriginalLine($atImportLine);
        $expectedTree->addChild($subNode);
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/subInclude.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/subInclude.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript'\n"));
        $subNode->setOriginalLine($atImportLine);
        $subNode->setSplit();
        $subSubNode = new AtImportInclude();
        $subSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript');
        $subSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("SubDirectory.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript'\n")->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        $expectedTree->addChild($subNode);
        yield 'atImport with directory and slash at end' => [
            $atImportLineStream,
            $expectedTree,
        ];

        $atImportStatement = '@import "EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup"';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $subNode->setOriginalLine(iterator_to_array($atImportLineStream->getNextLine())[0]);
        $expectedTree->addChild($subNode);
        yield 'atImport with single file adds .typoscript ending' => [
            $atImportLineStream,
            $expectedTree,
        ];

        $atImportStatement = '@import "EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup*"';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $atImportLine = iterator_to_array($atImportLineStream->getNextLine())[0];
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $subNode->setOriginalLine($atImportLine);
        $expectedTree->addChild($subNode);
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup2.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup2.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup2.typoscript\n"));
        $subNode->setOriginalLine($atImportLine);
        $expectedTree->addChild($subNode);
        yield 'atImport with setup* resolves setup.typoscript and setup2.typoscript' => [
            $atImportLineStream,
            $expectedTree,
        ];

        $atImportStatement = '@import "EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/*typoscript"';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $atImportLine = iterator_to_array($atImportLineStream->getNextLine())[0];
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $subNode->setOriginalLine($atImportLine);
        $expectedTree->addChild($subNode);
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup2.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup2.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup2.typoscript\n"));
        $subNode->setOriginalLine($atImportLine);
        $expectedTree->addChild($subNode);
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/subInclude.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/subInclude.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript'\n"));
        $subNode->setOriginalLine($atImportLine);
        $subNode->setSplit();
        $subSubNode = new AtImportInclude();
        $subSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript');
        $subSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("SubDirectory.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript'\n")->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        $expectedTree->addChild($subNode);
        yield 'atImport with *typoscript resolves setup.typoscript, setup2.typoscript and subInclude.typoscript' => [
            $atImportLineStream,
            $expectedTree,
        ];

        $atImportStatement = '@import "EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/*.typoscript"';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $atImportLine = iterator_to_array($atImportLineStream->getNextLine())[0];
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $subNode->setOriginalLine($atImportLine);
        $expectedTree->addChild($subNode);
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup2.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup2.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup2.typoscript\n"));
        $subNode->setOriginalLine($atImportLine);
        $expectedTree->addChild($subNode);
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/subInclude.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/subInclude.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript'\n"));
        $subNode->setOriginalLine($atImportLine);
        $subNode->setSplit();
        $subSubNode = new AtImportInclude();
        $subSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript');
        $subSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("SubDirectory.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript'\n")->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        $expectedTree->addChild($subNode);
        yield 'atImport with *.typoscript resolves setup.typoscript, setup2.typoscript and subInclude.typoscript' => [
            $atImportLineStream,
            $expectedTree,
        ];

        $atImportStatement = '@import "EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup*.typoscript"';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $atImportLine = iterator_to_array($atImportLineStream->getNextLine())[0];
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $subNode->setOriginalLine($atImportLine);
        $expectedTree->addChild($subNode);
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup2.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup2.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup2.typoscript\n"));
        $subNode->setOriginalLine($atImportLine);
        $expectedTree->addChild($subNode);
        yield 'atImport with setup*.typoscript resolves setup.typoscript and setup2.typoscript' => [
            $atImportLineStream,
            $expectedTree,
        ];

        $atImportStatement = '@import \'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario2/pathTraversal1.typoscript\'';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario2/pathTraversal1.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario2/pathTraversal1.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario2/./subInclude.typoscript'\n"));
        $subNode->setSplit();
        $subNode->setOriginalLine(iterator_to_array($atImportLineStream->getNextLine())[0]);
        $subSubNode = new AtImportInclude();
        $subSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario2/./subInclude.typoscript');
        $subSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario2/./subInclude.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("subInclude.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario2/./subInclude.typoscript'\n")->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        $expectedTree->addChild($subNode);
        yield 'atImport with dot-slash path traversal is allowed' => [
            $atImportLineStream,
            $expectedTree,
        ];

        $atImportStatement = '@import "EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/hasRelative1.typoscript"';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/hasRelative1.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/hasRelative1.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("@import './relativeTarget.typoscript'\n"));
        $subNode->setOriginalLine(iterator_to_array($atImportLineStream->getNextLine())[0]);
        $subNode->setSplit();
        $expectedTree->addChild($subNode);
        $subSubNode = new AtImportInclude();
        $subSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/relativeTarget.typoscript');
        $subSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/relativeTarget.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("relativeTarget.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("@import './relativeTarget.typoscript'\n")->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        yield 'atImport with dot slash relative include' => [
            $atImportLineStream,
            $expectedTree,
        ];

        $atImportStatement = '@import "EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/hasRelative2.typoscript"';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/hasRelative2.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/hasRelative2.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("@import 'relativeTarget.typoscript'\n"));
        $subNode->setOriginalLine(iterator_to_array($atImportLineStream->getNextLine())[0]);
        $subNode->setSplit();
        $expectedTree->addChild($subNode);
        $subSubNode = new AtImportInclude();
        $subSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/relativeTarget.typoscript');
        $subSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/relativeTarget.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("relativeTarget.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("@import 'relativeTarget.typoscript'\n")->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        yield 'atImport with relative include' => [
            $atImportLineStream,
            $expectedTree,
        ];

        $atImportStatement = '@import "EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/hasRelative3.typoscript"';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/hasRelative3.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/hasRelative3.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("@import './RelativeSubDirectory/relativeTarget.typoscript'\n"));
        $subNode->setOriginalLine(iterator_to_array($atImportLineStream->getNextLine())[0]);
        $subNode->setSplit();
        $expectedTree->addChild($subNode);
        $subSubNode = new AtImportInclude();
        $subSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/RelativeSubDirectory/relativeTarget.typoscript');
        $subSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/RelativeSubDirectory/relativeTarget.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("relativeTarget.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("@import './RelativeSubDirectory/relativeTarget.typoscript'\n")->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        yield 'atImport with dot slash relative directory include' => [
            $atImportLineStream,
            $expectedTree,
        ];

        $atImportStatement = '@import "EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/hasRelative4.typoscript"';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/hasRelative4.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/hasRelative4.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("@import 'RelativeSubDirectory/relativeTarget.typoscript'\n"));
        $subNode->setOriginalLine(iterator_to_array($atImportLineStream->getNextLine())[0]);
        $subNode->setSplit();
        $expectedTree->addChild($subNode);
        $subSubNode = new AtImportInclude();
        $subSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/RelativeSubDirectory/relativeTarget.typoscript');
        $subSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/RelativeSubDirectory/relativeTarget.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("relativeTarget.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("@import 'RelativeSubDirectory/relativeTarget.typoscript'\n")->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        yield 'atImport with relative directory include' => [
            $atImportLineStream,
            $expectedTree,
        ];

        $atImportStatement = '@import "EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/hasRelative5.typoscript"';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/hasRelative5.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/hasRelative5.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("@import 'RelativeRecursiveDirectory/relativeRecursive.typoscript'\n"));
        $subNode->setOriginalLine(iterator_to_array($atImportLineStream->getNextLine())[0]);
        $subNode->setSplit();
        $expectedTree->addChild($subNode);
        $subSubNode = new AtImportInclude();
        $subSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/RelativeRecursiveDirectory/relativeRecursive.typoscript');
        $subSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/RelativeRecursiveDirectory/relativeRecursive.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("@import 'relativeTarget.typoscript'\n"));
        $subSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("@import 'RelativeRecursiveDirectory/relativeRecursive.typoscript'\n")->getNextLine())[0]);
        $subSubNode->setSplit();
        $subNode->addChild($subSubNode);
        $subSubSubNode = new AtImportInclude();
        $subSubSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/RelativeRecursiveDirectory/relativeTarget.typoscript');
        $subSubSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/RelativeImport/RelativeRecursiveDirectory/relativeTarget.typoscript');
        $subSubSubNode->setLineStream((new LosslessTokenizer())->tokenize("relativeTarget.typoscript\n"));
        $subSubSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("@import 'relativeTarget.typoscript'\n")->getNextLine())[0]);
        $subSubNode->addChild($subSubSubNode);
        yield 'atImport with relative recursive include' => [
            $atImportLineStream,
            $expectedTree,
        ];

        $atImportStatement = '@import "EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/InvalidImport/pathTraversal1.typoscript"';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/InvalidImport/pathTraversal1.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/InvalidImport/pathTraversal1.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/InvalidImport/../InvalidImport/invalidPathTraversalTarget.typoscript'\n"));
        $subNode->setOriginalLine(iterator_to_array($atImportLineStream->getNextLine())[0]);
        $subNode->setSplit();
        $expectedTree->addChild($subNode);
        yield 'atImport single path traversal dot-dot-slash 1 is not allowed' => [
            $atImportLineStream,
            $expectedTree,
        ];

        $atImportStatement = '@import "EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/InvalidImport/pathTraversal2.typoscript"';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/InvalidImport/pathTraversal2.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/InvalidImport/pathTraversal2.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/InvalidImport/../invalidPathTraversalTarget.typoscript'\n"));
        $subNode->setOriginalLine(iterator_to_array($atImportLineStream->getNextLine())[0]);
        $subNode->setSplit();
        $expectedTree->addChild($subNode);
        yield 'atImport single path traversal dot-dot-slash 2 is not allowed' => [
            $atImportLineStream,
            $expectedTree,
        ];

        $atImportStatement = '@import "EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/InvalidImport/pathTraversal3.typoscript"';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/InvalidImport/pathTraversal3.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/InvalidImport/pathTraversal3.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("@import '../invalidPathTraversalTarget.typoscript'\n"));
        $subNode->setOriginalLine(iterator_to_array($atImportLineStream->getNextLine())[0]);
        $subNode->setSplit();
        $expectedTree->addChild($subNode);
        yield 'atImport single path traversal relative dot-dot-slash 1 is not allowed' => [
            $atImportLineStream,
            $expectedTree,
        ];

        $atImportStatement = '@import "EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/InvalidImport/pathTraversal4.typoscript"';
        $atImportLineStream = (new LosslessTokenizer())->tokenize($atImportStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($atImportLineStream);
        $expectedTree->setSplit();
        $subNode = new AtImportInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/InvalidImport/pathTraversal4.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/InvalidImport/pathTraversal4.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("@import './../invalidPathTraversalTarget.typoscript'\n"));
        $subNode->setOriginalLine(iterator_to_array($atImportLineStream->getNextLine())[0]);
        $subNode->setSplit();
        $expectedTree->addChild($subNode);
        yield 'atImport single path traversal relative dot-dot-slash 2 is not allowed' => [
            $atImportLineStream,
            $expectedTree,
        ];
    }

    /**
     * @test
     * @dataProvider buildTreeAtImportDataProvider
     */
    public function buildTreeAtImport(LineStream $lineStream, IncludeInterface $expectedTree): void
    {
        $tree = (new FileInclude());
        $tree->setLineStream($lineStream);
        $treeFromTokenStreamBuilder = $this->get(TreeFromLineStreamBuilder::class);
        $treeFromTokenStreamBuilder->setTokenizer(new LosslessTokenizer());
        $treeFromTokenStreamBuilder->buildTree($tree, 'setup');
        self::assertEquals($expectedTree, $tree);
    }

    public function buildTreeImportTyposcriptDataProvider(): iterable
    {
        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $subNode->setOriginalLine(iterator_to_array($includeTyposcriptStatementLineStream->getNextLine())[0]);
        $expectedTree->addChild($subNode);
        yield 'INCLUDE_TYPOSCRIPT FILE EXT file' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/relative1.typoscript">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setSplit();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/relative1.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/relative1.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("<INCLUDE_TYPOSCRIPT: source=\"FILE:./subInclude.typoscript\">\n"));
        $subNode->setOriginalLine(iterator_to_array($includeTyposcriptStatementLineStream->getNextLine())[0]);
        $expectedTree->addChild($subNode);
        $subSubNode = new IncludeTyposcriptInclude();
        $subSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/subInclude.typoscript');
        $subSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/subInclude.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("subInclude.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("<INCLUDE_TYPOSCRIPT: source=\"FILE:./subInclude.typoscript\">\n")->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        yield 'INCLUDE_TYPOSCRIPT FILE EXT file relative 1 sub include' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/relative2.typoscript">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setSplit();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/relative2.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/relative2.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("<INCLUDE_TYPOSCRIPT: source=\"FILE:./RelativeSub/subInclude.typoscript\">\n"));
        $subNode->setOriginalLine(iterator_to_array($includeTyposcriptStatementLineStream->getNextLine())[0]);
        $expectedTree->addChild($subNode);
        $subSubNode = new IncludeTyposcriptInclude();
        $subSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/RelativeSub/subInclude.typoscript');
        $subSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/RelativeSub/subInclude.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("subInclude.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("<INCLUDE_TYPOSCRIPT: source=\"FILE:./RelativeSub/subInclude.typoscript\">\n")->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        yield 'INCLUDE_TYPOSCRIPT FILE EXT file relative 2 sub include' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/relative3.typoscript">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setSplit();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/relative3.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/relative3.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("<INCLUDE_TYPOSCRIPT: source=\"FILE: ./subInclude.typoscript\">\n"));
        $subNode->setOriginalLine(iterator_to_array($includeTyposcriptStatementLineStream->getNextLine())[0]);
        $expectedTree->addChild($subNode);
        $subSubNode = new IncludeTyposcriptInclude();
        $subSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/subInclude.typoscript');
        $subSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/subInclude.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("subInclude.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("<INCLUDE_TYPOSCRIPT: source=\"FILE: ./subInclude.typoscript\">\n")->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        yield 'INCLUDE_TYPOSCRIPT FILE EXT file relative 3 sub include' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: source="FILE:fileadmin/Scenario1/setup.typoscript">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('fileadmin/Scenario1/setup.typoscript');
        $subNode->setName('fileadmin/Scenario1/setup.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $subNode->setOriginalLine(iterator_to_array($includeTyposcriptStatementLineStream->getNextLine())[0]);
        $expectedTree->addChild($subNode);
        yield 'INCLUDE_TYPOSCRIPT FILE fileadmin file' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: source="FILE:fileadmin/Scenario2/relative1.typoscript">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setSplit();
        $subNode->setIdentifier('fileadmin/Scenario2/relative1.typoscript');
        $subNode->setName('fileadmin/Scenario2/relative1.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("<INCLUDE_TYPOSCRIPT: source=\"FILE:./subInclude.typoscript\">\n"));
        $subNode->setOriginalLine(iterator_to_array($includeTyposcriptStatementLineStream->getNextLine())[0]);
        $expectedTree->addChild($subNode);
        $subSubNode = new IncludeTyposcriptInclude();
        $subSubNode->setIdentifier('fileadmin/Scenario2/subInclude.typoscript');
        $subSubNode->setName('fileadmin/Scenario2/subInclude.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("subInclude.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("<INCLUDE_TYPOSCRIPT: source=\"FILE:./subInclude.typoscript\">\n")->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        yield 'INCLUDE_TYPOSCRIPT FILE fileadmin file relative 1 sub include' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: source="FILE:fileadmin/Scenario2/relative2.typoscript">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setSplit();
        $subNode->setIdentifier('fileadmin/Scenario2/relative2.typoscript');
        $subNode->setName('fileadmin/Scenario2/relative2.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("<INCLUDE_TYPOSCRIPT: source=\"FILE:./RelativeSub/subInclude.typoscript\">\n"));
        $subNode->setOriginalLine(iterator_to_array($includeTyposcriptStatementLineStream->getNextLine())[0]);
        $expectedTree->addChild($subNode);
        $subSubNode = new IncludeTyposcriptInclude();
        $subSubNode->setIdentifier('fileadmin/Scenario2/RelativeSub/subInclude.typoscript');
        $subSubNode->setName('fileadmin/Scenario2/RelativeSub/subInclude.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("subInclude.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("<INCLUDE_TYPOSCRIPT: source=\"FILE:./RelativeSub/subInclude.typoscript\">\n")->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        yield 'INCLUDE_TYPOSCRIPT FILE fileadmin file relative 2 sub include' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: source="FILE:fileadmin/Scenario2/relative3.typoscript">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setSplit();
        $subNode->setIdentifier('fileadmin/Scenario2/relative3.typoscript');
        $subNode->setName('fileadmin/Scenario2/relative3.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("<INCLUDE_TYPOSCRIPT: source=\"FILE: ./subInclude.typoscript\">\n"));
        $subNode->setOriginalLine(iterator_to_array($includeTyposcriptStatementLineStream->getNextLine())[0]);
        $expectedTree->addChild($subNode);
        $subSubNode = new IncludeTyposcriptInclude();
        $subSubNode->setIdentifier('fileadmin/Scenario2/subInclude.typoscript');
        $subSubNode->setName('fileadmin/Scenario2/subInclude.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("subInclude.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("<INCLUDE_TYPOSCRIPT: source=\"FILE: ./subInclude.typoscript\">\n")->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        yield 'INCLUDE_TYPOSCRIPT FILE fileadmin file relative 3 sub include' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: source="DIR:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario3">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $includeTyposcriptStatementLineStreamLine = iterator_to_array((new LosslessTokenizer())->tokenize($includeTyposcriptStatement)->getNextLine())[0];
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario3/setup1.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario3/setup1.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup1.typoscript\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario3/setup2.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario3/setup2.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup2.typoscript\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        yield 'INCLUDE_TYPOSCRIPT DIR EXT files' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: source="DIR:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario3/">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $includeTyposcriptStatementLineStreamLine = iterator_to_array((new LosslessTokenizer())->tokenize($includeTyposcriptStatement)->getNextLine())[0];
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario3/setup1.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario3/setup1.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup1.typoscript\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario3/setup2.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario3/setup2.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup2.typoscript\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        yield 'INCLUDE_TYPOSCRIPT DIR EXT files with ending slash' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: source="DIR:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario4">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $includeTyposcriptStatementLineStreamLine = iterator_to_array((new LosslessTokenizer())->tokenize($includeTyposcriptStatement)->getNextLine())[0];
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario4/setup1.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario4/setup1.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup1.typoscript\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario4/AutoSubDir1/auto1.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario4/AutoSubDir1/auto1.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("auto1.typoscript\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario4/AutoSubDir1/auto2.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario4/AutoSubDir1/auto2.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("auto2.typoscript\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario4/AutoSubDir2/auto1.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario4/AutoSubDir2/auto1.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("auto1.typoscript\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        yield 'INCLUDE_TYPOSCRIPT DIR EXT files with auto include sub dirs' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: extensions="typoscript,txt" source="DIR:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario5">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $includeTyposcriptStatementLineStreamLine = iterator_to_array((new LosslessTokenizer())->tokenize($includeTyposcriptStatement)->getNextLine())[0];
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario5/setup1.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario5/setup1.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup1.typoscript\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario5/setup2.foo.txt');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario5/setup2.foo.txt');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup2.foo.txt\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        yield 'INCLUDE_TYPOSCRIPT DIR EXT files extensions restriction' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: source="DIR:fileadmin/Scenario3">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $includeTyposcriptStatementLineStreamLine = iterator_to_array((new LosslessTokenizer())->tokenize($includeTyposcriptStatement)->getNextLine())[0];
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('fileadmin/Scenario3/setup1.typoscript');
        $subNode->setName('fileadmin/Scenario3/setup1.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup1.typoscript\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('fileadmin/Scenario3/setup2.typoscript');
        $subNode->setName('fileadmin/Scenario3/setup2.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup2.typoscript\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        yield 'INCLUDE_TYPOSCRIPT DIR fileamin files' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: source="DIR:fileadmin/Scenario3/">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $includeTyposcriptStatementLineStreamLine = iterator_to_array((new LosslessTokenizer())->tokenize($includeTyposcriptStatement)->getNextLine())[0];
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('fileadmin/Scenario3/setup1.typoscript');
        $subNode->setName('fileadmin/Scenario3/setup1.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup1.typoscript\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('fileadmin/Scenario3/setup2.typoscript');
        $subNode->setName('fileadmin/Scenario3/setup2.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup2.typoscript\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        yield 'INCLUDE_TYPOSCRIPT DIR fileadmin files with ending slash' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: source="DIR:fileadmin/Scenario4">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $includeTyposcriptStatementLineStreamLine = iterator_to_array((new LosslessTokenizer())->tokenize($includeTyposcriptStatement)->getNextLine())[0];
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('fileadmin/Scenario4/setup1.typoscript');
        $subNode->setName('fileadmin/Scenario4/setup1.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup1.typoscript\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('fileadmin/Scenario4/AutoSubDir1/auto1.typoscript');
        $subNode->setName('fileadmin/Scenario4/AutoSubDir1/auto1.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("auto1.typoscript\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('fileadmin/Scenario4/AutoSubDir1/auto2.typoscript');
        $subNode->setName('fileadmin/Scenario4/AutoSubDir1/auto2.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("auto2.typoscript\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('fileadmin/Scenario4/AutoSubDir2/auto1.typoscript');
        $subNode->setName('fileadmin/Scenario4/AutoSubDir2/auto1.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("auto1.typoscript\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        yield 'INCLUDE_TYPOSCRIPT DIR fileadmin files with auto include sub dirs' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: extensions="typoscript,txt" source="DIR:fileadmin/Scenario5">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $includeTyposcriptStatementLineStreamLine = iterator_to_array((new LosslessTokenizer())->tokenize($includeTyposcriptStatement)->getNextLine())[0];
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('fileadmin/Scenario5/setup1.typoscript');
        $subNode->setName('fileadmin/Scenario5/setup1.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup1.typoscript\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('fileadmin/Scenario5/setup2.foo.txt');
        $subNode->setName('fileadmin/Scenario5/setup2.foo.txt');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("setup2.foo.txt\n"));
        $subNode->setOriginalLine($includeTyposcriptStatementLineStreamLine);
        $expectedTree->addChild($subNode);
        yield 'INCLUDE_TYPOSCRIPT DIR fileadmin files extensions restriction' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: condition="[foo = bar]" source="FILE:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new ConditionIncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript-condition');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript Condition');
        $subNode->setSplit();
        $subNode->setConditionToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 0));
        $expectedTree->addChild($subNode);
        $subSubNode = new IncludeTyposcriptInclude();
        $subSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript');
        $subSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array($includeTyposcriptStatementLineStream->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        yield 'INCLUDE_TYPOSCRIPT FILE EXT file with condition restriction' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: source="FILE:fileadmin/Scenario1/setup.typoscript" condition="[foo = bar]">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new ConditionIncludeTyposcriptInclude();
        $subNode->setIdentifier('fileadmin/Scenario1/setup.typoscript-condition');
        $subNode->setName('fileadmin/Scenario1/setup.typoscript Condition');
        $subNode->setSplit();
        $subNode->setConditionToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 0));
        $expectedTree->addChild($subNode);
        $subSubNode = new IncludeTyposcriptInclude();
        $subSubNode->setIdentifier('fileadmin/Scenario1/setup.typoscript');
        $subSubNode->setName('fileadmin/Scenario1/setup.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array($includeTyposcriptStatementLineStream->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        yield 'INCLUDE_TYPOSCRIPT FILE fileadmin file with condition restriction' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: condition="[applicationContext matches \"/^Production/\"]" source="FILE:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new ConditionIncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript-condition');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript Condition');
        $subNode->setSplit();
        $subNode->setConditionToken(new Token(TokenType::T_VALUE, 'applicationContext matches "/^Production/"', 0, 0));
        $expectedTree->addChild($subNode);
        $subSubNode = new IncludeTyposcriptInclude();
        $subSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript');
        $subSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array($includeTyposcriptStatementLineStream->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        yield 'INCLUDE_TYPOSCRIPT FILE EXT file with complex condition restriction 1' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: condition="page[\"pid\"] in [17,24]" source="FILE:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new ConditionIncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript-condition');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript Condition');
        $subNode->setSplit();
        $subNode->setConditionToken(new Token(TokenType::T_VALUE, 'page["pid"] in [17,24]', 0, 0));
        $expectedTree->addChild($subNode);
        $subSubNode = new IncludeTyposcriptInclude();
        $subSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript');
        $subSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array($includeTyposcriptStatementLineStream->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        yield 'INCLUDE_TYPOSCRIPT FILE EXT file with complex condition restriction 2' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: condition="[foo = bar]" source="FILE:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/relative1.typoscript">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new ConditionIncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/relative1.typoscript-condition');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/relative1.typoscript Condition');
        $subNode->setSplit();
        $subNode->setConditionToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 0));
        $expectedTree->addChild($subNode);
        $subSubNode = new IncludeTyposcriptInclude();
        $subSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/relative1.typoscript');
        $subSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/relative1.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("<INCLUDE_TYPOSCRIPT: source=\"FILE:./subInclude.typoscript\">\n"));
        $subSubNode->setOriginalLine(iterator_to_array($includeTyposcriptStatementLineStream->getNextLine())[0]);
        $subSubNode->setSplit();
        $subNode->addChild($subSubNode);
        $subSubSubNode = new IncludeTyposcriptInclude();
        $subSubSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/subInclude.typoscript');
        $subSubSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario2/subInclude.typoscript');
        $subSubSubNode->setLineStream((new LosslessTokenizer())->tokenize("subInclude.typoscript\n"));
        $subSubSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("<INCLUDE_TYPOSCRIPT: source=\"FILE:./subInclude.typoscript\">\n")->getNextLine())[0]);
        $subSubNode->addChild($subSubSubNode);
        yield 'INCLUDE_TYPOSCRIPT FILE EXT fileadmin file relative 1 sub include with condition restriction' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: extensions="typoscript" source="DIR:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario5" condition="[foo = bar]">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new ConditionIncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario5-condition');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario5 Condition');
        $subNode->setSplit();
        $subNode->setConditionToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 0));
        $expectedTree->addChild($subNode);
        $subSubNode = new IncludeTyposcriptInclude();
        $subSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario5/setup1.typoscript');
        $subSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario5/setup1.typoscript');
        $subSubNode->setLineStream((new LosslessTokenizer())->tokenize("setup1.typoscript\n"));
        $subSubNode->setOriginalLine(iterator_to_array($includeTyposcriptStatementLineStream->getNextLine())[0]);
        $subNode->addChild($subSubNode);
        yield 'INCLUDE_TYPOSCRIPT DIR EXT files extensions restriction and condition restriction' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/InvalidImport/Scenario1/setup.typoscript">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/InvalidImport/Scenario1/setup.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/InvalidImport/Scenario1/setup.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("<INCLUDE_TYPOSCRIPT: source=\"FILE:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/InvalidImport/Scenario1/../Scenario1/invalidPathTraversalTarget.typoscript\">\n"));
        $subNode->setOriginalLine(iterator_to_array($includeTyposcriptStatementLineStream->getNextLine())[0]);
        $subNode->setSplit();
        $expectedTree->addChild($subNode);
        yield 'INCLUDE_TYPOSCRIPT FILE EXT path traversal dot-dot-slash 1 is not allowed' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/InvalidImport/Scenario2/setup.typoscript">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/InvalidImport/Scenario2/setup.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/InvalidImport/Scenario2/setup.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("<INCLUDE_TYPOSCRIPT: source=\"FILE:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/InvalidImport/Scenario2/../invalidPathTraversalTarget.typoscript\">\n"));
        $subNode->setOriginalLine(iterator_to_array($includeTyposcriptStatementLineStream->getNextLine())[0]);
        $subNode->setSplit();
        $expectedTree->addChild($subNode);
        yield 'INCLUDE_TYPOSCRIPT FILE EXT path traversal dot-dot-slash 2 is not allowed' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/InvalidImport/Scenario3/setup.typoscript">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/InvalidImport/Scenario3/setup.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/InvalidImport/Scenario3/setup.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("<INCLUDE_TYPOSCRIPT: source=\"FILE:./../Scenario3/invalidPathTraversalTarget.typoscript\">\n"));
        $subNode->setOriginalLine(iterator_to_array($includeTyposcriptStatementLineStream->getNextLine())[0]);
        $subNode->setSplit();
        $expectedTree->addChild($subNode);
        yield 'INCLUDE_TYPOSCRIPT FILE relative path traversal dot-dot-slash 1 is not allowed' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];

        $includeTyposcriptStatement = '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/InvalidImport/Scenario4/setup.typoscript">';
        $includeTyposcriptStatementLineStream = (new LosslessTokenizer())->tokenize($includeTyposcriptStatement);
        $expectedTree = new FileInclude();
        $expectedTree->setLineStream($includeTyposcriptStatementLineStream);
        $expectedTree->setSplit();
        $subNode = new IncludeTyposcriptInclude();
        $subNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/InvalidImport/Scenario4/setup.typoscript');
        $subNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/InvalidImport/Scenario4/setup.typoscript');
        $subNode->setLineStream((new LosslessTokenizer())->tokenize("<INCLUDE_TYPOSCRIPT: source=\"FILE:./../invalidPathTraversalTarget.typoscript\">\n"));
        $subNode->setOriginalLine(iterator_to_array($includeTyposcriptStatementLineStream->getNextLine())[0]);
        $subNode->setSplit();
        $expectedTree->addChild($subNode);
        yield 'INCLUDE_TYPOSCRIPT FILE relative path traversal dot-dot-slash 2 is not allowed' => [
            $includeTyposcriptStatementLineStream,
            $expectedTree,
        ];
    }

    /**
     * @test
     * @dataProvider buildTreeImportTyposcriptDataProvider
     */
    public function buildTreeImportTyposcript(LineStream $lineStream, IncludeInterface $expectedTree): void
    {
        $tree = (new FileInclude());
        $tree->setLineStream($lineStream);
        $treeFromTokenStreamBuilder = $this->get(TreeFromLineStreamBuilder::class);
        $treeFromTokenStreamBuilder->setTokenizer(new LosslessTokenizer());
        $treeFromTokenStreamBuilder->buildTree($tree, 'setup');
        self::assertEquals($expectedTree, $tree);
    }

    /**
     * @test
     */
    public function atImportIncludesMagicTypoScriptRenderingForSimpleFile(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'] = [
            'core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'] = 'foo23';

        $expectedTree = new FileInclude();
        $expectedTree->setLineStream((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript'\n"));
        $expectedTree->setSplit();
        $expectedTreeSubNode = new AtImportInclude();
        $expectedTreeSubNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $expectedTreeSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $expectedTreeSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $expectedTreeSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript'\n")->getNextLine())[0]);
        $expectedTree->addChild($expectedTreeSubNode);
        $expectedTreeSubNode = new DefaultTypoScriptMagicKeyInclude();
        $expectedTreeSubNode->setLineStream((new LosslessTokenizer())->tokenize('foo23'));
        $expectedTreeSubNode->setIdentifier('globals-defaultTypoScript-setup-defaultContentRendering-EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $expectedTreeSubNode->setName('TYPO3_CONF_VARS defaultContentRendering for EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $expectedTree->addChild($expectedTreeSubNode);

        $tree = (new FileInclude());
        $tree->setLineStream((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript'\n"));
        $treeFromTokenStreamBuilder = $this->get(TreeFromLineStreamBuilder::class);
        $treeFromTokenStreamBuilder->setTokenizer(new LosslessTokenizer());
        $treeFromTokenStreamBuilder->buildTree($tree, 'setup');
        self::assertEquals($expectedTree, $tree);
    }

    /**
     * @test
     */
    public function atImportIncludesMagicTypoScriptRenderingForSimpleFileWithoutDotTypoScriptEnding(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'] = [
            'core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'] = 'foo23';

        $expectedTree = new FileInclude();
        $expectedTree->setLineStream((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup'\n"));
        $expectedTree->setSplit();
        $expectedTreeSubNode = new AtImportInclude();
        $expectedTreeSubNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $expectedTreeSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $expectedTreeSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $expectedTreeSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup'\n")->getNextLine())[0]);
        $expectedTree->addChild($expectedTreeSubNode);
        $expectedTreeSubNode = new DefaultTypoScriptMagicKeyInclude();
        $expectedTreeSubNode->setLineStream((new LosslessTokenizer())->tokenize('foo23'));
        $expectedTreeSubNode->setIdentifier('globals-defaultTypoScript-setup-defaultContentRendering-EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $expectedTreeSubNode->setName('TYPO3_CONF_VARS defaultContentRendering for EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $expectedTree->addChild($expectedTreeSubNode);

        $tree = (new FileInclude());
        $tree->setLineStream((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup'\n"));
        $treeFromTokenStreamBuilder = $this->get(TreeFromLineStreamBuilder::class);
        $treeFromTokenStreamBuilder->setTokenizer(new LosslessTokenizer());
        $treeFromTokenStreamBuilder->buildTree($tree, 'setup');
        self::assertEquals($expectedTree, $tree);
    }

    /**
     * @test
     */
    public function atImportIncludesMagicTypoScriptRenderingForDirectoryInclude(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'] = [
            'core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'] = 'foo23';

        $expectedTree = new FileInclude();
        $expectedTree->setLineStream((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/'\n"));
        $expectedTree->setSplit();
        $expectedTreeSubNode = new AtImportInclude();
        $expectedTreeSubNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $expectedTreeSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $expectedTreeSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $expectedTreeSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/'\n")->getNextLine())[0]);
        $expectedTree->addChild($expectedTreeSubNode);
        $expectedTreeSubNode = new DefaultTypoScriptMagicKeyInclude();
        $expectedTreeSubNode->setLineStream((new LosslessTokenizer())->tokenize('foo23'));
        $expectedTreeSubNode->setIdentifier('globals-defaultTypoScript-setup-defaultContentRendering-EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $expectedTreeSubNode->setName('TYPO3_CONF_VARS defaultContentRendering for EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $expectedTree->addChild($expectedTreeSubNode);
        $expectedTreeSubNode = new AtImportInclude();
        $expectedTreeSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup2.typoscript');
        $expectedTreeSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup2.typoscript');
        $expectedTreeSubNode->setLineStream((new LosslessTokenizer())->tokenize("setup2.typoscript\n"));
        $expectedTreeSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/'\n")->getNextLine())[0]);
        $expectedTree->addChild($expectedTreeSubNode);
        $expectedTreeSubNode = new AtImportInclude();
        $expectedTreeSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/subInclude.typoscript');
        $expectedTreeSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/subInclude.typoscript');
        $expectedTreeSubNode->setLineStream((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript'\n"));
        $expectedTreeSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/'\n")->getNextLine())[0]);
        $expectedTreeSubNode->setSplit();
        $expectedTreeSubSubNode = new AtImportInclude();
        $expectedTreeSubSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript');
        $expectedTreeSubSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript');
        $expectedTreeSubSubNode->setLineStream((new LosslessTokenizer())->tokenize("SubDirectory.typoscript\n"));
        $expectedTreeSubSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/SubDirectory/SubDirectory.typoscript'\n")->getNextLine())[0]);
        $expectedTreeSubNode->addChild($expectedTreeSubSubNode);
        $expectedTree->addChild($expectedTreeSubNode);

        $tree = (new FileInclude());
        $tree->setLineStream((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/'\n"));
        $treeFromTokenStreamBuilder = $this->get(TreeFromLineStreamBuilder::class);
        $treeFromTokenStreamBuilder->setTokenizer(new LosslessTokenizer());
        $treeFromTokenStreamBuilder->buildTree($tree, 'setup');
        self::assertEquals($expectedTree, $tree);
    }

    /**
     * @test
     */
    public function atImportIncludesMagicTypoScriptRenderingForWildcardInclude(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'] = [
            'core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'] = 'foo23';

        $expectedTree = new FileInclude();
        $expectedTree->setLineStream((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup*'\n"));
        $expectedTree->setSplit();
        $expectedTreeSubNode = new AtImportInclude();
        $expectedTreeSubNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $expectedTreeSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $expectedTreeSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $expectedTreeSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup*'\n")->getNextLine())[0]);
        $expectedTree->addChild($expectedTreeSubNode);
        $expectedTreeSubNode = new DefaultTypoScriptMagicKeyInclude();
        $expectedTreeSubNode->setLineStream((new LosslessTokenizer())->tokenize('foo23'));
        $expectedTreeSubNode->setIdentifier('globals-defaultTypoScript-setup-defaultContentRendering-EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $expectedTreeSubNode->setName('TYPO3_CONF_VARS defaultContentRendering for EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup.typoscript');
        $expectedTree->addChild($expectedTreeSubNode);
        $expectedTreeSubNode = new AtImportInclude();
        $expectedTreeSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup2.typoscript');
        $expectedTreeSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup2.typoscript');
        $expectedTreeSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup*'\n")->getNextLine())[0]);
        $expectedTreeSubNode->setLineStream((new LosslessTokenizer())->tokenize("setup2.typoscript\n"));
        $expectedTree->addChild($expectedTreeSubNode);

        $tree = (new FileInclude());
        $tree->setLineStream((new LosslessTokenizer())->tokenize("@import 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1/setup*'\n"));
        $treeFromTokenStreamBuilder = $this->get(TreeFromLineStreamBuilder::class);
        $treeFromTokenStreamBuilder->setTokenizer(new LosslessTokenizer());
        $treeFromTokenStreamBuilder->buildTree($tree, 'setup');
        self::assertEquals($expectedTree, $tree);
    }

    /**
     * @test
     */
    public function importTyposcriptIncludesMagicTypoScriptRenderingForSimpleFile(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'] = [
            'core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'] = 'foo23';

        $expectedTree = new FileInclude();
        $expectedTree->setLineStream((new LosslessTokenizer())->tokenize('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript">'));
        $expectedTree->setSplit();
        $expectedTreeSubNode = new IncludeTyposcriptInclude();
        $expectedTreeSubNode->setLineStream((new LosslessTokenizer())->tokenize("setup.typoscript\n"));
        $expectedTreeSubNode->setIdentifier('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript');
        $expectedTreeSubNode->setName('EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript');
        $expectedTreeSubNode->setOriginalLine(iterator_to_array((new LosslessTokenizer())->tokenize('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript">')->getNextLine())[0]);
        $expectedTree->addChild($expectedTreeSubNode);
        $expectedTreeSubNode = new DefaultTypoScriptMagicKeyInclude();
        $expectedTreeSubNode->setLineStream((new LosslessTokenizer())->tokenize('foo23'));
        $expectedTreeSubNode->setIdentifier('globals-defaultTypoScript-setup-defaultContentRendering-EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript');
        $expectedTreeSubNode->setName('TYPO3_CONF_VARS defaultContentRendering for EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript');
        $expectedTree->addChild($expectedTreeSubNode);

        $tree = (new FileInclude());
        $tree->setLineStream((new LosslessTokenizer())->tokenize('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/IncludeTyposcript/ExtImport/Scenario1/setup.typoscript">'));
        $treeFromTokenStreamBuilder = $this->get(TreeFromLineStreamBuilder::class);
        $treeFromTokenStreamBuilder->setTokenizer(new LosslessTokenizer());
        $treeFromTokenStreamBuilder->buildTree($tree, 'setup');
        self::assertEquals($expectedTree, $tree);
    }
}
