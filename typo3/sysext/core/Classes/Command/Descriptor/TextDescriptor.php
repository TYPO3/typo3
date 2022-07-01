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

namespace TYPO3\CMS\Core\Command\Descriptor;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Descriptor\ApplicationDescription;
use Symfony\Component\Console\Descriptor\TextDescriptor as SymfonyTextDescriptor;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputDefinition;
use TYPO3\CMS\Core\Console\CommandRegistry;

/**
 * Text descriptor.
 *
 * @internal
 */
class TextDescriptor extends SymfonyTextDescriptor
{
    private CommandRegistry $commandRegistry;
    private bool $degraded;

    public function __construct(CommandRegistry $commandRegistry, bool $degraded)
    {
        $this->commandRegistry = $commandRegistry;
        $this->degraded = $degraded;
    }

    /**
     * {@inheritdoc}
     */
    protected function describeApplication(Application $application, array $options = [])
    {
        $describedNamespace = $options['namespace'] ?? null;
        $rawOutput = $options['raw_text'] ?? false;

        $commands = $this->commandRegistry->filter($describedNamespace);

        if ($rawOutput) {
            $width = $this->getColumnWidth(['' => ['commands' => array_keys($commands)]]);

            foreach ($commands as $command) {
                $this->write(sprintf("%-{$width}s %s\n", $command['name'], strip_tags($command['description'])), true);
            }
            return;
        }

        if ($this->degraded) {
            $this->write("<error>Failed to boot dependency injection, only lowlevel commands are available.</error>\n\n", true);
        }

        $namespaces = $this->commandRegistry->getNamespaces();
        $help = $application->getHelp();
        if ($help !== '') {
            $this->write($help . "\n\n", true);
        }

        $this->write("<comment>Usage:</comment>\n", true);
        $this->write("  command [options] [arguments]\n\n");

        $this->describeInputDefinition(new InputDefinition($application->getDefinition()->getOptions()));

        $this->write("\n\n");

        if ($describedNamespace) {
            $this->write(sprintf('<comment>Available commands for the "%s" namespace:</comment>', $describedNamespace), true);
            $namespace = $namespaces[$describedNamespace] ?? [];
            $width = $this->getColumnWidth(['' => $namespace]);
            $this->describeNamespace($namespace, $commands, $width);
        } else {
            $this->write('<comment>Available commands:</comment>', true);
            // calculate max. width based on available commands per namespace
            $width = $this->getColumnWidth($namespaces);
            foreach ($namespaces as $namespace) {
                if ($namespace['id'] !== ApplicationDescription::GLOBAL_NAMESPACE) {
                    $this->write("\n");
                    $this->write(' <comment>' . $namespace['id'] . '</comment>', true);
                }
                $this->describeNamespace($namespace, $commands, $width);
            }
        }

        $this->write("\n");

        if ($this->degraded) {
            $this->write("\n<error>Failed to boot dependency injection, only lowlevel commands are available.</error>\n", true);
        }
    }

    private function describeNamespace(array $namespace, array $commands, int $width): void
    {
        foreach ($namespace['commands'] as $name) {
            $this->write("\n");
            $spacingWidth = $width - Helper::strlen($name);
            $command = $commands[$name];

            $aliases = count($command['aliases']) ? '[' . implode('|', $command['aliases']) . '] ' : '';
            $this->write(sprintf('  <info>%s</info>%s%s', $name, str_repeat(' ', $spacingWidth), $aliases . $command['description']), true);
        }
    }

    private function getColumnWidth(array $namespaces): int
    {
        $widths = [];
        foreach ($namespaces as $name => $namespace) {
            $widths[] = Helper::strlen($name);
            foreach ($namespace['commands'] as $commandName) {
                $widths[] = Helper::strlen($commandName);
            }
        }

        return $widths ? max($widths) + 2 : 0;
    }
}
