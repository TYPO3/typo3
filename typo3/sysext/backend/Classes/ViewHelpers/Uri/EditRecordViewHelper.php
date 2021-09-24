<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\ViewHelpers\Uri;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Use this ViewHelper to provide edit links (only the uri) to records. The ViewHelper will
 * pass the uid and table to FormEngine.
 *
 * The uid must be given as a positive integer.
 * For new records, use the :ref:`<be:uri.newRecord> <typo3-backend-uri-newrecord>`.
 *
 * Examples
 * ========
 *
 * URI to the record-edit action passed to FormEngine::
 *
 *    <be:uri.editRecord uid="42" table="a_table" returnUrl="foo/bar" />
 *
 * ``/typo3/record/edit?edit[a_table][42]=edit&returnUrl=foo/bar``
 *
 * URI to the edit record action: edit only the fields title and subtitle of
 * page uid=42 and return to foo/bar::
 *
 *    <be:uri.editRecord uid="42" table="pages" fields="title,subtitle" returnUrl="foo/bar" />
 *
 * ``<a href="/typo3/record/edit&edit[pages][42]=edit&returnUrl=foo/bar&columnsOnly=title,subtitle">``
 */
class EditRecordViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public function initializeArguments()
    {
        $this->registerArgument('uid', 'int', 'uid of record to be edited, 0 for creation', true);
        $this->registerArgument('table', 'string', 'target database table', true);
        $this->registerArgument('fields', 'string', 'Edit only these fields (comma separated list)', false);
        $this->registerArgument('returnUrl', 'string', 'return to this URL after closing the edit dialog', false, '');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        if ($arguments['uid'] < 1) {
            throw new \InvalidArgumentException('Uid must be a positive integer, ' . $arguments['uid'] . ' given.', 1526128259);
        }
        if (empty($arguments['returnUrl'])) {
            $arguments['returnUrl'] = $renderingContext->getRequest()->getAttribute('normalizedParams')->getRequestUri();
        }

        $params = [
            'edit' => [$arguments['table'] => [$arguments['uid'] => 'edit']],
            'returnUrl' => $arguments['returnUrl'],
        ];
        if ($arguments['fields'] ?? false) {
            $params['columnsOnly'] = $arguments['fields'];
        }
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute('record_edit', $params);
    }
}
