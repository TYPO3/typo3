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

namespace TYPO3\CMS\Fluid\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Fluid\Service\CacheWarmupService;

/**
 * Warmup Fluid cache for detected template files
 *
 * @internal: Specific command implementation, not API itself.
 */
#[AsCommand('fluid:cache:warmup', 'Performs a cache warmup for detected Fluid templates')]
final class WarmupCommand extends Command
{
    public function __construct(private readonly CacheWarmupService $cacheWarmupService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $results = $this->cacheWarmupService->warmupTemplatesInAllPackages();
        $formatter = new FormatterHelper();
        $returnStatus = Command::SUCCESS;
        foreach ($results as $result) {
            $templateFile = $result->path;
            if (str_starts_with($templateFile, Environment::getProjectPath())) {
                $templateFile = substr($templateFile, strlen(Environment::getProjectPath()) + 1);
            }
            foreach ($result->errors as $error) {
                $returnStatus = Command::FAILURE;
                $output->writeln($formatter->formatSection(
                    'error',
                    $templateFile . ': ' . $error->getMessage(),
                    'error',
                ));
            }
            foreach ($result->deprecations as $deprecation) {
                $output->writeln($formatter->formatSection(
                    'deprecation',
                    $templateFile . ': ' . $deprecation->message,
                    'info',
                ));
            }
        }
        return $returnStatus;
    }
}
