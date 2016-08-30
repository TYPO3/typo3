<?php
namespace TYPO3\CMS\Workspaces\ExtDirect;

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

/**
 * Abstract ExtDirect handler
 */
abstract class AbstractHandler
{
    /**
     * Gets the current workspace ID.
     *
     * @return int The current workspace ID
     */
    protected function getCurrentWorkspace()
    {
        return $this->getWorkspaceService()->getCurrentWorkspace();
    }

    /**
     * Gets an error response to be shown in the grid component.
     *
     * @param string $errorLabel Name of the label in the locallang.xlf file
     * @param int $errorCode The error code to be used
     * @param bool $successFlagValue Value of the success flag to be delivered back (might be FALSE in most cases)
     * @return array
     */
    protected function getErrorResponse($errorLabel, $errorCode = 0, $successFlagValue = false)
    {
        $localLangFile = 'LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf';
        $response = [
            'error' => [
                'code' => $errorCode,
                'message' => $GLOBALS['LANG']->sL($localLangFile . ':' . $errorLabel)
            ],
            'success' => $successFlagValue
        ];
        return $response;
    }

    /**
     * Gets an instance of the workspaces service.
     *
     * @return \TYPO3\CMS\Workspaces\Service\WorkspaceService
     */
    protected function getWorkspaceService()
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\WorkspaceService::class);
    }

    /**
     * Validates whether the submitted language parameter can be
     * interpreted as integer value.
     *
     * @param stdClass $parameters
     * @return int|NULL
     */
    protected function validateLanguageParameter(\stdClass $parameters)
    {
        $language = null;
        if (isset($parameters->language) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($parameters->language)) {
            $language = $parameters->language;
        }
        return $language;
    }

    /**
     * Gets affected elements on publishing/swapping actions.
     * Affected elements have a dependency, e.g. translation overlay
     * and the default origin record - thus, the default record would be
     * affected if the translation overlay shall be published.
     *
     * @param stdClass $parameters
     * @return array
     */
    protected function getAffectedElements(\stdClass $parameters)
    {
        $affectedElements = [];
        if ($parameters->type === 'selection') {
            foreach ((array)$parameters->selection as $element) {
                $affectedElements[] = \TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord::create($element->table, $element->liveId, $element->versionId);
            }
        } elseif ($parameters->type === 'all') {
            $versions = $this->getWorkspaceService()->selectVersionsInWorkspace($this->getCurrentWorkspace(), 0, -99, -1, 0, 'tables_select', $this->validateLanguageParameter($parameters));
            foreach ($versions as $table => $tableElements) {
                foreach ($tableElements as $element) {
                    $affectedElement = \TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord::create($table, $element['t3ver_oid'], $element['uid']);
                    $affectedElement->getVersionRecord()->setRow($element);
                    $affectedElements[] = $affectedElement;
                }
            }
        }
        return $affectedElements;
    }

    /**
     * Creates a new instance of the integrity service for the
     * given set of affected elements.
     *
     * @param \TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord[] $affectedElements
     * @return \TYPO3\CMS\Workspaces\Service\IntegrityService
     * @see getAffectedElements
     */
    protected function createIntegrityService(array $affectedElements)
    {
        /** @var $integrityService \TYPO3\CMS\Workspaces\Service\IntegrityService */
        $integrityService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\IntegrityService::class);
        $integrityService->setAffectedElements($affectedElements);
        return $integrityService;
    }
}
