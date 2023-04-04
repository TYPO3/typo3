<?php

declare(strict_types=1);

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * This script checks all class tests are declared final.
 */
class NodeVisitor extends NodeVisitorAbstract
{
    public array $matches = [];

    public function enterNode(Node $node): void
    {
        if (($node instanceof Node\Stmt\Class_) && !$node->isFinal() && !$node->isAnonymous() && !$node->isAbstract()) {
            $this->matches[$node->getLine()] = $node->name;
        }
    }
}

$parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);

$finder = new Symfony\Component\Finder\Finder();
$finder->files()
    ->in([
        __DIR__ . '/../../typo3/sysext/*/Tests/Unit/',
        __DIR__ . '/../../typo3/sysext/*/Tests/UnitDeprecated/',
        __DIR__ . '/../../typo3/sysext/*/Tests/Functional/',
        __DIR__ . '/../../typo3/sysext/*/Tests/FunctionalDeprecated/',
    ])
    ->name('/Test\.php$/');

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
        $output->writeln('<error>Test class should be marked as final. Found in ' . $file . '</error>');

        /**
         * @var array $matchesPerLine
         * @var int $line
         * @var array $matches
         */
        foreach ($matchesPerLine as $line => $methodName) {
            $output->writeln('Method:' . $methodName . ' Line:' . $line);
        }
    }
    exit(1);
}

exit(0);
