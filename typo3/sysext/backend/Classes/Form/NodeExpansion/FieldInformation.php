<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Form\NodeExpansion;

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

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Field information are additional HTML on a single node level that are typically
 * shown between the label and the main element. They are registered in ['config']['fieldInformation']
 * TCA section and each element may merge that with default registered information.
 *
 * Field information must not add additional functionality to the element. They are only
 * allowed to add "informational" stuff like links, div and spans and similar.
 */
class FieldInformation extends AbstractNode
{
    /**
     * Order the list of field information to be rendered with the ordering service,
     * then call each information element through the node factory and merge their
     * results.
     *
     * @return array Result array
     */
    public function render(): array
    {
        $result = $this->initializeResultArray();
        if (!isset($this->data['renderData']['fieldInformation'])) {
            return $result;
        }

        $fieldInformations = $this->data['renderData']['fieldInformation'];
        $orderingService = GeneralUtility::makeInstance(DependencyOrderingService::class);
        $orderedFieldInformation = $orderingService->orderByDependencies($fieldInformations, 'before', 'after');

        foreach ($orderedFieldInformation as $anOrderedFieldInformation => $orderedFieldInformationConfiguration) {
            if (isset($orderedFieldInformationConfiguration['disabled']) && $orderedFieldInformationConfiguration['disabled']
                || !isset($fieldInformations[$anOrderedFieldInformation]['renderType'])
            ) {
                // Don't consider this control if disabled.
                // Also ignore if renderType is not given.
                // Missing renderType may happen if an element registers a default field control
                // as disabled, and TCA enabled that. If then additionally for instance the
                // element renderType is changed to an element that doesn't register the control
                // by default anymore, this would then fatal if we don't continue here.
                // @todo: the above scenario indicates a small configuration flaw, maybe log an error somewhere?
                continue;
            }

            $options = $this->data;
            $options['renderType'] = $fieldInformations[$anOrderedFieldInformation]['renderType'];
            $options['renderData']['fieldInformationOptions'] = $orderedFieldInformationConfiguration['options'] ?? [];
            $informationResult = $this->nodeFactory->create($options)->render();

            $allowedTags = '<a><br><br/><div><em><i><p><strong><span><code>';
            if (strip_tags($informationResult['html'], $allowedTags) !== $informationResult['html']) {
                throw new \RuntimeException(
                    'The field information API supports only a limited number of HTML tags within the result'
                    . ' HTML of children. Allowed tags are: "' . $allowedTags . '" Child'
                    . ' ' . $options['renderType'] . ' violated this rule. Either remove offending tags or'
                    . ' switch to a different API like "fieldWizard" or "fieldControl".',
                    1485084419
                );
            }

            $result = $this->mergeChildReturnIntoExistingResult($result, $informationResult);
        }
        return $result;
    }
}
