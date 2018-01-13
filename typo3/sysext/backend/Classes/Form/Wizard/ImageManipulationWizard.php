<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Form\Wizard;

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
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Wizard for rendering image manipulation view
 */
class ImageManipulationWizard
{
    /**
     * @var StandaloneView
     */
    private $templateView;

    /**
     * @param StandaloneView $templateView
     */
    public function __construct(StandaloneView $templateView = null)
    {
        if (!$templateView) {
            $templateView = GeneralUtility::makeInstance(StandaloneView::class);
            $templateView->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Layouts/')]);
            $templateView->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials/ImageManipulation/')]);
            $templateView->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/ImageManipulation/ImageManipulationWizard.html'));
        }
        $this->templateView = $templateView;
    }

    /**
     * Returns the HTML for the wizard inside the modal
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface $response
     */
    public function getWizardAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($this->isSignatureValid($request)) {
            $queryParams = json_decode($request->getQueryParams()['arguments'], true);
            $fileUid = $queryParams['image'];
            $image = null;
            if (MathUtility::canBeInterpretedAsInteger($fileUid)) {
                try {
                    $image = ResourceFactory::getInstance()->getFileObject($fileUid);
                } catch (FileDoesNotExistException $e) {
                }
            }
            $viewData = [
                'image' => $image,
                'cropVariants' => $queryParams['cropVariants']
            ];
            $content = $this->templateView->renderSection('Main', $viewData);
            $response->getBody()->write($content);

            return $response;
        }
        return $response->withStatus(403);
    }

    /**
     * Check if hmac signature is correct
     *
     * @param ServerRequestInterface $request the request with the GET parameters
     * @return bool
     */
    protected function isSignatureValid(ServerRequestInterface $request)
    {
        $token = GeneralUtility::hmac($request->getQueryParams()['arguments'], 'ajax_wizard_image_manipulation');
        return hash_equals($token, $request->getQueryParams()['signature']);
    }
}
