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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * A view helper which returns content elements with 'Show in Section Menus' enabled
 *
 * By default only content in colPos=0 will be found. This can be overruled by using "column"
 *
 * If you set property "type" to 'all', then the 'Show in Section Menus' checkbox is not considered
 * and all content elements are selected.
 *
 * If the property "type" is 'header' then only content elements with a visible header layout
 * (and a non-empty 'header' field!) are selected.
 * In other words, if the header layout of an element is set to 'Hidden' then the element will not be in the results.
 *
 * = Example =
 *
 * <code title="Content elements in page with uid = 1 and 'Show in Section Menu's' enabled">
 * <ce:menu.section pageUid="1" as="contentElements">
 *   <f:for each="{contentElements}" as="contentElement">
 *     {contentElement.header}
 *   </f:for>
 * </ce:menu.section>
 * </code>
 *
 * <output>
 * Content element 1 in page with uid = 1 and "Show in section menu's" enabled
 * Content element 2 in page with uid = 1 and "Show in section menu's" enabled
 * Content element 3 in page with uid = 1 and "Show in section menu's" enabled
 * </output>
 */
class SectionViewHelper extends AbstractViewHelper
{
    use MenuViewHelperTrait;

    /**
     * Initialize ViewHelper arguments
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('as', 'string', 'Name of the template variable that will contain selected pages', true);
        $this->registerArgument('column', 'string', 'Column numbers (colPos) from which to select content', false, '0');
        $this->registerArgument('pageUid', 'integer', 'UID of page containing section-objects; defaults to current page', false, null);
        $this->registerArgument('type', 'string', 'Search method when selecting indices from page', false, '');
    }

    /**
     * Render the view helper
     *
     * @return string
     */
    public function render()
    {
        $as = (string)$this->arguments['as'];
        $pageUid = (int)$this->arguments['pageUid'];
        $type = (string)$this->arguments['type'];

        if (empty($pageUid)) {
            $pageUid = $this->getTypoScriptFrontendController()->id;
        }

        if (!empty($type) && !in_array($type, ['all', 'header'], true)) {
            return '';
        }

        return $this->renderChildrenWithVariables([
            $as => $this->findBySection($pageUid, $type, $this->arguments['column'])
        ]);
    }

    /**
     * Find content with 'Show in Section Menus' enabled in a page
     *
     * By default only content in colPos=0 will be found. This can be overruled by using $column
     *
     * If you set property type to "all", then the 'Show in Section Menus' checkbox is not considered
     * and all content elements are selected.
     *
     * If the property $type is 'header' then only content elements with a visible header layout
     * (and a non-empty 'header' field!) is selected.
     * In other words, if the header layout of an element is set to 'Hidden' then the page will not appear in the menu.
     *
     * @param int $pageUid The page uid
     * @param string $type Search method
     * @param string $column Restrict content by the column number
     * @return array
     */
    protected function findBySection($pageUid, $type = '', $column = '')
    {
        $constraints = [];
        if (trim($column) !== '') {
            $colPosList = implode(',', GeneralUtility::intExplode(',', $column, true));
            $constraints[] = 'colPos IN(' . ($colPosList !== '' ? $colPosList : '0') . ')';
        }

        switch ($type) {
            case 'all':
                break;
            case 'header':
                $constraints[] = 'sectionIndex = 1';
                $constraints[] = 'header <> \'\'';
                $constraints[] = 'header_layout <> 100';
                break;
            default:
                $constraints[] = 'sectionIndex = 1';
        }

        $whereStatement = implode(' AND ', $constraints);

        $contentElements = $this->getTypoScriptFrontendController()->cObj->getRecords('tt_content', [
            'where' => $whereStatement,
            'orderBy' => 'sorting',
            'languageField' => 'sys_language_uid',
            'pidInList' => (int)$pageUid
        ]);

        return $contentElements;
    }
}
