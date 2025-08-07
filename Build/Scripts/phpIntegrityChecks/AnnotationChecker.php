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

namespace TYPO3\CMS\PhpIntegrityChecks;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Check for allowed annotations in classes, report on any not whitelisted
 */
final class AnnotationChecker extends AbstractPhpIntegrityChecker
{
    // black list some unit test fixture files from extension scanner that test matchers of old annotations
    protected array $excludedFileNames = [
        'MethodAnnotationMatcherFixture.php',
        'PropertyAnnotationMatcherFixture.php',
    ];

    public function enterNode(Node $node): void
    {
        switch (get_class($node)) {
            case Node\Stmt\Class_::class:
            case Node\Stmt\Property::class:
            case Node\Stmt\ClassMethod::class:
                /** Node\Stmt\ClassMethod $node */
                if (!($docComment = $node->getDocComment()) instanceof Doc) {
                    return;
                }

                // These annotations are OK to have on class, class property and class method level, everything else is denied
                $negativeLookaheadMatches = [
                    // PHPDocumentor 1 tags
                    'private', 'static', 'staticvar', 'staticVar',
                    // PHPDocumentor 2 tags
                    'author', 'category', 'copyright', 'deprecated', 'example', 'internal', 'license', 'link', 'param', 'property', 'return', 'see', 'since', 'throws', 'todo', 'TODO', 'var', 'version',
                    // PHPUnit & codeception tags
                    'depends', 'env',
                    // PHPCheckStyle
                    'SuppressWarnings', 'noinspection',
                    // Extension scanner
                    'extensionScannerIgnoreFile', 'extensionScannerIgnoreLine',
                    // static code analysis
                    'template', 'implements', 'extends',
                    // phpstan specific annotations
                    'phpstan-var', 'phpstan-param', 'phpstan-return',
                ];
                // allow annotation only on class level
                if (get_class($node) === Node\Stmt\Class_::class) {
                    $negativeLookaheadMatches = array_merge(
                        $negativeLookaheadMatches,
                        [
                            // PHPStan
                            'phpstan-type', 'phpstan-import-type',
                        ]
                    );
                }

                $matches = [];
                preg_match_all(
                    '/\*(\s+)@(?!' . implode('|', $negativeLookaheadMatches) . ')(?<annotations>[a-zA-Z0-9\-\\\\]+)/',
                    $docComment->getText(),
                    $matches
                );
                if (!empty($matches['annotations'])) {
                    $this->messages[$this->getRelativeFileNameFromRepositoryRoot()][$node->getLine()] = array_map(
                        static function (string $value): string {
                            return '@' . $value;
                        },
                        $matches['annotations']
                    );
                }

                break;
            default:
                break;
        }
    }

    public function outputResult(SymfonyStyle $io, array $issueCollection): void
    {
        $io->title('Annotation checker result');
        if ($issueCollection !== []) {
            $io->error('Following annotations are invalid. Remove them:');
            $table = new Table($io);
            $table->setHeaders([
                'File',
                'Line',
                'Annotation(s)',
            ]);
            foreach ($issueCollection as $file => $issues) {
                foreach ($issues as $line => $annotations) {
                    $table->addRow([
                        $file,
                        $line,
                        implode(', ', $annotations),
                    ]);
                }
            }
            $table->render();
        } else {
            $io->success('Annotation integrity is in good shape.');
        }
    }
}
