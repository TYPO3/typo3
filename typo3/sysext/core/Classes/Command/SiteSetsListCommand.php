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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Set\SetCollector;

/**
 * Command for listing all configured sites
 */
class SiteSetsListCommand extends Command
{
    public function __construct(
        protected readonly SetCollector $setCollector
    ) {
        parent::__construct();
    }

    /**
     * Shows a table with all configured sites
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sets = $this->setCollector->getSetDefinitions();

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
            $table->addRow(
                [
                    '<options=bold>' . $set->name . '</>',
                    $this->getLanguageService()->sL($set->label),
                    implode(', ', [
                        ...$set->dependencies,
                        ...array_map(static fn(string $d): string => '(' . $d . ')', $set->optionalDependencies),
                    ]),
                ]
            );
        }
        $table->render();
        return Command::SUCCESS;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
