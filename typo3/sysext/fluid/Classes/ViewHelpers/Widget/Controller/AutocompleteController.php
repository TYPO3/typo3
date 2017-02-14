<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller;

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
 * Class AutocompleteController
 */
class AutocompleteController extends \TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController
{
    /**
     * Simply assigns the ID of the widget.
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
