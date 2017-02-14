<?php
namespace TYPO3\CMS\Extbase\Command;

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

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;

/**
 * Wrapper to wrap an Extbase command from a command controller into a Symfony Command
 */
class ExtbaseCommand extends Command
{
    /**
     * Extbase's command
     * @var \TYPO3\CMS\Extbase\Mvc\Cli\Command
     */
    protected $command;

    /**
     * Extbase has its own validation logic, so it is disabled in this place
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();
    }

    /**
     * Sets the extbase command to be used for fetching the description etc.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Cli\Command $command
     */
    public function setExtbaseCommand(\TYPO3\CMS\Extbase\Mvc\Cli\Command $command)
    {
        $this->command = $command;
    }

    /**
     * Sets the application instance for this command.
     * Also uses the setApplication call now, as $this->configure() is called
     * too early
     *
     * @param Application $application An Application instance
     */
    public function setApplication(Application $application = null)
    {
        parent::setApplication($application);
        $description = $this->command->getDescription();
        $description = str_replace(LF, ' ', $description);
        $this->setDescription($description);
    }

    /**
     * Executes the command to find any Extbase command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // ugly hack because extbase only knows "help" (hardcoded, but already defined by symfony)
        // and "extbase:help:help"
        if ($_SERVER['argv'][1] === 'extbase:help') {
            $_SERVER['argv'][1] = 'extbase:help:help';
        }
        $bootstrap = GeneralUtility::makeInstance(Bootstrap::class);
        $bootstrap->run('', []);
    }
}
