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
        $typoscript = '';
        $jsonArray = [];

        // Check if the referenced record is available
        if ($repository->hasRecord()) {
            // Save the data
            $typoscript = $repository->save();
        }

        if (!$typoscript) {
            $response = $response->withStatus(500);
            $message = $this->getLanguageService()->getLL('action_save_message_failed', false);
        } else {
            $message = $this->getLanguageService()->getLL('action_save_message_saved', false);
            $jsonArray['fakeTs'] = $typoscript;
        }

        $jsonArray['message'] = $message;
        $response->getBody()->write(json_encode($jsonArray));
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
