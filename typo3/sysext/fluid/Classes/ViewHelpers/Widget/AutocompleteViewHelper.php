<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Widget;

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
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Simple autocomplete widget.
 *
 * .. note::
 *     Make sure to include jQuery and jQuery UI in the HTML, like that::
 *
 *         <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
 *         <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.4/jquery-ui.min.js"></script>
 *         <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.3/themes/base/jquery-ui.css" type="text/css" media="all" />
 *         <link rel="stylesheet" href="http://static.jquery.com/ui/css/demo-docs-theme/ui.theme.css" type="text/css" media="all" />
 *
 * You can include the provided TypoScript template that includes the above snippet to the pages headerData.
 *
 * Examples
 * ========
 *
 * Render lib object::
 *
 *    <input type="text" id="name" />
 *    <f:widget.autocomplete for="name" objects="{posts}" searchProperty="author">
 *
 * Output::
 *
 *    <input type="text" id="name" />
 *
 * The input field and the required JavaScript for the Ajax communication.
 * See Resources/Private/Templates/ViewHelpers/Widget/Autocomplete/Index.html
 */
class AutocompleteViewHelper extends \TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper
{
    /**
     * @var bool
     */
    protected $ajaxWidget = true;

    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\AutocompleteController
     */
    protected $controller;

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('objects', QueryResultInterface::class, 'Objects to auto-complete', true);
        $this->registerArgument('for', 'string', 'Property to fill', true);
        $this->registerArgument('searchProperty', 'string', 'Property to search within when filtering list', true);
    }

    /**
     * @param \TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\AutocompleteController $controller
     */
    public function injectAutocompleteController(\TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\AutocompleteController $controller)
    {
        $this->controller = $controller;
    }

    /**
     * @return string
     */
    public function render()
    {
        return $this->initiateSubRequest();
    }
}
