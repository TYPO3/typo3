<?php
namespace TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching;

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

use TYPO3\CMS\Backend\Controller\EditDocumentController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Matching TypoScript conditions for backend disposal.
 *
 * Used with the TypoScript parser.
 * Matches browserinfo, IPnumbers for use with templates
 */
class ConditionMatcher extends AbstractConditionMatcher
{
    /**
     * Constructor for this class
     */
    public function __construct()
    {
    }

    /**
     * Evaluates a TypoScript condition given as input, eg. "[browser=net][...(other conditions)...]"
     *
     * @param string $string The condition to match against its criteria.
     * @return bool Whether the condition matched
     * @see \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::parse()
     */
    protected function evaluateCondition($string)
    {
        list($key, $value) = GeneralUtility::trimExplode('=', $string, false, 2);
        $result = $this->evaluateConditionCommon($key, $value);
        if (is_bool($result)) {
            return $result;
        }
        switch ($key) {
                case 'usergroup':
                    $groupList = $this->getGroupList();
                    $values = GeneralUtility::trimExplode(',', $value, true);
                    foreach ($values as $test) {
                        if ($test === '*' || GeneralUtility::inList($groupList, $test)) {
                            return true;
                        }
                    }
                    break;
                case 'adminUser':
                    if ($this->isUserLoggedIn()) {
                        return !((bool)$value xor $this->isAdminUser());
                    }
                    break;
                case 'treeLevel':
                    $values = GeneralUtility::trimExplode(',', $value, true);
                    $treeLevel = count($this->rootline) - 1;
                    // If a new page is being edited or saved the treeLevel is higher by one:
                    if ($this->isNewPageWithPageId($this->pageId)) {
                        $treeLevel++;
                    }
                    foreach ($values as $test) {
                        if ($test == $treeLevel) {
                            return true;
                        }
                    }
                    break;
                case 'PIDupinRootline':
                case 'PIDinRootline':
                    $values = GeneralUtility::trimExplode(',', $value, true);
                    if ($key === 'PIDinRootline' || !in_array($this->pageId, $values) || $this->isNewPageWithPageId($this->pageId)) {
                        foreach ($values as $test) {
                            foreach ($this->rootline as $rl_dat) {
                                if ($rl_dat['uid'] == $test) {
                                    return true;
                                }
                            }
                        }
                    }
                    break;
                default:
                    $conditionResult = $this->evaluateCustomDefinedCondition($string);
                    if ($conditionResult !== null) {
                        return $conditionResult;
                    }
            }

        return false;
    }

    /**
     * Returns GP / ENV vars
     *
     * @param string $var Identifier
     * @return mixed The value of the variable pointed to or NULL if variable did not exist
     * @access private
     */
    protected function getVariable($var)
    {
        $vars = explode(':', $var, 2);
        return $this->getVariableCommon($vars);
    }

    /**
     * Get the usergroup list of the current user.
     *
     * @return string The usergroup list of the current user
     */
    protected function getGroupList()
    {
        return $this->getBackendUserAuthentication()->groupList;
    }

    /**
     * Tries to determine the ID of the page currently processed.
     * When User/Group TS-Config is parsed when no specific page is handled
     * (i.e. in the Extension Manager, etc.) this function will return "0", so that
     * the accordant conditions (e.g. PIDinRootline) will return "FALSE"
     *
     * @return int The determined page id or otherwise 0
     */
    protected function determinePageId()
    {
        $pageId = 0;
        $editStatement = GeneralUtility::_GP('edit');
        $commandStatement = GeneralUtility::_GP('cmd');
        // Determine id from module that was called with an id:
        if ($id = (int)GeneralUtility::_GP('id')) {
            $pageId = $id;
        } elseif (is_array($editStatement)) {
            $table = key($editStatement);
            $uidAndAction = current($editStatement);
            $uid = key($uidAndAction);
            $action = current($uidAndAction);
            if ($action === 'edit') {
                $pageId = $this->getPageIdByRecord($table, $uid);
            } elseif ($action === 'new') {
                $pageId = $this->getPageIdByRecord($table, $uid, true);
            }
        } elseif (is_array($commandStatement)) {
            $table = key($commandStatement);
            $uidActionAndTarget = current($commandStatement);
            $uid = key($uidActionAndTarget);
            $actionAndTarget = current($uidActionAndTarget);
            $action = key($actionAndTarget);
            $target = current($actionAndTarget);
            if ($action === 'delete') {
                $pageId = $this->getPageIdByRecord($table, $uid);
            } elseif ($action === 'copy' || $action === 'move') {
                $pageId = $this->getPageIdByRecord($table, $target, true);
            }
        }
        return $pageId;
    }

