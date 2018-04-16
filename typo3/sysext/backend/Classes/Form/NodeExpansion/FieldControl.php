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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Field controls are additional HTML on a single element level that are typically
 * shown right aside the main element HTML.
 *
 * They are restricted to only allow an icon as output.
 *
 * The "link popup" button next to the input field of a renderType "inputLink"
 * is an example of such an additional control.
 *
 * The element itself must position any field controls at an appropriate place.
 * For instance the "group" element shows them in a row vertically, while others
 * display single controls next to each other.
 */
class FieldControl extends AbstractNode
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
        if (!isset($this->data['renderData']['fieldControl'])) {
            return $result;
        }

        $languageService = $this->getLanguageService();

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $fieldControl = $this->data['renderData']['fieldControl'];
        $orderingService = GeneralUtility::makeInstance(DependencyOrderingService::class);
        $orderedFieldControl = $orderingService->orderByDependencies($fieldControl, 'before', 'after');

        foreach ($orderedFieldControl as $anOrderedFieldControl => $orderedFieldControlConfiguration) {
            if (isset($orderedFieldControlConfiguration['disabled']) && $orderedFieldControlConfiguration['disabled']
                || !isset($fieldControl[$anOrderedFieldControl]['renderType'])
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
            $options['renderType'] = $fieldControl[$anOrderedFieldControl]['renderType'];
            $options['renderData']['fieldControlOptions'] = $orderedFieldControlConfiguration['options'] ?? [];
            $controlResult = $this->nodeFactory->create($options)->render();

            if (!is_array($controlResult)) {
                throw new \RuntimeException(
                    'Field controls must return an array',
                    1484838560
                );
            }

            // If the controlResult is empty (this control rendered nothing), continue to next one
            if (empty($controlResult)) {
                continue;
            }

            if (empty($controlResult['iconIdentifier'])) {
                throw new \RuntimeException(
                    'Field controls must return an iconIdentifier',
                    1483890332
                );
            }
            if (empty($controlResult['title'])) {
                throw new \RuntimeException(
                    'Field controls must return a title',
                    1483890482
                );
            }
            if (empty($controlResult['linkAttributes'])) {
                throw new \RuntimeException(
                    'Field controls must return link attributes',
                    1483891272
                );
            }

            $icon = $controlResult['iconIdentifier'];
            $title = $languageService->sL($controlResult['title']);
            $linkAttributes = $controlResult['linkAttributes'];
            if (!isset($linkAttributes['class'])) {
                $linkAttributes['class'] = 'btn btn-default';
            } else {
                $linkAttributes['class'] .= 'btn btn-default';
            }
            if (!isset($linkAttributes['href'])) {
                $linkAttributes['href'] = '#';
            }

            unset($controlResult['iconIdentifier']);
            unset($controlResult['title']);
            unset($controlResult['linkAttributes']);

            $html = [];
            $html[] = '<a ' . GeneralUtility::implodeAttributes($linkAttributes, true) . '>';
            $html[] =   '<span alt="' . htmlspecialchars($title) . '" title="' . htmlspecialchars($title) . '">';
            $html[] =       $iconFactory->getIcon($icon, Icon::SIZE_SMALL)->render();
            $html[] =   '</span>';
            $html[] = '</a>';

            $finalControlResult = $this->initializeResultArray();
            $finalControlResult = array_merge($finalControlResult, $controlResult);
            $finalControlResult['html'] = implode(LF, $html);

            $result = $this->mergeChildReturnIntoExistingResult($result, $finalControlResult);
        }
        return $result;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
