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

use phpDocumentor\Reflection\DocBlockFactory;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../../vendor/autoload.php';

class NodeVisitor implements \PhpParser\NodeVisitor
{
    /**
     * @var DocBlockFactory
     */
    private $docBlockFactory;

    /**
     * @var string|null
     */
    public $namespace;

    /**
     * @var string|null
     */
    public $className;

    /**
     * @var string|null
     */
    public $classCommentError;

    /**
     * @var array
     */
    public $properties = [];

    /**
     * @var array
     */
    public $methods = [];

    /**
     * @var bool
     */
    public $hasErrors = false;

    /**
     * Called once before traversal.
     *
     * Return value semantics:
     *  * null:      $nodes stays as-is
     *  * otherwise: $nodes is set to the return value
     *
     * @param Node[] $nodes Array of nodes
     *
     * @return Node[]|null Array of nodes
     */
    public function beforeTraverse(array $nodes)
    {
        $this->docBlockFactory = DocBlockFactory::createInstance();
        return null;
    }

    /**
     * Called when entering a node.
     *
     * Return value semantics:
     *  * null
     *        => $node stays as-is
     *  * NodeTraverser::DONT_TRAVERSE_CHILDREN
     *        => Children of $node are not traversed. $node stays as-is
     *  * NodeTraverser::STOP_TRAVERSAL
     *        => Traversal is aborted. $node stays as-is
     *  * otherwise
     *        => $node is set to the return value
     *
     * @param Node $node Node
     *
     * @return int|Node|null Replacement node (or special return value)
     */
    public function enterNode(Node $node)
    {
        switch (get_class($node)) {
            case Node\Stmt\Namespace_::class:
                /** @var Node\Stmt\Namespace_ $node */
                $this->namespace = (string)$node->name;
                break;
            case Node\Stmt\Class_::class:
                /** @var Node\Stmt\Class_ $node */
                $this->className = (string)$node->name;

                try {
                    $docComment = $node->getDocComment();
                    if ($docComment instanceof \PhpParser\Comment) {
                        $this->docBlockFactory->create($docComment->getText());
                    }
                } catch (\Throwable $e) {
                    $this->hasErrors = true;
                    $this->classCommentError = $e->getMessage();
                }
                break;
            case Node\Stmt\Property::class:
                /** @var Node\Stmt\Property $node */
                $property = [
                    'name' => (string)$node->props[0]->name,
                    'error' => null
                ];

                try {
                    $docComment = $node->getDocComment();
                    if ($docComment instanceof \PhpParser\Comment) {
                        $this->docBlockFactory->create($docComment->getText());
                    }
                } catch (\Throwable $e) {
                    $this->hasErrors = true;
                    $property['error'] = $e->getMessage();
                }

                $this->properties[] = $property;
                break;
            case Node\Stmt\ClassMethod::class:
                /** @var Node\Stmt\ClassMethod $node */
                $method = [
                    'name' => (string)$node->name,
                    'error' => null
                ];

                try {
                    $docComment = $node->getDocComment();
                    if ($docComment instanceof \PhpParser\Comment) {
                        $this->docBlockFactory->create($docComment->getText());
                    }
                } catch (\Throwable $e) {
                    $this->hasErrors = true;
                    $method['error'] = $e->getMessage();
                }

                $this->methods[] = $method;
                break;
            default:
                break;
        }

        return null;
    }

    /**
     * Called when leaving a node.
     *
     * Return value semantics:
     *  * null
     *        => $node stays as-is
     *  * NodeTraverser::REMOVE_NODE
     *        => $node is removed from the parent array
     *  * NodeTraverser::STOP_TRAVERSAL
     *        => Traversal is aborted. $node stays as-is
     *  * array (of Nodes)
     *        => The return value is merged into the parent array (at the position of the $node)
     *  * otherwise
     *        => $node is set to the return value
     *
     * @param Node $node Node
     *
     * @return int|Node|Node[]|null Replacement node (or special return value)
     */
    public function leaveNode(Node $node)
    {
        return null;
    }

    /**
     * Called once after traversal.
     *
     * Return value semantics:
     *  * null:      $nodes stays as-is
     *  * otherwise: $nodes is set to the return value
     *
     * @param Node[] $nodes Array of nodes
     *
     * @return Node[]|null Array of nodes
     */
    public function afterTraverse(array $nodes)
    {
        return null;
    }
}

$parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);

$finder = new Symfony\Component\Finder\Finder();
$finder->files()
    ->in(__DIR__ . '/../../typo3/sysext/*/Classes/')
    ->in(__DIR__ . '/../../typo3/sysext/*/Tests/')
    ->notPath('_generated')
    ->name('/\.php$/')
//    ->notName('ServiceProviderRegistry.php')
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

    try {
        $ast = $traverser->traverse($ast);
    } catch (\Throwable $e) {
        $errors[$file->getRealPath()]['error'] = $e->getMessage();
        $output->write('<error>F</error>');
        continue;
    }

    if ($visitor->className === null || $visitor->namespace === null) {
        // only process files that contain classes for now
        continue;
    }

    if ($visitor->hasErrors) {
        $errors[$file->getRealPath()]['fqcn'] = $visitor->namespace . '\\' . $visitor->className;

        if ($visitor->classCommentError !== null) {
            $errors[$file->getRealPath()]['class'] = $visitor->classCommentError;
        }

        foreach ($visitor->properties as $property) {
            if (empty($property['error'])) {
                continue;
            }

            $errors[$file->getRealPath()]['properties'][$property['name']] = $property['error'];
        }

        foreach ($visitor->methods as $method) {
            if (empty($method['error'])) {
                continue;
            }

            $errors[$file->getRealPath()]['methods'][$method['name']] = $method['error'];
        }

        $output->write('<error>F</error>');
    } else {
        $output->write('<fg=green>.</>');
    }
}

$output->writeln('');

if (!empty($errors)) {
    foreach ($errors as $file => $errorsInFile) {
        $output->writeln('');
        $output->writeln('');
        $output->writeln('<error>' . $file . '</error>');
        $output->writeln('</>');

        if (isset($errorsInFile['class'])) {
            $table = new \Symfony\Component\Console\Helper\Table($output);
            $table->setHeaders(['Class', 'Errors']);
            $table->addRow([$errorsInFile['fqcn'], $errorsInFile['class']]);
            $table->setStyle('borderless');
            $table->render();
        }

        $properties = $errorsInFile['properties'] ?? [];
        if (count($properties)) {
            $table = new \Symfony\Component\Console\Helper\Table($output);
            $table->setHeaders(['Properties', 'Errors']);
            foreach ($properties as $propertyName => $error) {
                $table->addRow([$errorsInFile['fqcn'] . '::' . $propertyName, $error]);
            }
            $table->setStyle('borderless');
            $table->render();
        }

        $methods = $errorsInFile['methods'] ?? [];
        if (count($methods)) {
            $table = new \Symfony\Component\Console\Helper\Table($output);
            $table->setHeaders(['Methods', 'Errors']);
            foreach ($methods as $methodName => $error) {
                $table->addRow([$errorsInFile['fqcn'] . '::' . $methodName . '()', $error]);
            }
            $table->setStyle('borderless');
            $table->render();
        }
    }
    exit(1);
}

exit(0);
