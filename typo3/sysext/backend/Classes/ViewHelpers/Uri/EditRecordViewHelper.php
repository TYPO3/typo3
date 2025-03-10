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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to provide edit links (only the URI) to records. The ViewHelper will
 * pass the uid and table to FormEngine.
 *
 * The uid must be given as a positive integer.
 * For new records, use `<be:uri.newRecord>`.
 *
 * ```
 *    <be:uri.editRecord uid="42" table="pages" fields="title,subtitle" returnUrl="foo/bar" />
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-backend-uri-editrecord
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-backend-uri-newrecord
 */
final class EditRecordViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('uid', 'int', 'uid of record to be edited, 0 for creation', true);
        $this->registerArgument('table', 'string', 'target database table', true);
        $this->registerArgument('fields', 'string', 'Edit only these fields (comma separated list)');
        $this->registerArgument('returnUrl', 'string', 'return to this URL after closing the edit dialog', false, '');
    }

    /**
     * @throws \InvalidArgumentException
     * @throws RouteNotFoundException
     */
    public function render(): string
    {
        if ($this->arguments['uid'] < 1) {
            throw new \InvalidArgumentException('Uid must be a positive integer, ' . $this->arguments['uid'] . ' given.', 1526128259);
        }
        if (empty($this->arguments['returnUrl'])) {
            $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
            $this->arguments['returnUrl'] = $request->getAttribute('normalizedParams')->getRequestUri();
        }
        $params = [
            'edit' => [$this->arguments['table'] => [$this->arguments['uid'] => 'edit']],
            'returnUrl' => $this->arguments['returnUrl'],
        ];
        if ($this->arguments['fields'] ?? false) {
            $params['columnsOnly'] = [
                $this->arguments['table'] => GeneralUtility::trimExplode(',', $this->arguments['fields'], true),
            ];
        }
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute('record_edit', $params);
    }
}
