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

namespace TYPO3\CMS\Backend\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Backend\Module\ModuleRegistry;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;

/**
 * Debug backend routes, including module routes, AJAX routes and regular routes.
 * Similar to Symfony's debug:router command.
 * @internal only for development purposes
 */
#[AsCommand('debug:backend:routes', 'Debugging: List all registered backend routes (only for development purpose)')]
#[AsNonSchedulableCommand]
class DebugBackendRoutesCommand extends Command
{
    public function __construct(
        private readonly Router $router,
        private readonly ModuleRegistry $moduleRegistry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'json',
                null,
                InputOption::VALUE_NONE,
                'Output routes in JSON format'
            )
            ->addOption(
                'filter',
                'f',
                InputOption::VALUE_REQUIRED,
                'Filter routes by name (supports partial matching)'
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'Limit routes by type: ajax, module, or route'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $jsonOutput = $input->getOption('json');
        $filter = $input->getOption('filter');
        $limit = $input->getOption('limit');

        // Validate limit option
        if ($limit !== null) {
            $limit = strtolower($limit);
            if (!in_array($limit, ['ajax', 'module', 'route'], true)) {
                $io->error('Invalid limit type. Valid options are: ajax, module, route');
                return Command::FAILURE;
            }
        }

        // Collect all routes
        $routes = $this->collectAllRoutes($filter, $limit);

        if (empty($routes)) {
            $messages = [];
            if ($filter) {
                $messages[] = 'filter: ' . $filter;
            }
            if ($limit) {
                $messages[] = 'type: ' . $limit;
            }
            if ($messages) {
                $io->warning('No routes found matching ' . implode(', ', $messages));
            } else {
                $io->warning('No routes found.');
            }
            return Command::SUCCESS;
        }

        // Sort routes by name
        ksort($routes);

        if ($jsonOutput) {
            $this->outputJson($routes, $output);
        } else {
            $this->outputTable($routes, $io, $output);
        }

        return Command::SUCCESS;
    }

    /**
     * Collect all routes from Router (including AJAX routes) and Module routes
     *
     * @return array<string, array{name: string, method: string, path: string, target: string, type: string, options: array}>
     */
    private function collectAllRoutes(?string $filter, ?string $limitType): array
    {
        $routes = [];

        // Build a set of module identifiers for quick lookup
        $moduleIdentifiers = [];
        foreach ($this->moduleRegistry->getModules() as $module) {
            if ($module->hasParentModule() || $module->isStandalone()) {
                $moduleIdentifiers[$module->getIdentifier()] = true;
            }
        }

        // Get all routes from Router (includes regular routes, AJAX routes, and module routes)
        // Note: Routes can be either TYPO3 Route or Symfony Route objects
        foreach ($this->router->getRoutes() as $routeName => $route) {
            if ($filter && !str_contains((string)$routeName, $filter)) {
                continue;
            }

            // Determine route type
            $type = 'Route';
            if (str_starts_with((string)$routeName, 'ajax_')) {
                $type = 'Ajax';
            } elseif (isset($moduleIdentifiers[(string)$routeName])) {
                $type = 'Module';
            }

            // Apply limit filter if specified
            if ($limitType !== null) {
                $typeNormalized = strtolower($type);
                if ($typeNormalized !== $limitType) {
                    continue;
                }
            }

            // Get methods - works for both Symfony and TYPO3 Route objects
            $methods = $route->getMethods();
            $methodString = empty($methods) ? 'ANY' : implode('|', $methods);

            // Get options - works for both route types
            // phpDoc inference is too narrow as Symfony routes are possible. Ignored in the scope of this debug output:
            // @phpstan-ignore function.alreadyNarrowedType
            $options = method_exists($route, 'getOptions') ? $route->getOptions() : [];
            $target = $options['target'] ?? $options['_controller'] ?? '-';

            $routes[$routeName] = [
                'name' => (string)$routeName,
                'method' => $methodString,
                'path' => $route->getPath(),
                'target' => $target,
                'type' => $type,
                'options' => $options,
            ];
        }

        return $routes;
    }

    /**
     * Output routes as JSON
     */
    private function outputJson(array $routes, OutputInterface $output): void
    {
        $jsonData = [];
        foreach ($routes as $route) {
            $jsonData[] = [
                'name' => $route['name'],
                'method' => $route['method'],
                'path' => $route['path'],
                'target' => $route['target'],
                'type' => $route['type'],
                'options' => $route['options'],
            ];
        }

        $output->writeln((string)json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Output routes as formatted table (similar to Symfony's debug:router)
     */
    private function outputTable(array $routes, SymfonyStyle $io, OutputInterface $output): void
    {
        $table = new Table($output);
        $table->setHeaders(['Name', 'Method', 'Path', 'Target', 'Type']);
        $rows = [];
        foreach ($routes as $route) {
            $rows[] = [
                $route['name'],
                $route['method'],
                $route['path'],
                $this->formatTarget($route['target']),
                $route['type'],
            ];
        }

        $table->setRows($rows);
        $table->render();

        $io->newLine();
        $io->writeln(sprintf('<info>%d</info> routes found', count($routes)));
    }

    /**
     * Format the target for display (shorten class names)
     */
    private function formatTarget(string $target): string
    {
        // Shorten TYPO3 class names for better readability
        $target = str_replace('TYPO3\\CMS\\', '', $target);

        // Limit length if too long
        if (strlen($target) > 80) {
            return substr($target, 0, 77) . '...';
        }

        return $target;
    }
}
