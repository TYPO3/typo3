<?php
namespace TYPO3\CMS\T3editor\Controller;

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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * Code completion for t3editor
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class CodeCompletionController
{
    /**
     * Loads all templates up to a given page id (walking the rootline) and
     * cleans parts that are not required for the t3editor codecompletion.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function loadCompletions(ServerRequestInterface $request): ResponseInterface
    {
        // Check whether access is granted (only admin have access to sys_template records):
        if (!$GLOBALS['BE_USER']->isAdmin()) {
            return new HtmlResponse($this->getLanguageService()->sL('LLL:EXT:t3editor/Resources/Private/Language/locallang.xlf:noPermission'), 500);
        }
        $pageId = (int)($request->getParsedBody()['pageId'] ?? $request->getQueryParams()['pageId']);
        // Check whether there is a pageId given:
        if (!$pageId) {
            return new HtmlResponse($this->getLanguageService()->sL('LLL:EXT:t3editor/Resources/Private/Language/locallang.xlf:pageIDInteger'), 500);
        }
        // Fetch the templates
        return (new JsonResponse())->setPayload($this->getMergedTemplates($pageId));
    }

    /**
     * Gets merged templates by walking the rootline to a given page id.
     *
     * @todo oliver@typo3.org: Refactor this method and comment what's going on there
     * @param int $pageId
     * @return array Setup part of merged template records
     */
    protected function getMergedTemplates($pageId)
    {
        $tsParser = GeneralUtility::makeInstance(ExtendedTemplateService::class);
        // Gets the rootLine
        $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageId)->get();
        // This generates the constants/config + hierarchy info for the template.
        $tsParser->runThroughTemplates($rootLine);
        // ts-setup & ts-constants of the currently edited template should not be included
        // therefor we have to delete the last template from the stack
        array_pop($tsParser->config);
        array_pop($tsParser->constants);
        $tsParser->linkObjects = true;
        $tsParser->ext_regLinenumbers = false;
        $tsParser->generateConfig();
        $result = $this->treeWalkCleanup($tsParser->setup);
        return $result;
    }

    /**
     * Walks through a tree of TypoScript configuration an cleans it up.
     *
     * @TODO oliver@typo3.org: Define and comment why this is necessary and exactly happens below
     * @param array $treeBranch TypoScript configuration or sub branch of it
     * @return array Cleaned TypoScript branch
     */
    private function treeWalkCleanup(array $treeBranch)
    {
        $cleanedTreeBranch = [];
        foreach ($treeBranch as $key => $value) {
            //type definition or value-assignment
            if (substr($key, -1) !== '.') {
                if ($value != '') {
                    if (mb_strlen($value) > 20) {
                        $value = mb_substr($value, 0, 20);
                    }
                    if (!isset($cleanedTreeBranch[$key])) {
                        $cleanedTreeBranch[$key] = [];
                    }
                    $cleanedTreeBranch[$key]['v'] = $value;
                }
            } else {
                // subtree (definition of properties)
                $subBranch = $this->treeWalkCleanup($value);
                if ($subBranch) {
                    if (substr($key, -1) === '.') {
                        $key = rtrim($key, '.');
                    }
                    if (!isset($cleanedTreeBranch[$key])) {
                        $cleanedTreeBranch[$key] = [];
                    }
                    $cleanedTreeBranch[$key]['c'] = $subBranch;
                }
            }
        }
        return $cleanedTreeBranch;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
