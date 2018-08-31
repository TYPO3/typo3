<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Command;

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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Command for showing the configuration of a site
 */
class SiteShowCommand extends Command
{
    /**
     * Defines the allowed options for this command
     */
    protected function configure()
    {
        $this->setDescription('Shows the configuration of the specified site. Specify the identifier via "site:show <identifier>".')
            ->addArgument(
                'identifier',
                InputArgument::REQUIRED,
                'The identifier of the site'
            );
    }

    /**
     * Shows the configuration of a site
     *
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $siteFinder = new SiteFinder();
        $site = $siteFinder->getSiteByIdentifier($input->getArgument('identifier'));
        $io->title('Site configuration for ' . $input->getArgument('identifier'));
        $io->block(Yaml::dump($site->getConfiguration(), 4));
    }
}
