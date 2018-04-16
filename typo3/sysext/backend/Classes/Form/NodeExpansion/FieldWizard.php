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
 * Field wizards are additional HTML on a single element level that are typically
 * shown below the element input. They are registered in ['config']['fieldWizard']
 * TCA section and each element may merge that with default wizards.
 *
 * Field wizards may add additional functionality to the element. They could add
 * new ajax controllers for instance or add buttons and are not restricted by the
 * framework further.
 *
 * Examples for field wizards are the display of the "languaged diff" in input elements
 * and the file upload button in group elements.
 */
class FieldWizard extends AbstractNode
{
    /**
     * Order the list of field wizards to be rendered with the ordering service,
     * then call each wizard element through the node factory and merge their
     * results.
     *
     * @return array Result array
     */
    public function render(): array
    {
        $result = $this->initializeResultArray();
        if (!isset($this->data['renderData']['fieldWizard'])) {
            return $result;
        }

        $fieldWizard = $this->data['renderData']['fieldWizard'];
        $orderingService = GeneralUtility::makeInstance(DependencyOrderingService::class);
        $orderedFieldWizard = $orderingService->orderByDependencies($fieldWizard, 'before', 'after');

        foreach ($orderedFieldWizard as $anOrderedFieldWizard => $orderedFieldWizardConfiguration) {
            if (isset($orderedFieldWizardConfiguration['disabled']) && $orderedFieldWizardConfiguration['disabled']
                || !isset($fieldWizard[$anOrderedFieldWizard]['renderType'])
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
            $options['renderType'] = $fieldWizard[$anOrderedFieldWizard]['renderType'];
            $options['renderData']['fieldWizardOptions'] = $orderedFieldWizardConfiguration['options'] ?? [];
            $wizardResult = $this->nodeFactory->create($options)->render();
            $result = $this->mergeChildReturnIntoExistingResult($result, $wizardResult);
        }
        return $result;
    }
}
