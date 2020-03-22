<?php
declare(strict_types=1);
namespace TYPO3\CMS\Redirects\Command;

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
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Service\IntegrityService;

class CheckIntegrityCommand extends Command
{
    private const REGISTRY_NAMESPACE = 'tx_redirects';
    private const REGISTRY_KEY = 'conflicting_redirects';

    protected function configure(): void
    {
        $this
            ->setDescription('Check integrity of redirects')
            ->addArgument(
                'site',
                InputArgument::OPTIONAL,
                'If set, then only pages of a specific site are checked'
            );
    }

    /**
     * Executes the command for checking for conflicting redirects
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $registry = GeneralUtility::makeInstance(Registry::class);
        $registry->remove(static::REGISTRY_NAMESPACE, static::REGISTRY_KEY);

        $integrityService = GeneralUtility::makeInstance(IntegrityService::class);
        $list = [];
        foreach ($integrityService->findConflictingRedirects($input->getArgument('site')) as $conflict) {
            $list[] = $conflict;
            $output->writeln(sprintf(
                'Redirect (Host: %s, Path: %s) conflicts with %s',
                $conflict['redirect']['source_host'],
                $conflict['redirect']['source_path'],
                $conflict['uri']
            ));
        }
        $registry->set(static::REGISTRY_NAMESPACE, static::REGISTRY_KEY, $list);
        return 0;
    }
}
