<?php
namespace TYPO3\CMS\T3editor\Hook;

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

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * File edit hook for t3editor
 */
class FileEditHook
{
    /**
     * @var \TYPO3\CMS\T3editor\T3editor
     */
    protected $t3editor = null;

    /**
     * @var string
     */
    protected $ajaxSaveType = 'TypoScriptTemplateInformationModuleFunctionController';

    /**
     * @return \TYPO3\CMS\T3editor\T3editor
     */
    protected function getT3editor()
    {
        if ($this->t3editor === null) {
            $this->t3editor = GeneralUtility::makeInstance(\TYPO3\CMS\T3editor\T3editor::class)->setAjaxSaveType($this->ajaxSaveType);
        }
        return $this->t3editor;
    }

    /**
     * Hook-function: inject t3editor JavaScript code before the page is compiled
     * called in file_edit module
     *
     * @param array $parameters
     * @param \TYPO3\CMS\Backend\Controller\File\EditFileController $pObj
     */
    public function preOutputProcessingHook($parameters, $pObj)
    {
        $t3editor = $this->getT3editor();
        $t3editor->setModeByFile($parameters['target']);
        if (!$t3editor->getMode()) {
            return;
        }
        $t3editor->getJavascriptCode();
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/T3editor/FileEdit');
    }

    /**
     * Hook-function: inject t3editor JavaScript code before the page is compiled
     * called in \TYPO3\CMS\Backend\Template\DocumentTemplate:startPage
     *
     * @see \TYPO3\CMS\Backend\Template\DocumentTemplate::startPage
     */
    public function preStartPageHook()
    {
        // @todo: this is a workaround. Ideally the document template holds the current request so we can match the route
        // against the name of the route and not the GET parameter
        if (GeneralUtility::_GET('route') === '/file/editcontent') {
            $t3editor = $this->getT3editor();
            $t3editor->getJavascriptCode();
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/T3editor/FileEdit');
        }
    }

    /**
     * Hook-function:
     * called in file_edit module
     *
     * @param array $parameters
     * @param \TYPO3\CMS\Backend\Controller\File\EditFileController $pObj
     */
    public function postOutputProcessingHook($parameters, $pObj)
    {
        $t3editor = $this->getT3editor();
        if (!$t3editor->getMode()) {
            return;
        }
        $attributes = 'rows="30" ' . 'wrap="off"' . $pObj->doc->formWidth(48, true, 'width:98%;height:60%');
        $title = $GLOBALS['LANG']->getLL('file') . ' ' . htmlspecialchars($pObj->target);
        $outCode = $t3editor->getCodeEditor('file[editfile][0][data]', 'text-monospace enable-tab', '$1', $attributes, $title, [
            'target' => (int)$pObj->target
        ]);
        $parameters['pageContent'] = preg_replace('/\\<textarea .*name="file\\[editfile\\]\\[0\\]\\[data\\]".*\\>([^\\<]*)\\<\\/textarea\\>/mi', $outCode, $parameters['pageContent']);
    }

    /**
     * @param array $parameters
     * @param mixed $pObj
     *
     * @return bool TRUE if successful
     */
    public function save($parameters, $pObj)
    {
        $savingsuccess = false;
        if ($parameters['type'] === $this->ajaxSaveType) {
            /** @var \TYPO3\CMS\Backend\Controller\File\FileController $tceFile */
            $tceFile = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Controller\File\FileController::class);
            $response = $tceFile->processAjaxRequest($parameters['request'], $parameters['response']);
            $result = json_decode((string)$response->getBody(), true);
            $savingsuccess = is_array($result) && $result['editfile'][0];
        }
        return $savingsuccess;
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
