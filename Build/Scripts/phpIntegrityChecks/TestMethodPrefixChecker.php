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

use PhpParser\Node;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * check all test classes based on phpunit contain only test methods with annotation or attribute, but not with the `test` prefix.
 * report on any method using a prefix
 */
final class TestMethodPrefixChecker extends AbstractPhpIntegrityChecker
{
    public function canHandle(\SplFileInfo $file): bool
    {
        if (!str_contains($file->getRealPath(), 'Tests/Unit') && !str_contains($file->getRealPath(), 'Tests/Functional')) {
            return false;
        }
        if (!str_ends_with($file->getBasename(), 'Test.php')) {
            return false;
        }
        return true;
    }

    public function enterNode(Node $node): void
    {
        if (($node instanceof Node\Stmt\ClassMethod) && str_starts_with($node->name->name, 'test')) {
            $this->messages[$this->getRelativeFileNameFromRepositoryRoot()][$node->getLine()] = $node->name->name;
        }
    }

    public function outputResult(SymfonyStyle $io, array $issueCollection): void
    {
        $io->title('Test Method prefix checker result');
        if ($issueCollection !== []) {
            $io->error('Following test methods should not start with "test". Use @test phpDoc annotation or #[Test] attribute instead.');
            $table = new Table($io);
            $table->setHeaders([
                'File',
                'Line',
                'Method',
            ]);
            foreach ($issueCollection as $file => $issues) {
                foreach ($issues as $line => $issue) {
                    $table->addRow([
                        $file,
                        $line,
                        $issue,
                    ]);
                }
            }
            $table->render();
        } else {
            $io->success('Test method prefix integrity is in good shape.');
        }
    }
}
