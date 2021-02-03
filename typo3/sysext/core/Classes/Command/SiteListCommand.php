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
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Command for listing all configured sites
 */
class SiteListCommand extends Command
{
    /**
     * @var SiteFinder
     */
    protected $siteFinder;

    public function __construct(SiteFinder $siteFinder)
    {
        $this->siteFinder = $siteFinder;
        parent::__construct();
    }

    /**
     * Shows a table with all configured sites
     *
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $sites = $this->siteFinder->getAllSites();

        if (empty($sites)) {
            $io->title('No sites configured');
            $io->note('Configure new sites in the "Sites" module.');
            return 0;
        }

        $io->title('All configured sites');
        $table = new Table($output);
        $table->setHeaders([
            'Identifier',
            'Root PID',
            'Base URL',
            'Language',
            'Locale',
            'Status',
        ]);
        foreach ($sites as $site) {
            $baseUrls = [];
            $languages = [];
            $locales = [];
            $status = [];
            foreach ($site->getLanguages() as $language) {
                $baseUrls[] = (string)$language->getBase();
                $languages[] = sprintf(
                    '%s (id:%d)',
                    $language->getTitle(),
                    $language->getLanguageId()
                );
                $locales[] = $language->getLocale();
                $status[] = $language->isEnabled()
                    ? '<fg=green>enabled</>'
                    : '<fg=yellow>disabled</>';
            }
            $table->addRow(
                [
                    '<options=bold>' . $site->getIdentifier() . '</>',
                    $site->getRootPageId(),
                    implode("\n", $baseUrls),
                    implode("\n", $languages),
                    implode("\n", $locales),
                    implode("\n", $status),
                ]
            );
        }
        $table->render();
        return 0;
    }
}
