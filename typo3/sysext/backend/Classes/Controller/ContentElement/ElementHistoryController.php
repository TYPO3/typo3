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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\CMS\Backend\Module\AbstractModule;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for showing the history module of TYPO3s backend
 * @see \TYPO3\CMS\Backend\History\RecordHistory
 */
class ElementHistoryController extends AbstractModule
{
    /**
     * @var string
     */
    public $content;

    /**
     * Document template object
     *
     * @var DocumentTemplate
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
        parent::__construct();
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

        $response->getBody()->write($this->moduleTemplate->renderContent());
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
        // This is ugly, we need to remove the dependency-wiring via GLOBALS['SOBE']
        // In this case, RecordHistory.php depends on GLOBALS[SOBE] being set in here
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
    }

    /**
     * Generate module output
     *
     * @return void
     */
    public function main()
    {
        $this->content = '<h1>' . $this->getLanguageService()->getLL('title') . '</h1>';
        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation([]);

        // Start history object
        $historyObj = GeneralUtility::makeInstance(RecordHistory::class);

        $elementData = GeneralUtility::trimExplode(':', $historyObj->element);
        $this->setPagePath($elementData[0], $elementData[1]);

        // Get content:
        $this->content .= $historyObj->main();
        // Setting up the buttons and markers for docheader
        $this->getButtons();
        // Build the <body> for the module
        $this->moduleTemplate->setContent($this->content);
    }

    /**
     * Creates the correct path to the current record
     *
     * @param string $table
     * @param int $uid
     */
    protected function setPagePath($table, $uid)
    {
        $uid = (int)$uid;

        if ($table === 'pages') {
            $pageId = $uid;
        } else {
            $record = BackendUtility::getRecord($table, $uid, '*', '', false);
            $pageId = $record['pid'];
        }

        $pageAccess = BackendUtility::readPageAccess($pageId, $this->getBackendUser()->getPagePermsClause(1));
        if (is_array($pageAccess)) {
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageAccess);
        }
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     *
     * @return array All available buttons as an assoc. array
     */
    protected function getButtons()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $helpButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName('history_log');
        $buttonBar->addButton($helpButton);

         // Get returnUrl parameter
        $returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
        if ($returnUrl) {
            $backButton = $buttonBar->makeLinkButton()
                ->setHref($returnUrl)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
        }
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

    /**
     * Gets the current backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
