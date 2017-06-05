<?php
declare(strict_types=1);
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

use TYPO3\CMS\Backend\Controller\File\EditFileController;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\T3editor\Form\Element\T3editorElement;

/**
 * File edit hook for t3editor
 */
class FileEditHook
{
    /**
     * Editor mode to file extension mapping. This is just temporarily in place and will be removed after refactoring
     * EXT:t3editor.
     *
     * @var array
     */
    protected $fileExtensions = [
        T3editorElement::MODE_CSS => ['css'],
        T3editorElement::MODE_HTML => ['htm', 'html'],
        T3editorElement::MODE_JAVASCRIPT => ['js'],
        T3editorElement::MODE_PHP => ['php', 'php5', 'php7', 'phps'],
        T3editorElement::MODE_SPARQL => ['rq'],
        T3editorElement::MODE_TYPOSCRIPT => ['ts', 'typoscript', 'txt'],
        T3editorElement::MODE_XML => ['xml'],
    ];

    /**
     * Hook-function: inject t3editor JavaScript code before the page is compiled
     * called in file_edit module
     *
     * @param array $parameters
     * @param EditFileController $pObj
     */
    public function preOutputProcessingHook(array $parameters, EditFileController $pObj)
    {
        $target = '';
        if (isset($parameters['target']) && is_string($parameters['target'])) {
            $target = $parameters['target'];
        }
        $parameters['dataColumnDefinition']['config']['renderType'] = 't3editor';
        $parameters['dataColumnDefinition']['config']['format'] = $this->determineFormatByExtension($target);
    }

    /**
     * @param string $fileIdentifier
     * @return string
     */
    protected function determineFormatByExtension(string $fileIdentifier): string
    {
        $fileExtension = ResourceFactory::getInstance()->retrieveFileOrFolderObject($fileIdentifier)->getExtension();
        if (empty($fileExtension)) {
            return T3editorElement::MODE_MIXED;
        }

        foreach ($this->fileExtensions as $format => $extensions) {
            if (in_array($fileExtension, $extensions, true)) {
                return $format;
            }
        }

        return T3editorElement::MODE_MIXED;
    }
}
