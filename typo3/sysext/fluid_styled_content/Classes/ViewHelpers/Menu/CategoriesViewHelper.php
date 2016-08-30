<?php
namespace TYPO3\CMS\FluidStyledContent\ViewHelpers\Menu;

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

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Exception;
use TYPO3\CMS\Frontend\Category\Collection\CategoryCollection;

/**
 * A view helper which returns records with assigned categories
 *
 * = Example =
 *
 * <code title="Pages with categories 1 and 2 assigned">
 * <ce:menu.categories categoryUids="{0: 1, 1: 2}" as="pages" relationField="categories" table="pages">
 *   <f:for each="{pages}" as="page">
 *     {page.title}
 *   </f:for>
 * </ce:menu.categories>
 * </code>
 *
 * <output>
 * Page with category 1 assigned
 * Page with category 1 and 2 assigned
 * </output>
 */
class CategoriesViewHelper extends AbstractViewHelper
{
    use MenuViewHelperTrait;

    /**
     * Initialize ViewHelper arguments
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('categoryUids', 'array', 'The categories assigned', true);
        $this->registerArgument('as', 'string', 'Name of the template variable that will contain resolved pages', true);
        $this->registerArgument('relationField', 'string', 'The category field for MM relation table', true);
        $this->registerArgument('table', 'string', 'The table to which categories are assigned (source table)', true);
    }

    /**
     * Render the view helper
     *
     * @return string
     */
    public function render()
    {
        $categoryUids = (array)$this->arguments['categoryUids'];
        $as = (string)$this->arguments['as'];
        if (empty($categoryUids)) {
            return '';
        }

        return $this->renderChildrenWithVariables([
            $as => $this->findByCategories($categoryUids, $this->arguments['relationField'], $this->arguments['table'])
        ]);
    }

    /**
     * Find records from a certain table which have categories assigned
     *
     * @param array $categoryUids The uids of the categories
     * @param string $relationField Field relation in MM table
     * @param string $tableName Name of the table to search in
     * @return array
     * @throws Exception
     */
    protected function findByCategories($categoryUids, $relationField, $tableName = 'pages')
    {
        $result = [];

        foreach ($categoryUids as $categoryUid) {
            try {
                $collection = CategoryCollection::load(
                    $categoryUid,
                    true,
                    $tableName,
                    $relationField
                );
                if ($collection->count() > 0) {
                    foreach ($collection as $record) {
                        $result[$record['uid']] = $record;
                    }
                }
            } catch (\RuntimeException $e) {
                throw new Exception($e->getMessage());
            }
        }

        return $result;
    }
}
