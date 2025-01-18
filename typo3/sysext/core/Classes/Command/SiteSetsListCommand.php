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

namespace TYPO3\CMS\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Set\SetRegistry;

/**
 * Command for listing all configured sites
 */
class SiteSetsListCommand extends Command
{
    public function __construct(
        protected readonly SetRegistry $setRegistry
    ) {
        parent::__construct();
    }

    /**
     * Defines the allowed options for this command
     */
    protected function configure(): void
    {
        $this->setDefinition([
            new InputOption('all', 'a', InputOption::VALUE_NONE, 'Show all sets, including hidden ones.'),
        ]);
    }

    /**
     * Shows a table with all configured sites
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $showAll = $input->getOption('all') ?? false;
        $sets = $this->setRegistry->getAllSets();

        if ($sets === []) {
            $io->title('No site sets configured');
            $io->note('Configure new sites by placing a Configuration/Sets/MySetName/config.yaml in an extension.');
            return Command::SUCCESS;
        }

        $io->title('All configured site sets');
        $table = new Table($output);
        $table->setHeaders([
            'Name',
            'Label',
            'Dependencies',
        ]);
        foreach ($sets as $set) {
            if ($set->hidden && !$showAll) {
                continue;
            }
            $table->addRow(
                [
                    '<options=bold>' . $set->name . ($set->hidden ? ' (hidden)' : '') . '</>',
                    $this->getLanguageService()->sL($set->label),
                    implode(', ', [
                        ...$set->dependencies,
                        ...array_map(static fn(string $d): string => '(' . $d . ')', $set->optionalDependencies),
                    ]),
                ]
            );
        }
        $table->render();

        $invalidSets = $this->setRegistry->getInvalidSets();
        if ($invalidSets !== []) {
            $io->newLine();
            $io->newLine();
            $io->title('Invalid site set configurations');
            $table = new Table($output);
            $table->setHeaders([
                'Set',
                'Error',
            ]);
            foreach ($invalidSets as $invalidSet) {
                $table->addRow(
                    [
                        $invalidSet['name'],
                        sprintf(
                            $this->getLanguageService()->sL($invalidSet['error']->getLabel()),
                            $invalidSet['name'],
                            $invalidSet['context'],
                        ),
                    ]
                );
            }
            $table->render();
        }

        return Command::SUCCESS;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