    /**
     * Gets the properties for the current page.
     *
     * @return array The properties for the current page.
     */
    protected function getPage()
    {
        $pageId = isset($this->pageId) ? $this->pageId : $this->determinePageId();
        return BackendUtility::getRecord('pages', $pageId);
    }

    /**
     * Gets the page id by a record.
     *
     * @param string $table Name of the table
     * @param int $id Id of the accordant record
     * @param bool $ignoreTable Whether to ignore the page, if TRUE a positive
     * @return int Id of the page the record is persisted on
     */
    protected function getPageIdByRecord($table, $id, $ignoreTable = false)
    {
        $pageId = 0;
        $id = (int)$id;
        if ($table && $id) {
            if (($ignoreTable || $table === 'pages') && $id >= 0) {
                $pageId = $id;
            } else {
                $record = BackendUtility::getRecordWSOL($table, abs($id), '*', '', false);
                $pageId = $record['pid'];
            }
        }
        return $pageId;
    }

    /**
     * Determine if record of table 'pages' with the given $pid is currently created in TCEforms.
     * This information is required for conditions in BE for PIDupinRootline.
     *
     * @param int $pageId The pid the check for as parent page
     * @return bool TRUE if the is currently a new page record being edited with $pid as uid of the parent page
     */
    protected function isNewPageWithPageId($pageId)
    {
        if (isset($GLOBALS['SOBE']) && $GLOBALS['SOBE'] instanceof EditDocumentController) {
            $pageId = (int)$pageId;
            $elementsData = $GLOBALS['SOBE']->elementsData;
            $data = $GLOBALS['SOBE']->data;
            // If saving a new page record:
            if (is_array($data) && isset($data['pages']) && is_array($data['pages'])) {
                foreach ($data['pages'] as $uid => $fields) {
                    if (strpos($uid, 'NEW') === 0 && $fields['pid'] == $pageId) {
                        return true;
                    }
                }
            }
            // If editing a new page record (not saved yet):
            if (is_array($elementsData)) {
                foreach ($elementsData as $element) {
                    if ($element['cmd'] === 'new' && $element['table'] === 'pages') {
                        if ($element['pid'] < 0) {
                            $pageRecord = BackendUtility::getRecord('pages', abs($element['pid']), 'pid');
                            $element['pid'] = $pageRecord['pid'];
                        }
                        if ($element['pid'] == $pageId) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Determines the rootline for the current page.
     *
     * @return array The rootline for the current page.
     */
    protected function determineRootline()
    {
        $pageId = isset($this->pageId) ? $this->pageId : $this->determinePageId();
        return BackendUtility::BEgetRootLine($pageId, '', true);
    }

    /**
     * Get the id of the current user.
     *
     * @return int The id of the current user
     */
    protected function getUserId()
    {
        return $this->getBackendUserAuthentication()->user['uid'];
    }

    /**
     * Determines if a user is logged in.
     *
     * @return bool Determines if a user is logged in
     */
    protected function isUserLoggedIn()
    {
        return (bool)$this->getBackendUserAuthentication()->user['uid'];
    }

    /**
     * Determines whether the current user is admin.
     *
     * @return bool Whether the current user is admin
     */
    protected function isAdminUser()
    {
        return $this->getBackendUserAuthentication()->isAdmin();
    }

    /**
     * Set/write a log message.
     *
     * @param string $message The log message to set/write
     */
    protected function log($message)
    {
        if (is_object($this->getBackendUserAuthentication())) {
            $this->getBackendUserAuthentication()->writelog(3, 0, 1, 0, $message, []);
        }
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
