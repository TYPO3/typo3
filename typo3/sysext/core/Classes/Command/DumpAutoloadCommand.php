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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Core\Environment;

/**
 * Command for dumping the class-loading information.
 */
class DumpAutoloadCommand extends Command
{
    /**
     * Defines the allowed options for this command
     */
    protected function configure()
    {
        $this->setName('dumpautoload');
        $this->setDescription('Updates class loading information in non-composer mode.');
        $this->setHelp('This command is only needed during development. The extension manager takes care of creating or updating this info properly during extension (de-)activation.');
        $this->setAliases([
            'extensionmanager:extension:dumpclassloadinginformation',
            'extension:dumpclassloadinginformation',
        ]);
    }

    /**
     * This command is not needed in composer mode.
     *
     * @inheritdoc
     */
    public function isEnabled()
    {
        return !Environment::isComposerMode();
    }

    /**
     * Dumps the class loading information
     *
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        ClassLoadingInformation::dumpClassLoadingInformation();
        $io->success('Class loading information has been updated.');
        return 0;
    }
}
