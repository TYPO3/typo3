<?php
namespace TYPO3\CMS\Form\Controller;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Repository\ContentRepository;

/**
 * The form wizard controller
 */
class WizardController
{
    /**
     * The constructor to load the LL file
     */
    public function __construct()
    {
        $this->getLanguageService()->includeLLFile('EXT:form/Resources/Private/Language/locallang_wizard.xlf');
    }

    /**
     * The index action
     *
     * The action which should be taken when the wizard is loaded
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface returns a 500 error or a valid JSON response
     */
    public function indexAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        /** @var $view \TYPO3\CMS\Form\View\Wizard\WizardView */
        $view = GeneralUtility::makeInstance(\TYPO3\CMS\Form\View\Wizard\WizardView::class, $this->getRepository());
        $content = $view->render();
        $response->getBody()->write($content);
        return $response;
    }

    /**
     * The save action called via AJAX
     *
     * The action which should be taken when the form in the wizard is saved
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface returns a 500 error or a valid JSON response
     */
    public function saveAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $repository = $this->getRepository();
        $success = false;
        // Check if the referenced record is available
        if ($repository->hasRecord()) {
            // Save the data
            $success = $repository->save();
        }

        if (!$success) {
            $response = $response->withStatus(500);
            $message = $this->getLanguageService()->getLL('action_save_message_failed', false);
        } else {
            $message = $this->getLanguageService()->getLL('action_save_message_saved', false);
        }
        $response->getBody()->write(json_encode(['message' => $message]));
        return $response
                ->withHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT')
                ->withHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
                ->withHeader('Cache-Control', 'no-cache, must-revalidate')
                ->withHeader('Pragma', 'no-cache');
    }

    /**
     * The load action called via AJAX
     *
     * The action which should be taken when the form in the wizard is loaded
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response the response object
     * @return ResponseInterface returns a 500 error or a valid JSON response
     */
    public function loadAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $result = $this->getRepository()->getRecordAsJson();
        if (!$result) {
            $response = $response->withStatus(500);
            $result = ['message' => $this->getLanguageService()->getLL('action_load_message_failed', false)];
        } else {
            $result = ['configuration' => $result];
        }
        $response->getBody()->write(json_encode($result));
        return $response
                ->withHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT')
                ->withHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
                ->withHeader('Cache-Control', 'no-cache, must-revalidate')
                ->withHeader('Pragma', 'no-cache');
    }

    /**
     * Gets the repository object.
     *
     * @return ContentRepository
     */
    protected function getRepository()
    {
        return GeneralUtility::makeInstance(ContentRepository::class);
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
