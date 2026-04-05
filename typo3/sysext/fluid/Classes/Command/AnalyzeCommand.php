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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\Service\TemplateFinder;
use TYPO3Fluid\Fluid\Validation\TemplateValidator;
use TYPO3Fluid\Fluid\Validation\TemplateValidatorResult;

/**
 * Analyzes Fluid templates for syntax errors and deprecated functionality
 *
 * @internal: Specific command implementation, not API itself.
 */
#[AsCommand(
    'fluid:analyze',
    'Analyzes Fluid templates for syntax errors and deprecated functionality.',
    ['fluid:analyse'],
)]
final class AnalyzeCommand extends Command
{
    public function __construct(
        private readonly TemplateFinder $templateFinder,
        private readonly RenderingContextFactory $renderingContextFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'include-system-extensions',
            null,
            InputOption::VALUE_NONE,
            'Include template files that belong to TYPO3 system extensions',
        );
        $this->addOption(
            'stdin',
            null,
            InputOption::VALUE_NONE,
            'Analyze template string that is provided via STDIN',
        );
        $this->addOption(
            'json',
            null,
            InputOption::VALUE_NONE,
            'Output results as JSON',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $templates = $input->getOption('stdin')
            ? ['php://stdin']
            : $this->templateFinder->findTemplatesInAllPackages($input->getOption('include-system-extensions'));

        if ($input->getOption('json')) {
            $result = $this->validateTemplateFiles($templates);
            $result = $input->getOption('stdin') ? $result['php://stdin'] : $result;
            $output->writeln(json_encode($result));
            return Command::SUCCESS;
        }

        $formatter = new FormatterHelper();
        $io = new SymfonyStyle($input, $output);

        $io->note('This command only analyzes templates that are using the *.fluid.* file extension.');

        $templatesCount = count($templates);
        if ($output->isVeryVerbose()) {
            $io->success(sprintf('%d templates will be analyzed:', $templatesCount));
            $index = 0;
            foreach ($templates as $template) {
                $index++;
                $output->writeln(sprintf('<info>%d</info> %s', $index, $template));
            }
        }
        $results = $this->validateTemplateFiles($templates);
        $errors = $deprecations = 0;
        foreach ($results as $result) {
            $templateFile = $result->path;
            if (str_starts_with($templateFile, Environment::getProjectPath())) {
                $templateFile = substr($templateFile, strlen(Environment::getProjectPath()) + 1);
            }
            foreach ($result->errors as $error) {
                $errors++;
                $output->writeln($formatter->formatSection(
                    'ERROR',
                    $templateFile . ': ' . $error->getMessage(),
                    'error',
                ));
            }
            foreach ($result->deprecations as $deprecation) {
                $deprecations++;
                $output->writeln($formatter->formatSection(
                    'DEPRECATION',
                    $templateFile . ': ' . $deprecation->message,
                    'info',
                ));
            }
        }
        if ($output->isVerbose()) {
            if ($errors > 0) {
                $output->writeln('');
                $io->error(sprintf('%d error(s) found in %d analyzed templates.', $errors, $templatesCount));
            }
            if ($deprecations > 0) {
                $output->writeln('');
                $io->warning(sprintf('%d deprecation(s) found in %d analyzed templates.', $deprecations, $templatesCount));
            }
            if ($errors === 0 && $deprecations === 0) {
                $io->success(sprintf('%d templates analyzed without errors or deprecations.', $templatesCount));
            }
        }
        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @return TemplateValidatorResult[]
     */
    private function validateTemplateFiles(array $templates): array
    {
        return (new TemplateValidator())->validateTemplateFiles(
            $templates,
            $this->renderingContextFactory->create(),
        );
    }
}
