<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be\Widget;

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
 * This ViewHelper renders a Pagination of objects for the TYPO3 Backend.
 *
 * Examples
 * ========
 *
 * Minimum call with required arguments only::
 *
 *    <f:be.widget.paginate objects="{blogs}" as="paginatedBlogs">
 *       use {paginatedBlogs} as you used {blogs} before, most certainly inside
 *       a <f:for> loop.
 *    </f:be.widget.paginate>
 *
 * Full example with all configuration options::
 *
 *    <f:be.widget.paginate objects="{blogs}" as="paginatedBlogs" configuration="{itemsPerPage: 5, insertAbove: 1, insertBelow: 0, recordsLabel: 'MyRecords'}">
 *       use {paginatedBlogs} as you used {blogs} before, most certainly inside
 *       a <f:for> loop.
 *    </f:be.widget.paginate>
 *
 * The ``recordsLabel`` option can be used to replace the text in "Records 1 - 99" with a custom label.
 *
 * Performance characteristics
 * ===========================
 *
 * In the above examples, it looks like ``{blogs}`` contains all Blog objects, thus
 * you might wonder if all objects were fetched from the database.
 * However, the blogs are NOT fetched from the database until you actually use them,
 * so the paginate ViewHelper will adjust the query sent to the database and receive
 * only the small subset of objects.
 * So, there is no negative performance overhead in using the Be Paginate Widget.
 */
class PaginateViewHelper extends \TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Be\Widget\Controller\PaginateController
     */
    protected $controller;

    /**
     * @param \TYPO3\CMS\Fluid\ViewHelpers\Be\Widget\Controller\PaginateController $controller
     */
    public function injectPaginateController(\TYPO3\CMS\Fluid\ViewHelpers\Be\Widget\Controller\PaginateController $controller)
    {
        $this->controller = $controller;
    }

    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('objects', QueryResultInterface::class, 'The QueryResult containing all objects.', true);
        $this->registerArgument('as', 'string', 'as', true);
        $this->registerArgument('configuration', 'array', 'configuration', false, ['itemsPerPage' => 10, 'insertAbove' => false, 'insertBelow' => true, 'maximumNumberOfLinks' => 99]);
    }

    /**
     * @return string
     */
    public function render()
    {
        return $this->initiateSubRequest();
    }
}
