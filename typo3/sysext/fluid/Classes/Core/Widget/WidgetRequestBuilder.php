<?php
namespace TYPO3\CMS\Fluid\Core\Widget;

/*
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
/**
 * Builds the WidgetRequest if an AJAX widget is called.
 */
class WidgetRequestBuilder extends \TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder
{
    /**
     * @var \TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder
     */
    private $ajaxWidgetContextHolder;

    /**
     * @param \TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder $ajaxWidgetContextHolder
     * @return void
     */
    public function injectAjaxWidgetContextHolder(\TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder $ajaxWidgetContextHolder)
    {
        $this->ajaxWidgetContextHolder = $ajaxWidgetContextHolder;
    }

    /**
     * Builds a widget request object from the raw HTTP information
     *
     * @return \TYPO3\CMS\Fluid\Core\Widget\WidgetRequest The widget request as an object
     */
    public function build()
    {
        $request = $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class);
        $request->setRequestURI(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
        $request->setBaseURI(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
        $request->setMethod(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null);
        if (strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $request->setArguments(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST());
        } else {
            $request->setArguments(\TYPO3\CMS\Core\Utility\GeneralUtility::_GET());
        }
        $rawGetArguments = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET();
        // @todo rename to @action, to be consistent with normal naming?
        if (isset($rawGetArguments['action'])) {
            $request->setControllerActionName($rawGetArguments['action']);
        }
        $widgetContext = $this->ajaxWidgetContextHolder->get($rawGetArguments['fluid-widget-id']);
        $request->setWidgetContext($widgetContext);
        return $request;
    }
}
