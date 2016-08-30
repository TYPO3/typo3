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

use TYPO3\CMS\Backend\Controller\File\FileController;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\T3editor\T3editor;

/**
 * File edit hook for t3editor
 */
class FileEditHook
{
    /**
     * @var T3editor
     */
    protected $t3editor = null;

    /**
     * @var string
     */
    protected $ajaxSaveType = 'TypoScriptTemplateInformationModuleFunctionController';

    /**
     * @return T3editor
     */
    protected function getT3editor()
    {
        if ($this->t3editor === null) {
            $this->t3editor = GeneralUtility::makeInstance(T3editor::class);
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
    public function preOutputProcessingHook(&$parameters, $pObj)
    {
        $t3editor = $this->getT3editor();
        $t3editor->setModeByFile($parameters['target']);
        if (!$t3editor->getMode()) {
            return;
        }
        $parameters['dataColumnDefinition']['config']['renderType'] = 't3editor';
        $parameters['dataColumnDefinition']['config']['format'] = $t3editor->getMode();
        $parameters['dataColumnDefinition']['config']['ajaxSaveType'] = $this->ajaxSaveType;
    }

    /**
     * @param array $parameters
     * @param T3editor $pObj
     * @return bool TRUE if successful
     */
    public function save($parameters, $pObj)
    {
        $savingsuccess = false;
        if ($parameters['type'] === $this->ajaxSaveType) {
            /** @var FileController $tceFile */
            $tceFile = GeneralUtility::makeInstance(FileController::class);
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
