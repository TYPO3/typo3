<?php
namespace TYPO3\CMS\Backend\Controller\ContentElement;

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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for showing the history module of TYPO3s backend
 * @see \TYPO3\CMS\Backend\History\RecordHistory
 */
class ElementHistoryController
{
    /**
     * @var string
     */
    public $content;

    /**
     * Document template object
     *
     * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    public $doc;

    /**
     * @var array
     */
    protected $pageInfo;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->getLanguageService()->includeLLFile('EXT:lang/locallang_show_rechis.xlf');
        $GLOBALS['SOBE'] = $this;

        $this->init();
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->main();

        $this->content .= $this->doc->endPage();
        $this->content = $this->doc->insertStylesAndJS($this->content);

        $response->getBody()->write($this->content);
        return $response;
    }

    /**
     * Initialize the module output
     *
     * @return void
     */
    protected function init()
    {
        // Create internal template object
        $this->doc = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);
        $this->doc->setModuleTemplate('EXT:backend/Resources/Private/Templates/show_rechis.html');
        // Start the page header
        $this->content .= $this->doc->header($this->getLanguageService()->getLL('title'));
    }

    /**
     * Generate module output
     *
     * @return void
     */
    public function main()
    {
        // Start history object
        $historyObj = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\History\RecordHistory::class);
        // Get content:
        $this->content .= $historyObj->main();
        // Setting up the buttons and markers for docheader
        $docHeaderButtons = $this->getButtons();
        $markers['CONTENT'] = $this->content;
        $markers['CSH'] = $docHeaderButtons['csh'];
        // Build the <body> for the module
        $this->content = $this->doc->startPage($this->getLanguageService()->getLL('title'));
        $this->content .= $this->doc->moduleBody($this->pageInfo, $docHeaderButtons, $markers);
    }

    /**
     * Outputting the accumulated content to screen
     *
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use mainAction() instead
     */
    public function printContent()
    {
        GeneralUtility::logDeprecatedFunction();
        $this->content .= $this->doc->endPage();
        $this->content = $this->doc->insertStylesAndJS($this->content);
        echo $this->content;
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     *
     * @return array All available buttons as an assoc. array
     */
    protected function getButtons()
    {
        $buttons = array(
            'csh' => '',
            'back' => ''
        );
        // CSH
        $buttons['csh'] = \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('xMOD_csh_corebe', 'history_log');
        // Get returnUrl parameter
        $returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
        if ($returnUrl) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $buttons['back'] = '<a href="' . htmlspecialchars($returnUrl) . '" class="typo3-goBack">' . $iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL)->render() . '</a>';
        }
        return $buttons;
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
