#!/usr/bin/env php
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

use PhpParser\Comment\Doc;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Class NodeVisitor
 */
class NodeVisitor extends NodeVisitorAbstract
{
    /**
     * @var array
     */
    public $matches = [];

    public function enterNode(Node $node)
    {
        switch (get_class($node)) {
            case Node\Stmt\Class_::class:
            case Node\Stmt\Property::class:
            case Node\Stmt\ClassMethod::class:
                /** Node\Stmt\ClassMethod $node */
                if (!($docComment = $node->getDocComment()) instanceof Doc) {
                    return;
                }

                // These annotations are OK to have, everything else is denied
                $negativeLookaheadMatches = [
                    // Annotation tags
                    'Annotation', 'Attribute', 'Attributes', 'Required', 'Target',
                    // Widely used tags (but not existent in phpdoc)
                    'fix', 'fixme', 'override',
                    // PHPDocumentor 1 tags
                    'abstract', 'code', 'deprec', 'endcode', 'exception', 'final', 'ingroup', 'inheritdoc', 'inheritDoc', 'magic', 'name', 'toc', 'tutorial', 'private', 'static', 'staticvar', 'staticVar', 'throw',
                    // PHPDocumentor 2 tags
                    'api', 'author', 'category', 'copyright', 'deprecated', 'example', 'filesource', 'global', 'ignore', 'internal', 'license', 'link', 'method', 'package', 'param', 'property', 'property-read', 'property-write', 'return', 'see', 'since', 'source', 'subpackage', 'throws', 'todo', 'TODO', 'usedby', 'uses', 'var', 'version',
                    // PHPUnit tags
                    'codeCoverageIgnore', 'codeCoverageIgnoreStart', 'codeCoverageIgnoreEnd', 'test', 'covers', 'dataProvider', 'group', 'skip', 'depends', 'expectedException', 'before', 'requires', 'runInSeparateProcess',
                    // codeception tags
                    'env',
                    // PHPCheckStyle
                    'SuppressWarnings', 'noinspection',
                    // Extbase related
                    'TYPO3\\\\CMS\\\\Extbase\\\\Annotation\\\\IgnoreValidation', 'Extbase\\\\IgnoreValidation', 'IgnoreValidation',
                    'TYPO3\\\\CMS\\\\Extbase\\\\Annotation\\\\Inject', 'Extbase\\\\Inject', 'Inject',
                    'TYPO3\\\\CMS\\\\Extbase\\\\Annotation\\\\Validate', 'Extbase\\\\Validate', 'Validate',
                    'TYPO3\\\\CMS\\\\Extbase\\\\Annotation\\\\ORM\\\\Cascade', 'Extbase\\\\ORM\\\\Cascade', 'Cascade',
                    'TYPO3\\\\CMS\\\\Extbase\\\\Annotation\\\\ORM\\\\Lazy', 'Extbase\\\\ORM\\\\Lazy', 'Lazy',
                    'TYPO3\\\\CMS\\\\Extbase\\\\Annotation\\\\ORM\\\\Transient', 'Extbase\\\\ORM\\\\Transient', 'Transient',
                    // annotations shipped with doctrine/annotations
                    'Doctrine\\\\Common\\\\Annotations\\\\Annotation\\\\Enum', 'Enum',
                    // Extension scanner
                    'extensionScannerIgnoreFile', 'extensionScannerIgnoreLine'
                ];

                $matches = [];
                preg_match_all(
                    '/\*\s@(?!' . implode('|', $negativeLookaheadMatches) . ')(?<annotations>[a-zA-Z0-9\\\\]+)/',
                    $docComment->getText(),
                    $matches
                );

                if (!empty($matches['annotations'])) {
                    $this->matches[$node->getLine()] = array_map(function ($value) {
                        return '@' . $value;
                    }, $matches['annotations']);
                }

                break;
            default:
                break;
        }
    }
}

$parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);

$finder = new Symfony\Component\Finder\Finder();
$finder->files()
    ->in(__DIR__ . '/../../typo3/')
    ->name('/\.php$/')
    // black list some unit test fixture files from extension scanner that test matchers of old annotations
    ->notName('MethodAnnotationMatcherFixture.php')
    ->notName('PropertyAnnotationMatcherFixture.php')
;

$output = new ConsoleOutput();

$errors = [];
foreach ($finder as $file) {
    try {
        $ast = $parser->parse($file->getContents());
    } catch (Error $error) {
        $output->writeln('<error>Parse error: ' . $error->getMessage() . '</error>');
        exit(1);
    }

    $visitor = new NodeVisitor();

    $traverser = new NodeTraverser();
    $traverser->addVisitor($visitor);

    $ast = $traverser->traverse($ast);

    if (!empty($visitor->matches)) {
        $errors[$file->getRealPath()] = $visitor->matches;
        $output->write('<error>F</error>');
    } else {
        $output->write('<fg=green>.</>');
    }
}

$output->writeln('');

if (!empty($errors)) {
    $output->writeln('');

    foreach ($errors as $file => $matchesPerLine) {
        $output->writeln('');
        $output->writeln('<error>' . $file . '</error>');

        /**
         * @var array $matchesPerLine
         * @var int $line
         * @var array $matches
         */
        foreach ($matchesPerLine as $line => $matches) {
            $output->writeln($line . ': ' . implode(', ', $matches));
        }
    }
    exit(1);
}

exit(0);
