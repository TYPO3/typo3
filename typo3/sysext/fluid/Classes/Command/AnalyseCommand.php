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
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\Service\TemplateFinder;
use TYPO3Fluid\Fluid\Validation\TemplateValidator;

/**
 * Analyses Fluid templates for syntax errors and deprecated functionality
 *
 * @internal: Specific command implementation, not API itself.
 */
#[AsCommand(
    'fluid:analyse',
    'Analyses Fluid templates for syntax errors and deprecated functionality.',
    ['fluid:analyze'],
)]
final class AnalyseCommand extends Command
{
    public function __construct(
        private readonly TemplateFinder $templateFinder,
        private readonly RenderingContextFactory $renderingContextFactory,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $formatter = new FormatterHelper();
        $io = new SymfonyStyle($input, $output);

        $templates = $this->templateFinder->findTemplatesInAllPackages();
        $templatesCount = count($templates);
        if ($output->isVeryVerbose()) {
            $io->success(sprintf('%d templates will be analyzed:', $templatesCount));
            $index = 0;
            foreach ($templates as $template) {
                $index++;
                $output->writeln(sprintf('<info>%d</info> %s', $index, $template));
            }
        }
        $results = (new TemplateValidator())->validateTemplateFiles(
            $templates,
            $this->renderingContextFactory->create(),
        );
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
                $io->error(sprintf('%d error(s) found in %d analysed templates.', $errors, $templatesCount));
            }
            if ($deprecations > 0) {
                $output->writeln('');
                $io->warning(sprintf('%d deprecation(s) found in %d analysed templates.', $deprecations, $templatesCount));
            }
            if ($errors === 0 && $deprecations === 0) {
                $io->success(sprintf('%d templates analyzed without errors or deprecations.', $templatesCount));
            }
        }
        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
