<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Backend\ViewHelpers\Link;

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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Use this ViewHelper to provide 'create new record' links.
 * The ViewHelper will pass the command to FormEngine.
 *
 * The table argument is mandatory, it decides what record is to be created.
 *
 * The pid argument will put the new record on this page, if ``0`` given it will
 * be placed to the root page.
 *
 * The uid argument accepts only negative values. If this is given, the new
 * record will be placed (by sorting field) behind the record with the uid.
 * It will end up on the same pid as this given record, so the pid must not
 * be given explicitly by pid argument.
 *
 * An exception will be thrown, if both uid and pid are given.
 * An exception will be thrown, if the uid argument is not a negative integer.
 *
 * To edit records, use the :ref:`<be:link.editRecordViewHelper> <typo3-backend-link-editrecord>`.
 *
 * Examples
 * ========
 *
 * Link to create a new record of a_table after record 17 on the same pid::
 *
 *    <be:link.newRecord table="a_table" returnUrl="foo/bar" uid="-17"/>
 *
 * Output::
 *
 *    <a href="/typo3/index.php?route=/record/edit&edit[a_table][-17]=new&returnUrl=foo/bar">
 *        Edit record
 *    </a>
 *
 * Link to create a new record of a_table on root page::
 *
 *    <be:link.newRecord table="a_table" returnUrl="foo/bar""/>
 *
 * Output::
 *
 *    <a href="/typo3/index.php?route=/record/edit&edit[a_table][]=new&returnUrl=foo/bar">
 *        Edit record
 *    </a>
 *
 * Link to create a new record of a_table on page 17::
 *
 *    <be:link.newRecord table="a_table" returnUrl="foo/bar" pid="17"/>
 *
 * Output::
 *
 *    <a href="/typo3/index.php?route=/record/edit&edit[a_table][-17]=new&returnUrl=foo/bar">
 *        Edit record
 *    </a>
 *
 * Link to create a new record then return back to the BE module "web_MyextensionList"::
 *
 *    <be:link.newRecord table="a_table" returnUrl="{f:be.uri(route: 'web_MyextensionList')}" pid="17">
 */
class NewRecordViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument('uid', 'int', 'uid < 0 will insert the record after the given uid', false);
        $this->registerArgument('pid', 'int', 'the page id where the record will be created', false);
        $this->registerArgument('table', 'string', 'target database table', true);
        $this->registerArgument('returnUrl', 'string', 'return to this URL after closing the edit dialog', false, '');
    }

    /**
     * @return string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function render(): string
    {
        if ($this->arguments['uid'] && $this->arguments['pid']) {
            throw new \InvalidArgumentException('Can\'t handle both uid and pid for new records', 1526129969);
        }
        if (isset($this->arguments['uid']) && $this->arguments['uid'] >= 0) {
            throw new \InvalidArgumentException('Uid must be negative integer, ' . $this->arguments['uid'] . ' given', 1526134901);
        }

        if (empty($this->arguments['returnUrl'])) {
            $this->arguments['returnUrl'] = GeneralUtility::getIndpEnv('REQUEST_URI');
        }

        $params = [
            'edit' => [$this->arguments['table'] => [$this->arguments['uid'] ?? $this->arguments['pid'] ?? 0 => 'new']],
            'returnUrl' => $this->arguments['returnUrl']
        ];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uri = (string)$uriBuilder->buildUriFromRoute('record_edit', $params);
        $this->tag->addAttribute('href', $uri);
        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }
}
