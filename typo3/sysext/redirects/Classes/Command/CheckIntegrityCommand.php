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

namespace TYPO3\CMS\Redirects\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Redirects\Service\IntegrityService;

class CheckIntegrityCommand extends Command
{
    private const REGISTRY_NAMESPACE = 'tx_redirects';
    private const REGISTRY_KEY = 'conflicting_redirects';

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var IntegrityService
     */
    private $integrityService;

    public function __construct(Registry $registry, IntegrityService $integrityService)
    {
        $this->registry = $registry;
        $this->integrityService = $integrityService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'site',
            InputArgument::OPTIONAL,
            'If set, then only pages of a specific site are checked',
            ''
        );
    }

    /**
     * Executes the command for checking for conflicting redirects
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->registry->remove(self::REGISTRY_NAMESPACE, self::REGISTRY_KEY);

        $conflictingRedirects = [];
        $list = [];
        $site = $input->getArgument('site') ?: null;

        $table = new Table($output);
        $table->setHeaders(
            [
                LocalizationUtility::translate(
                    'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.source_host'
                ),
                LocalizationUtility::translate(
                    'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.source_path'
                ),
                LocalizationUtility::translate(
                    'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target'
                ),
            ]
        );

        foreach ($this->integrityService->findConflictingRedirects($site) as $conflict) {
            $conflictingRedirects[] = [
                $conflict['redirect']['source_host'],
                $conflict['redirect']['source_path'],
                $conflict['uri'],
            ];
            $list[] = $conflict;
        }

        if ($conflictingRedirects !== []) {
            $table->setRows($conflictingRedirects);
            $table->render();
        }
        $this->registry->set(self::REGISTRY_NAMESPACE, self::REGISTRY_KEY, $list);
        return Command::SUCCESS;
    }
}
