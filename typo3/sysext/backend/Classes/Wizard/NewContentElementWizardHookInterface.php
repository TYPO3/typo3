<?php

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

namespace TYPO3\CMS\Backend\Wizard;

/**
 * Interface for classes which hook into \TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController
 * and manipulate wizardItems array
 *
 * @deprecated not in use anymore since TYPO3 v12, will be removed in TYPO3 13. Only stays to allow extensions to be compatible with TYPO3 v11+v12
 */
interface NewContentElementWizardHookInterface
{
    /**
     * Modifies WizardItems array
     *
     * @param array $wizardItems Array of Wizard Items
     * @param \TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController $parentObject Parent object New Content element wizard
     */
    public function manipulateWizardItems(&$wizardItems, &$parentObject);
}
