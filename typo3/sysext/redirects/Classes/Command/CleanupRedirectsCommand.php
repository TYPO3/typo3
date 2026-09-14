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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Redirects\Repository\Demand;
use TYPO3\CMS\Redirects\Repository\RedirectRepository;

#[AsCommand('redirects:cleanup', 'Periodically cleans up old redirects for constraints such as days, hit count or domains.')]
class CleanupRedirectsCommand extends Command
{
    protected LanguageService $languageService;

    public function __construct(
        protected readonly RedirectRepository $redirectRepository,
        protected readonly LanguageServiceFactory $languageServiceFactory
    ) {
        $this->languageService = $languageServiceFactory->create('en');
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'domain',
                'd',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->translate('cleanupRedirectsCommand.label.domain'),
                null,
                function (): array {
                    return array_column($this->redirectRepository->findHostsOfRedirects(), 'name');
                }
            )
            ->addOption(
                'statusCode',
                's',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->translate('cleanupRedirectsCommand.label.statusCode'),
                null,
                function (): array {
                    return array_column($this->redirectRepository->findStatusCodesOfRedirects(), 'code');
                }
            )
            ->addOption(
                'days',
                'a',
                InputOption::VALUE_OPTIONAL,
                $this->translate('cleanupRedirectsCommand.label.days'),
                null
            )
            ->addOption(
                'hitCount',
                'c',
                InputOption::VALUE_OPTIONAL,
                $this->translate('cleanupRedirectsCommand.label.hitCount'),
                null
            )
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_OPTIONAL,
                $this->translate('cleanupRedirectsCommand.label.path'),
                null
            )
            ->addOption(
                'creationType',
                't',
                InputOption::VALUE_OPTIONAL,
                $this->translate('cleanupRedirectsCommand.label.creationType'),
                null,
                function (): array {
                    return array_keys($this->redirectRepository->findCreationTypes());
                }
            )
            ->addOption(
                'integrityStatus',
                'i',
                InputOption::VALUE_OPTIONAL,
                $this->translate('cleanupRedirectsCommand.label.integrityStatus'),
                null,
                function (): array {
                    return array_keys($this->redirectRepository->findIntegrityStatusCodes());
                }
            )
            ->addOption(
                'redirectType',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->translate('cleanupRedirectsCommand.label.redirectType'),
                Demand::DEFAULT_REDIRECT_TYPE,
                function (): array {
                    return array_keys($this->redirectRepository->findRedirectTypes());
                }
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                $this->translate('cleanupRedirectsCommand.label.dryRun')
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Bootstrap::initializeBackendAuthentication();

        $demand = Demand::fromCommandInput($input);

        if (!$input->getOption('dry-run')) {
            $this->redirectRepository->removeByDemand($demand);
            return Command::SUCCESS;
        }

        $io = new SymfonyStyle($input, $output);
        $redirects = $this->redirectRepository->findRedirectsToRemoveByDemand($demand);
        if ($redirects === []) {
            $io->note($this->translate('cleanupRedirectsCommand.dryRun.noMatches'));
            return Command::SUCCESS;
        }

        $io->table(
            [
                $this->translate('cleanupRedirectsCommand.dryRun.column.uid'),
                $this->translate('cleanupRedirectsCommand.dryRun.column.sourceHost'),
                $this->translate('cleanupRedirectsCommand.dryRun.column.sourcePath'),
                $this->translate('cleanupRedirectsCommand.dryRun.column.target'),
                $this->translate('cleanupRedirectsCommand.dryRun.column.statusCode'),
                $this->translate('cleanupRedirectsCommand.dryRun.column.hitCount'),
            ],
            array_map(
                static fn(array $redirect): array => [
                    $redirect['uid'],
                    $redirect['source_host'],
                    $redirect['source_path'],
                    $redirect['target'],
                    $redirect['target_statuscode'],
                    $redirect['hitcount'],
                ],
                $redirects
            )
        );
        $io->note($this->translate('cleanupRedirectsCommand.dryRun.summary', ['count' => count($redirects)]));

        return Command::SUCCESS;
    }

    private function translate(string $id, array $arguments = []): string
    {
        return (string)$this->languageService->translate($id, 'redirects.messages', $arguments);
    }
}
