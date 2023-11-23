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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\Form\FormDataGroupInterface;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Calls provider with dependencies specified given by setter
 *
 * This group is used to call a list of providers in order by specified
 * dependencies before/depends.
 */
#[Autoconfigure(public: true, shared: false)]
class OrderedProviderList implements FormDataGroupInterface
{
    /**
     * @var FormDataProviderInterface[]
     */
    protected array $providerList = [];

    public function __construct(
        #[Autowire(service: 'cache.runtime')]
        private readonly FrontendInterface $runtimeCache,
        private readonly DependencyOrderingService $dependencyOrderingService,
    ) {}

    /**
     * @param array $result Initialized result array
     * @return array Result filled with data
     * @todo: compile() should receive $list from setProviderList() as argument to make this service
     *        stateless and shared. It would be even better if the "static" lists from TYPO3_CONF_VARS
     *        like 'tcaDatabaseRecord' could be provided as already ordered compile time service provider:
     *        This would allow low lever caching and would avoid calls to DependencyOrderingService at runtime.
     */
    public function compile(array $result): array
    {
        $cacheIdentifier = 'FormEngine-OrderedProviderList-' . hash('xxh3', json_encode($this->providerList));
        $orderedList = $this->runtimeCache->get($cacheIdentifier);
        if (!is_array($orderedList)) {
            $orderedList = $this->dependencyOrderingService->orderByDependencies($this->providerList, 'before', 'depends');
            $this->runtimeCache->set($cacheIdentifier, $orderedList);
        }
        foreach ($orderedList as $providerClassName => $providerConfig) {
            if (isset($providerConfig['disabled']) && $providerConfig['disabled'] === true) {
                // Skip this data provider if disabled by configuration
                continue;
            }
            if (!class_exists($providerClassName)) {
                throw new \InvalidArgumentException('Implementation class for data provider ' . $providerClassName . ' does not exist', 1685542507);
            }
            /** @var FormDataProviderInterface $provider */
            $provider = GeneralUtility::makeInstance($providerClassName);
            if (!$provider instanceof FormDataProviderInterface) {
                throw new \UnexpectedValueException('Data provider ' . $providerClassName . ' must implement FormDataProviderInterface', 1485299408);
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
     */
    public function setProviderList(array $list): void
    {
        $this->providerList = $list;
    }
}
