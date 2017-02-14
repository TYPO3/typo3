<?php
namespace TYPO3\CMS\Extbase\Mvc\View;

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
 * The not found view - a special case.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NotFoundView extends \TYPO3\CMS\Extbase\Mvc\View\AbstractView
{
    /**
     * @var array
     */
    protected $variablesMarker = ['errorMessage' => 'ERROR_MESSAGE'];

    /**
     * Renders the not found view
     *
     * @return string The rendered view
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception if no request has been set
     * @api
     */
    public function render()
    {
        if (!is_object($this->controllerContext->getRequest())) {
            throw new \TYPO3\CMS\Extbase\Mvc\Exception('Can\'t render view without request object.', 1192450280);
        }
        $template = file_get_contents($this->getTemplatePathAndFilename());
        if ($this->controllerContext->getRequest() instanceof \TYPO3\CMS\Extbase\Mvc\Web\Request) {
            $template = str_replace('###BASEURI###', \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), $template);
        }
        foreach ($this->variablesMarker as $variableName => $marker) {
            $variableValue = isset($this->variables[$variableName]) ? $this->variables[$variableName] : '';
            $template = str_replace('###' . $marker . '###', $variableValue, $template);
        }
        return $template;
    }

    /**
     * Retrieves path and filename of the not-found-template
     *
     * @return string path and filename of the not-found-template
     */
    protected function getTemplatePathAndFilename()
    {
        return \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('extbase') . 'Resources/Private/MVC/NotFoundView_Template.html';
    }

    /**
     * A magic call method.
     *
     * Because this not found view is used as a Special Case in situations when no matching
     * view is available, it must be able to handle method calls which originally were
     * directed to another type of view. This magic method should prevent PHP from issuing
     * a fatal error.
     *
     * @param string $methodName
     * @param array $arguments
     */
    public function __call($methodName, array $arguments)
    {
    }
}
