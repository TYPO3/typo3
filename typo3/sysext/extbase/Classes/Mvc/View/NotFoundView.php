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

namespace TYPO3\CMS\Extbase\Mvc\View;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Exception;
use TYPO3\CMS\Extbase\Mvc\Request;

/**
 * The not found view - a special case.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class NotFoundView implements ViewInterface
{
    /**
     * @var array
     */
    protected $variablesMarker = ['errorMessage' => 'ERROR_MESSAGE'];

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * View variables and their values
     *
     * @var array
     * @see assign()
     */
    protected $variables = [];

    /**
     * Renders the not found view
     *
     * @return string The rendered view
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception if no request has been set
     */
    public function render()
    {
        if (!is_object($this->controllerContext->getRequest())) {
            throw new Exception('Can\'t render view without request object.', 1192450280);
        }
        $template = file_get_contents($this->getTemplatePathAndFilename());
        $template = is_string($template) ? $template : '';
        if ($this->controllerContext->getRequest() instanceof Request) {
            $template = str_replace('###BASEURI###', $this->controllerContext->getRequest()->getAttribute('normalizedParams')->getSiteUrl(), $template);
        }
        foreach ($this->variablesMarker as $variableName => $marker) {
            $variableValue = $this->variables[$variableName] ?? '';
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
        return ExtensionManagementUtility::extPath('extbase') . 'Resources/Private/MVC/NotFoundView_Template.html';
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

    /**
     * Sets the current controller context
     *
     * @param ControllerContext $controllerContext
     */
    public function setControllerContext(ControllerContext $controllerContext)
    {
        $this->controllerContext = $controllerContext;
    }

    /**
     * Add a variable to $this->viewData.
     * Can be chained, so $this->view->assign(..., ...)->assign(..., ...); is possible
     *
     * @param string $key Key of variable
     * @param mixed $value Value of object
     * @return NotFoundView an instance of $this, to enable chaining
     */
    public function assign($key, $value)
    {
        $this->variables[$key] = $value;
        return $this;
    }

    /**
     * Add multiple variables to $this->viewData.
     *
     * @param array $values array in the format array(key1 => value1, key2 => value2).
     * @return NotFoundView an instance of $this, to enable chaining
     */
    public function assignMultiple(array $values)
    {
        foreach ($values as $key => $value) {
            $this->assign($key, $value);
        }
        return $this;
    }

    /**
     * Tells if the view implementation can render the view for the given context.
     *
     * By default we assume that the view implementation can handle all kinds of
     * contexts. Override this method if that is not the case.
     *
     * @param ControllerContext $controllerContext
     * @return bool TRUE if the view has something useful to display, otherwise FALSE
     */
    public function canRender(ControllerContext $controllerContext)
    {
        return true;
    }

    /**
     * Initializes this view.
     *
     * Override this method for initializing your concrete view implementation.
     */
    public function initializeView()
    {
    }
}
