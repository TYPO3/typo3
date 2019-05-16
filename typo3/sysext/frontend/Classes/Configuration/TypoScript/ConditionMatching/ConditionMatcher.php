<?php

namespace TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching;

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

use TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Matching TypoScript conditions for frontend disposal.
 *
 * Used with the TypoScript parser. Matches browserinfo
 * and IP numbers for use with templates.
 */
class ConditionMatcher extends AbstractConditionMatcher
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @param Context $context optional context to fetch data from
     */
    public function __construct(Context $context = null)
    {
        $this->context = $context ?? GeneralUtility::makeInstance(Context::class);
        $this->rootline = (array)$GLOBALS['TSFE']->tmpl->rootLine;
        $this->initializeExpressionLanguageResolver();
    }

    protected function updateExpressionLanguageVariables(): void
    {
        $tree = new \stdClass();
        $tree->level = $this->rootline ? count($this->rootline) - 1 : 0;
        $tree->rootLine = $this->rootline;
        $tree->rootLineIds = array_column($this->rootline, 'uid');

        $frontendUserAspect = $this->context->getAspect('frontend.user');
        $frontend = new \stdClass();
        $frontend->user = new \stdClass();
        $frontend->user->isLoggedIn = $frontendUserAspect->get('isLoggedIn');
        $frontend->user->userId = $frontendUserAspect->get('id');
        $frontend->user->userGroupList = implode(',', $frontendUserAspect->get('groupIds'));

        $this->expressionLanguageResolverVariables = [
            'tree' => $tree,
            'frontend' => $frontend,
            'page' => $GLOBALS['TSFE']->page ?? [],
        ];
    }
}
