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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Redirects\Repository\Demand;
use TYPO3\CMS\Redirects\Repository\RedirectRepository;

class CleanupRedirectsCommand extends Command
{
    /**
     * @var RedirectRepository
     */
    protected $redirectRepository;

    /**
     * @var LanguageService
     */
    protected $languageService;

    public function __construct(RedirectRepository $redirectRepository, LanguageServiceFactory $languageServiceFactory)
    {
        $this->redirectRepository = $redirectRepository;
        $this->languageService = $languageServiceFactory->create('default');
        parent::__construct(null);
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'domain',
                'd',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang.xlf:cleanupRedirectsCommand.label.domain'),
                null
            )
            ->addOption(
                'statusCode',
                's',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang.xlf:cleanupRedirectsCommand.label.statusCode'),
                null
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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->redirectRepository->removeByDemand(Demand::fromCommandInput($input));
        return 0;
    }
}
