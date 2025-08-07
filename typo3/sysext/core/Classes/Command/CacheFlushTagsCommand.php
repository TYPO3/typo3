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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CacheFlushTagsCommand extends Command
{
    public function __construct(
        private readonly CacheManager $cacheManager
    ) {
        parent::__construct('cache:flushtags');
    }

    /**
     * Defines the allowed options for this command
     */
    protected function configure(): void
    {
        $this->setDescription('Flush TYPO3 caches with tags.');
        $this->setHelp('This command can be used to clear the caches with specific tags, for example after code updates in local development and after deployments.');
        $this->setDefinition([
            new InputArgument(
                'tags',
                InputArgument::REQUIRED,
                'Array of tags (specified as comma separated values) to flush.'
            ),
            new InputOption(
                'groups',
                'g',
                InputOption::VALUE_REQUIRED,
                'Array of groups (specified as comma separated values) for which to flush tags. If no group is specified, caches of all groups are flushed.',
                'all'
            ),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $groups = GeneralUtility::trimExplode(',', $input->getOption('groups') ?? '', true);
        $tags = GeneralUtility::trimExplode(',', $input->getArgument('tags') ?? '', true);

        foreach ($groups as $group) {
            if ($group === 'all') {
                $this->cacheManager->flushCachesByTags($tags);
                continue;
            }

            $this->cacheManager->flushCachesInGroupByTags($group, $tags);
        }

        return Command::SUCCESS;
    }
}
