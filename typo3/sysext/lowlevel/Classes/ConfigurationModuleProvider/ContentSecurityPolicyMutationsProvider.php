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

namespace TYPO3\CMS\Lowlevel\ConfigurationModuleProvider;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ModelService;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationOrigin;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationRepository;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Policy;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceInterface;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceValueInterface;
use TYPO3\CMS\Core\Type\Map;
use TYPO3\CMS\Core\Utility\ArrayUtility;

class ContentSecurityPolicyMutationsProvider extends AbstractProvider
{
    public function __construct(
        protected readonly ModelService $modelService,
        protected readonly MutationRepository $mutationRepository
    ) {}

    public function getConfiguration(): array
    {
        $nonce = new ConsumableNonce();
        $data = [];
        /**
         * @var Scope $scope
         * @var Map<MutationOrigin, MutationCollection> $scopeDetails
         */
        foreach ($this->mutationRepository->findAll() as $scope => $scopeDetails) {
            $policy = new Policy();
            $scopeValue = (string)$scope;
            $data[$scopeValue] = [];
            /**
             * @var MutationOrigin $mutationOrigin
             * @var MutationCollection $mutationCollection
             */
            foreach ($scopeDetails as $mutationOrigin => $mutationCollection) {
                $policy->mutate($mutationCollection);
                $mutationOriginValue = sprintf(
                    "%s '%s'",
                    $mutationOrigin->type->value,
                    $mutationOrigin->value
                );
                foreach ($mutationCollection->mutations as $mutation) {
                    $sourceValues = array_map(
                        // like `ModelService::compileSources()`, but for a single item & without a nonce
                        fn(SourceInterface $source) => $source instanceof SourceValueInterface
                            ? $source->compile()
                            : $this->modelService->serializeSource($source),
                        $mutation->sources
                    );
                    $data[$scopeValue][$mutationOriginValue][] = sprintf(
                        '%s: %s %s',
                        $mutation->mode->value,
                        $mutation->directive->value,
                        implode(' ', $sourceValues)
                    );
                }
            }
            $data[$scopeValue] = ['@policy' => $policy->compile($nonce), ...$data[$scopeValue]];
        }
        ArrayUtility::naturalKeySortRecursive($data);
        return $data;
    }
}
