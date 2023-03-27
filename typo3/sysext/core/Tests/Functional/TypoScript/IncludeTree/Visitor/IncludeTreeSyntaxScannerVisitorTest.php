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

namespace TYPO3\CMS\Core\Tests\Functional\TypoScript\IncludeTree\Visitor;

use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\FileInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeSyntaxScannerVisitor;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LosslessTokenizer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class IncludeTreeSyntaxScannerVisitorTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function visitDataProvider(): iterable
    {
        $node = new FileInclude();
        $node->setLineStream((new LosslessTokenizer())->tokenize('
          foo {
            bar = barValue
          }
        '));
        yield 'no errors detected' => [
            $node,
            [],
        ];

        $node = new FileInclude();
        $node->setLineStream((new LosslessTokenizer())->tokenize('
          foo {
            bar = barValue
        '));
        yield 'brace missing' => [
            $node,
            [
                [
                    'type' => 'brace.missing',
                    'include' => $node,
                    'lineNumber' => 3,
                ],
            ],
        ];

        $node = new FileInclude();
        $node->setLineStream((new LosslessTokenizer())->tokenize('
          }
        '));
        yield 'brace excess' => [
            $node,
            [
                [
                    'type' => 'brace.excess',
                    'include' => $node,
                    'lineNumber' => 1,
                ],
            ],
        ];

        $node = new FileInclude();
        $node->setLineStream((new LosslessTokenizer())->tokenize('
          foo {
            bar = barValue
          }
          }

          foo2 {
            bar2 = bar2Value
        '));
        yield 'brace excess and brace missing' => [
            $node,
            [
                [
                    'type' => 'brace.excess',
                    'include' => $node,
                    'lineNumber' => 4,
                ],
                [
                    'type' => 'brace.missing',
                    'include' => $node,
                    'lineNumber' => 8,
                ],
            ],
        ];

        $node = new FileInclude();
        $node->setLineStream((new LosslessTokenizer())->tokenize('
          foo <
        '));
        yield 'invalid line' => [
            $node,
            [
                [
                    'type' => 'line.invalid',
                    'include' => $node,
                    'lineNumber' => 1,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider visitDataProvider
     */
    public function visit(IncludeInterface $node, array $expectedErrors): void
    {
        $subject = new IncludeTreeSyntaxScannerVisitor();
        $subject->visit($node, 0);
        self::assertEquals($expectedErrors, $this->removeLineFromErrors($subject->getErrors()));
    }

    /**
     * Helper method to remove line. This is more convenient
     * to compare, we simply rely on lineNumber.
     */
    private function removeLineFromErrors(array $errors): array
    {
        foreach ($errors as &$error) {
            unset($error['line']);
        }
        return $errors;
    }
}
