<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\PageTitle;

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

use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class will take care of the different providers and returns the title with the highest priority
 */
class PageTitleProviderManager implements SingletonInterface
{
    /**
     * @var array
     */
    private $pageTitleCache = [];

    /**
     * @return string
     * @throws \TYPO3\CMS\Core\Cache\Exception
     * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException
     */
    public function getTitle(): string
    {
        $pageTitle = '';

        $titleProviders = $this->getPageTitleProviderConfiguration();
        $titleProviders = $this->setProviderOrder($titleProviders);

        $orderedTitleProviders = GeneralUtility::makeInstance(DependencyOrderingService::class)
            ->orderByDependencies($titleProviders);

        foreach ($orderedTitleProviders as $provider => $configuration) {
            if (class_exists($configuration['provider']) && is_subclass_of($configuration['provider'], PageTitleProviderInterface::class)) {
                /** @var PageTitleProviderInterface $titleProviderObject */
                $titleProviderObject = GeneralUtility::makeInstance($configuration['provider']);
                if (($pageTitle = $titleProviderObject->getTitle())
                    || ($pageTitle = $this->pageTitleCache[$configuration['provider']] ?? '') !== ''
                ) {
                    $this->pageTitleCache[$configuration['provider']] = $pageTitle;
                    break;
                }
            }
        }

        return $pageTitle;
    }

    /**
     * @return array
     * @internal
     */
    public function getPageTitleCache(): array
    {
        return $this->pageTitleCache;
    }

    /**
     * @param array $pageTitleCache
     * @internal
     */
    public function setPageTitleCache(array $pageTitleCache): void
    {
        $this->pageTitleCache = $pageTitleCache;
    }

    /**
     * Get the TypoScript configuration for pageTitleProviders
     * @return array
     */
    private function getPageTitleProviderConfiguration(): array
    {
        $typoscriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $config = $typoscriptService->convertTypoScriptArrayToPlainArray(
            $GLOBALS['TSFE']->config['config'] ?? []
        );

        return $config['pageTitleProviders'] ?? [];
    }

    /**
     * @param array $orderInformation
     * @return string[]
     * @throws \UnexpectedValueException
     */
    protected function setProviderOrder(array $orderInformation): array
    {
        foreach ($orderInformation as $provider => &$configuration) {
            if (isset($configuration['before'])) {
                if (is_string($configuration['before'])) {
                    $configuration['before'] = GeneralUtility::trimExplode(',', $configuration['before'], true);
                } elseif (!is_array($configuration['before'])) {
                    throw new \UnexpectedValueException(
                        'The specified "before" order configuration for provider "' . $provider . '" is invalid.',
                        1535803185
                    );
                }
            }
            if (isset($configuration['after'])) {
                if (is_string($configuration['after'])) {
                    $configuration['after'] = GeneralUtility::trimExplode(',', $configuration['after'], true);
                } elseif (!is_array($configuration['after'])) {
                    throw new \UnexpectedValueException(
                        'The specified "after" order configuration for provider "' . $provider . '" is invalid.',
                        1535803186
                    );
                }
            }
        }
        return $orderInformation;
    }
}
