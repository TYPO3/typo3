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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Redirects\Service\IntegrityService;

#[AsCommand('redirects:checkintegrity', 'Check integrity of redirects')]
class CheckIntegrityCommand extends Command
{
    private const REGISTRY_NAMESPACE = 'tx_redirects';
    public const REGISTRY_KEY_CONFLICTING_REDIRECTS = 'conflicting_redirects';
    public const REGISTRY_KEY_LAST_TIMESTAMP_CHECK_INTEGRITY = 'redirects_check_integrity_last_check';
    private const LANGUAGE_FILE_PATH = 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf';

    public function __construct(
        private readonly Registry $registry,
        private readonly IntegrityService $integrityService,
        private readonly SiteFinder $siteFinder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'site',
            InputArgument::OPTIONAL,
            'If set, then only pages of a specific site are checked',
            '',
            function (): array {
                return array_keys($this->siteFinder->getAllSites());
            }
        );
    }

    /**
     * Executes the command for checking for conflicting redirects
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->registry->remove(self::REGISTRY_NAMESPACE, self::REGISTRY_KEY_CONFLICTING_REDIRECTS);
        $this->registry->remove(self::REGISTRY_NAMESPACE, self::REGISTRY_KEY_LAST_TIMESTAMP_CHECK_INTEGRITY);

        $conflictingRedirects = [];
        $list = [];
        $site = $input->getArgument('site') ?: null;

        $table = new Table($output);
        $table->setHeaders(
            [
                LocalizationUtility::translate(
                    self::LANGUAGE_FILE_PATH . ':sys_redirect.uid'
                ),
                LocalizationUtility::translate(
                    self::LANGUAGE_FILE_PATH . ':sys_redirect.source_host'
                ),
                LocalizationUtility::translate(
                    self::LANGUAGE_FILE_PATH . ':sys_redirect.source_path'
                ),
                LocalizationUtility::translate(
                    self::LANGUAGE_FILE_PATH . ':sys_redirect.target'
                ),
                LocalizationUtility::translate(
                    self::LANGUAGE_FILE_PATH . ':sys_redirect.integrity_status'
                ),
            ]
        );

        $integrityStatusLabel = ':sys_redirect.integrity_status.';
        foreach ($this->integrityService->findConflictingRedirects($site) as $conflict) {
            $conflictingRedirects[] = [
                $conflict['redirect']['uid'],
                $conflict['redirect']['source_host'],
                $conflict['redirect']['source_path'],
                $conflict['uri'],
                LocalizationUtility::translate(
                    self::LANGUAGE_FILE_PATH . $integrityStatusLabel . $conflict['redirect']['integrity_status']
                ),
            ];
            $list[] = $conflict;
            $this->integrityService->setIntegrityStatus($conflict['redirect']);
        }

        if ($conflictingRedirects !== []) {
            $table->setRows($conflictingRedirects);
            $table->render();
        }

        $this->registry->set(self::REGISTRY_NAMESPACE, self::REGISTRY_KEY_CONFLICTING_REDIRECTS, $list);
        $this->registry->set(self::REGISTRY_NAMESPACE, self::REGISTRY_KEY_LAST_TIMESTAMP_CHECK_INTEGRITY, time());
        return Command::SUCCESS;
    }
}
