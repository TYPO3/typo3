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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;

/**
 * Extends the help command of symfony to show the specific help for Extbase commands
 */
class HelpCommand extends \Symfony\Component\Console\Command\HelpCommand
{
    /**
     * This needs to be re-set as the parent command has this property declared as "private" as well.
     *
     * @var Command
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setAliases([]);
    }

    /**
     * Sets the command.
     *
     * @param Command $command The command to set
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (null === $this->command) {
            $this->command = $this->getApplication()->find($input->getArgument('command_name'));
        }

        // Extbase help was explicitly called
        if ($input->getArgument('command') === 'extbase:help' || $input->getArgument('command') === 'extbase:help:help') {
            $_SERVER['argv'][1] = 'extbase:help:help';
            $bootstrap = GeneralUtility::makeInstance(Bootstrap::class);
            $bootstrap->run('', []);
        } elseif ($this->command instanceof ExtbaseCommand) {
            // An extbase command was originally called, but is now required to show the help information
            // Ugly hack to modify 'argv' so the help command for a specific command is shown
            $args = [$_SERVER['argv'][0], 'help'];
            foreach ($_SERVER['argv'] as $k => $value) {
                if ($k === 0 || $value === '--help' || $value === '-h') {
                    continue;
                }
                $args[] = $value;
            }
            $_SERVER['argv'] = $args;

            // run Extbase bootstrap
            $bootstrap = GeneralUtility::makeInstance(Bootstrap::class);
            $bootstrap->run('', []);
        } else {
            // Any other symfony command should just show up the regular info
            parent::execute($input, $output);
        }
    }
}
