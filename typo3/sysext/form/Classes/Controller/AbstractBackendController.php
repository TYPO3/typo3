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

namespace TYPO3\CMS\Form\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;

/**
 * The abstract form backend controller
 *
 * Scope: backend
 * @internal
 */
abstract class AbstractBackendController extends ActionController
{

    /**
     * @var array
     */
    protected $formSettings;

    /**
     * @var \TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface
     */
    protected $formPersistenceManager;

    /**
     * @param \TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface $formPersistenceManager
     * @internal
     */
    public function injectFormPersistenceManager(FormPersistenceManagerInterface $formPersistenceManager)
    {
        $this->formPersistenceManager = $formPersistenceManager;
    }

    /**
     * @internal
     */
    public function initializeObject()
    {
        $this->formSettings = GeneralUtility::makeInstance(ConfigurationManagerInterface::class)
            ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_YAML_SETTINGS, 'form');
    }

    /**
     * Convert arrays with EXT: resource paths to web paths
     *
     * Input:
     * [
     *   100 => 'EXT:form/Resources/Public/Css/form.css'
     * ]
     *
     * Output:
     *
     * [
     *   0 => 'typo3/sysext/form/Resources/Public/Css/form.css'
     * ]
     *
     * @param array $resourcePaths
     * @return array
     */
    protected function resolveResourcePaths(array $resourcePaths): array
    {
        $return = [];
        foreach ($resourcePaths as $resourcePath) {
            $resourcePath = PathUtility::getPublicResourceWebPath($resourcePath);
            if (empty($resourcePath)) {
                continue;
            }
            $return[] = $resourcePath;
        }

        return $return;
    }
}
