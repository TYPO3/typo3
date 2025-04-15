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
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * ViewHelper to provide 'create new record' links.
 * The ViewHelper will pass the command to FormEngine.
 *
 * The `pid` argument will put the new record on this page, if ``0`` given it will
 * be placed to the root page.
 *
 * The `uid` argument accepts only negative values. If this is given, the new
 * record will be placed (by sorting field) behind the record with the uid.
 * It will end up on the same pid as this given record, so the pid must not
 * be given explicitly by pid argument.
 *
 * An exception will be thrown, if both uid and pid are given.
 * An exception will be thrown, if the uid argument is not a negative integer.
 *
 * ```
 *    <be:uri.newRecord table="a_table" returnUrl="foo/bar" uid="-17"/>
 *    <be:uri.newRecord table="a_table" returnUrl="foo/bar" pid="17"/>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-backend-uri-newrecord
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-backend-uri-editrecord
 */
final class NewRecordViewHelper extends AbstractTagBasedViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('uid', 'int', 'uid < 0 will insert the record after the given uid');
        $this->registerArgument('pid', 'int', 'the page id where the record will be created');
        $this->registerArgument('table', 'string', 'target database table', true);
        $this->registerArgument('returnUrl', 'string', 'return to this URL after closing the edit dialog', false, '');
        $this->registerArgument('defaultValues', 'array', 'default values for fields of the new record', false, []);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws RouteNotFoundException
     */
    public function render(): string
    {
        if ($this->arguments['uid'] && $this->arguments['pid']) {
            throw new \InvalidArgumentException('Can\'t handle both uid and pid for new records', 1526136338);
        }
        if (isset($this->arguments['uid']) && $this->arguments['uid'] >= 0) {
            throw new \InvalidArgumentException('Uid must be negative integer, ' . $this->arguments['uid'] . ' given', 1526136362);
        }
        if (empty($this->arguments['returnUrl'])) {
            $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
            $this->arguments['returnUrl'] = $request->getAttribute('normalizedParams')->getRequestUri();
        }
        $params = [
            'edit' => [$this->arguments['table'] => [$this->arguments['uid'] ?? $this->arguments['pid'] ?? 0 => 'new']],
            'returnUrl' => $this->arguments['returnUrl'],
        ];
        if ($this->arguments['defaultValues']) {
            $params['defVals'] = $this->arguments['defaultValues'];
        }
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute('record_edit', $params);
    }
}
