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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Configuration\RedirectCleanupConfiguration;
use TYPO3\CMS\Redirects\Service\RedirectService;

class CleanupRedirectsCommand extends Command
{
    /**
     * @var RedirectService
     */
    protected $redirectService;

    /**
     * @var LanguageService
     */
    protected $languageService;

    public function __construct(RedirectService $redirectService, LanguageService $languageService)
    {
        $this->redirectService = $redirectService;
        $this->languageService = $languageService;
        parent::__construct(null);
    }

    protected function configure(): void
    {
        $this
            ->setDescription($this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang.xlf:cleanupRedirectsTask.description'))
            ->addOption(
                'domains',
                'd',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang.xlf:cleanupRedirectsTask.label.domains'),
                []
            )
            ->addOption(
                'statusCodes',
                's',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang.xlf:cleanupRedirectsTask.label.statusCodes'),
                []
            )
            ->addOption(
                'age',
                'a',
                InputOption::VALUE_OPTIONAL,
                $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang.xlf:cleanupRedirectsTask.label.days'),
                90
            )
            ->addOption(
                'hitCount',
                'c',
                InputOption::VALUE_OPTIONAL,
                $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang.xlf:cleanupRedirectsTask.label.hitCount'),
                null
            )
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_OPTIONAL,
                $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang.xlf:cleanupRedirectsTask.label.path'),
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $redirectCleanupConfiguration = GeneralUtility::makeInstance(RedirectCleanupConfiguration::class);
        $redirectCleanupConfiguration
            ->setDomains($input->getOption('domains'))
            ->setStatusCodes($input->getOption('statusCodes'))
            ->setDays((int)$input->getOption('age'))
            ->setPath($input->getOption('path'));
        $hitCount = $input->getOption('hitCount');
        if ($hitCount !== null) {
            $redirectCleanupConfiguration->setHitCount((int)$hitCount);
        }
        $this->redirectService->cleanupRedirectsByConfiguration($redirectCleanupConfiguration);
        return 0;
    }
}
