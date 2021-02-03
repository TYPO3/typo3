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

use Symfony\Component\Console\Command\ListCommand as SymfonyListCommand;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Command\Descriptor\TextDescriptor;
use TYPO3\CMS\Core\Console\CommandRegistry;

/**
 * ListCommand displays the list of all available commands for the application.
 */
class ListCommand extends SymfonyListCommand
{
    protected CommandRegistry $commandRegistry;

    public function __construct(CommandRegistry $commandRegistry)
    {
        $this->commandRegistry = $commandRegistry;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = new DescriptorHelper();
        $helper->register('txt', new TextDescriptor($this->commandRegistry));
        $helper->describe($output, $this->getApplication(), [
            'format' => $input->getOption('format'),
            'raw_text' => $input->getOption('raw'),
            'namespace' => $input->getArgument('namespace'),
        ]);

        return 0;
    }
}
