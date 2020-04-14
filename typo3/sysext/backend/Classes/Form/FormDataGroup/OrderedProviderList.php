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

namespace TYPO3\CMS\Backend\Form\FormDataGroup;

use TYPO3\CMS\Backend\Form\FormDataGroupInterface;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Calls provider with dependencies specified given by setter
 *
 * This group is used to call a list of providers in order by specified
 * dependencies before/depends.
 */
class OrderedProviderList implements FormDataGroupInterface
{
    /**
     * @var FormDataProviderInterface[]
     */
    protected $providerList = [];

    /**
     * Compile form data
     *
     * @param array $result Initialized result array
     * @return array Result filled with data
     * @throws \UnexpectedValueException
     */
    public function compile(array $result): array
    {
        $orderingService = GeneralUtility::makeInstance(DependencyOrderingService::class);
        $orderedDataProvider = $orderingService->orderByDependencies($this->providerList, 'before', 'depends');

        foreach ($orderedDataProvider as $providerClassName => $providerConfig) {
            if (isset($providerConfig['disabled']) && $providerConfig['disabled'] === true) {
                // Skip this data provider if disabled by configuration
                continue;
            }

            /** @var FormDataProviderInterface $provider */
            $provider = GeneralUtility::makeInstance($providerClassName);

            if (!$provider instanceof FormDataProviderInterface) {
                throw new \UnexpectedValueException(
                    'Data provider ' . $providerClassName . ' must implement FormDataProviderInterface',
                    1485299408
                );
            }

            $result = $provider->addData($result);
        }

        return $result;
    }

    /**
     * Set list of providers to be called
     *
     * The dependencies of a provider are specified as:
     *
     *   FormDataProvider::class => [
     *      'before' => [AnotherFormDataProvider::class]
     *      'depends' => [YetAnotherFormDataProvider::class]
     *   ]
     *
     * @param array $list Given list of Provider class names
     * @see \TYPO3\CMS\Core\Service\DependencyOrderingService
     */
    public function setProviderList(array $list)
    {
        $this->providerList = $list;
    }
}
