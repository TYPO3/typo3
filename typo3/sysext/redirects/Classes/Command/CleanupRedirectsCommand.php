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
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Redirects\Repository\Demand;
use TYPO3\CMS\Redirects\Repository\RedirectRepository;

#[AsCommand('redirects:cleanup', 'Cleanup old redirects periodically for given constraints like days, hit count or domains.')]
class CleanupRedirectsCommand extends Command
{
    protected LanguageService $languageService;

    public function __construct(
        protected readonly RedirectRepository $redirectRepository,
        protected readonly LanguageServiceFactory $languageServiceFactory
    ) {
        $this->languageService = $languageServiceFactory->create('default');
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'domain',
                'd',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang.xlf:cleanupRedirectsCommand.label.domain'),
                null,
                function (): array {
                    return array_column($this->redirectRepository->findHostsOfRedirects(), 'name');
                }
            )
            ->addOption(
                'statusCode',
                's',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang.xlf:cleanupRedirectsCommand.label.statusCode'),
                null,
                function (): array {
                    return array_column($this->redirectRepository->findStatusCodesOfRedirects(), 'code');
                }
            )
            ->addOption(
                'days',
                'a',
                InputOption::VALUE_OPTIONAL,
                $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang.xlf:cleanupRedirectsCommand.label.days'),
                null
            )
            ->addOption(
                'hitCount',
                'c',
                InputOption::VALUE_OPTIONAL,
                $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang.xlf:cleanupRedirectsCommand.label.hitCount'),
                null
            )
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_OPTIONAL,
                $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang.xlf:cleanupRedirectsCommand.label.path'),
                null
            )
            ->addOption(
                'creationType',
                't',
                InputOption::VALUE_OPTIONAL,
                $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang.xlf:cleanupRedirectsCommand.label.creationType'),
                null,
                function (): array {
                    return array_keys($this->redirectRepository->findCreationTypes());
                }
            )
            ->addOption(
                'integrityStatus',
                'i',
                InputOption::VALUE_OPTIONAL,
                $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang.xlf:cleanupRedirectsCommand.label.integrityStatus'),
                null,
                function (): array {
                    return array_keys($this->redirectRepository->findIntegrityStatusCodes());
                }
            )
            ->addOption(
                'redirectType',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang.xlf:cleanupRedirectsCommand.label.redirectType'),
                Demand::DEFAULT_REDIRECT_TYPE,
                function (): array {
                    return array_keys($this->redirectRepository->findRedirectTypes());
                }
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->redirectRepository->removeByDemand(Demand::fromCommandInput($input));
        return Command::SUCCESS;
    }
}
