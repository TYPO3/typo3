<?php
namespace TYPO3\CMS\Install\Service;

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
 * Service for determining the current context (as a backend module or in standalone mode)
 */
class ContextService
{
    /**
     * @var bool
     */
    private $backendContext = false;

    /**
     * Constructor, prepare the context information
     */
    public function __construct()
    {
        $formValues = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('install');
        if (isset($formValues['context'])) {
            $this->backendContext = ($formValues['context'] === 'backend');
        }
    }

    /**
     * Is the install tool running in the backend?
     *
     * @return bool
     */
    public function isBackendContext()
    {
        return $this->backendContext;
    }

    /**
     * Is the install tool running as a standalone application?
     *
     * @return bool
     */
    public function isStandaloneContext()
    {
        return !$this->backendContext;
    }

    /**
     * Is the install tool running as a standalone application?
     *
     * @return bool
     */
    public function getContextString()
    {
        return $this->isBackendContext() ? 'backend' : 'standalone';
    }
}
