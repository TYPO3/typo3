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

namespace TYPO3\CMS\Backend\Command;

use Doctrine\DBAL\ParameterType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderPropertyManager;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Unlocks the MFA providers of a backend user, which got locked by too many failed attempts.
 * In contrast to deactivating, the configured providers and their secrets are kept.
 */
#[AsCommand('backend:user:mfa:unlock', 'Unlocks the locked MFA providers of a backend user.')]
#[AsNonSchedulableCommand]
class UnlockBackendUserMfaCommand extends Command
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly MfaProviderRegistry $mfaProviderRegistry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('user', InputArgument::REQUIRED, 'The uid, username or email address of the backend user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $identifier = (string)$input->getArgument('user');

        $rows = $this->findUsers($identifier);
        if (count($rows) !== 1) {
            $io->error(
                $rows === []
                    ? 'No backend user found for "' . $identifier . '".'
                    : 'Multiple backend users found for "' . $identifier . '", please use the uid.'
            );
            return Command::FAILURE;
        }

        $user = $this->createUser($rows[0]);
        $lockedProviders = $this->mfaProviderRegistry->getLockedProviders($user);
        if ($lockedProviders === []) {
            $io->note('Backend user "' . $user->getUserName() . '" has no locked MFA provider.');
            return Command::SUCCESS;
        }

        $request = new ServerRequest();
        $failed = false;
        foreach ($lockedProviders as $identifier => $provider) {
            if ($provider->unlock($request, MfaProviderPropertyManager::create($provider, $user))) {
                $io->writeln('Unlocked MFA provider "' . $identifier . '".');
            } else {
                $io->warning('Could not unlock MFA provider "' . $identifier . '".');
                $failed = true;
            }
        }
        if ($failed) {
            return Command::FAILURE;
        }
        $io->success('Unlocked MFA providers of backend user "' . $user->getUserName() . '".');
        return Command::SUCCESS;
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if (!$input->mustSuggestArgumentValuesFor('user')) {
            return;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $rows = $queryBuilder
            ->select('*')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->neq('mfa', $queryBuilder->createNamedParameter('')),
            )
            ->orderBy('username')
            ->executeQuery()
            ->fetchAllAssociative();
        foreach ($rows as $row) {
            if ($this->mfaProviderRegistry->hasLockedProviders($this->createUser($row))) {
                $suggestions->suggestValue((string)$row['username']);
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function findUsers(string $identifier): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $identifierParameter = $queryBuilder->createNamedParameter($identifier);
        $conditions = [
            $queryBuilder->expr()->eq('username', $identifierParameter),
            $queryBuilder->expr()->eq('email', $identifierParameter),
        ];
        if (ctype_digit($identifier)) {
            $conditions[] = $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter((int)$identifier, ParameterType::INTEGER));
        }
        return array_values(
            $queryBuilder
                ->select('*')
                ->from('be_users')
                ->where(
                    $queryBuilder->expr()->or(...$conditions),
                )
                ->executeQuery()
                ->fetchAllAssociative()
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function createUser(array $row): BackendUserAuthentication
    {
        $user = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $user->user = $row;
        return $user;
    }
}
