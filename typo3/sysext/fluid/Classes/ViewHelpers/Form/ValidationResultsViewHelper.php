<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Form;

/*                                                                        *
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
 * Validation results view helper
 *
 * = Examples =
 *
 * <code title="Output error messages as a list">
 * <f:form.validationResults>
 *   <f:if condition="{validationResults.flattenedErrors}">
 *     <ul class="errors">
 *       <f:for each="{validationResults.flattenedErrors}" as="errors" key="propertyPath">
 *         <li>{propertyPath}
 *           <ul>
 *           <f:for each="{errors}" as="error">
 *             <li>{error.code}: {error}</li>
 *           </f:for>
 *           </ul>
 *         </li>
 *       </f:for>
 *     </ul>
 *   </f:if>
 * </f:form.validationResults>
 * </code>
 * <output>
 * <ul class="errors">
 *   <li>1234567890: Validation errors for argument "newBlog"</li>
 * </ul>
 * </output>
 *
 * <code title="Output error messages for a single property">
 * <f:form.validationResults for="someProperty">
 *   <f:if condition="{validationResults.flattenedErrors}">
 *     <ul class="errors">
 *       <f:for each="{validationResults.errors}" as="error">
 *         <li>{error.code}: {error}</li>
 *       </f:for>
 *     </ul>
 *   </f:if>
 * </f:form.validationResults>
 * </code>
 * <output>
 * <ul class="errors">
 *   <li>1234567890: Some error message</li>
 * </ul>
 * </output>
 *
 * @api
 */
class ValidationResultsViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * Iterates through selected errors of the request.
     *
     * @param string $for The name of the error name (e.g. argument name or property name). This can also be a property path (like blog.title), and will then only display the validation errors of that property.
     * @param string $as The name of the variable to store the current error
     * @return string Rendered string
     * @api
     */
    public function render($for = '', $as = 'validationResults')
    {
        $validationResults = $this->controllerContext->getRequest()->getOriginalRequestMappingResults();
        if ($validationResults !== null && $for !== '') {
            $validationResults = $validationResults->forProperty($for);
        }
        $this->templateVariableContainer->add($as, $validationResults);
        $output = $this->renderChildren();
        $this->templateVariableContainer->remove($as);
        return $output;
    }
}
