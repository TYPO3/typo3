<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller;

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
class AutocompleteController extends \TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController
{
    /**
     * @return void
     */
    public function indexAction()
    {
        $this->view->assign('id', $this->widgetConfiguration['for']);
    }

    /**
     * @param string $term
     * @return string
     */
    public function autocompleteAction($term)
    {
        $searchProperty = $this->widgetConfiguration['searchProperty'];
        $query = $this->widgetConfiguration['objects']->getQuery();
        $constraint = $query->getConstraint();
        if ($constraint !== null) {
            $query->matching($query->logicalAnd($constraint, $query->like($searchProperty, '%' . $term . '%', false)));
        } else {
            $query->matching($query->like($searchProperty, '%' . $term . '%', false));
        }
        $results = $query->execute();
        $output = [];
        foreach ($results as $singleResult) {
            $val = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($singleResult, $searchProperty);
            $output[] = [
                'id' => $val,
                'label' => $val,
                'value' => $val
            ];
        }
        return json_encode($output);
    }
}
