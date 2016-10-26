<?php
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
     * @var string
     */
    protected $templatePath = 'EXT:backend/Resources/Private/Templates/';

    /**
     * Returns the HTML for the wizard inside the modal
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface $response
     */
    public function getWizardAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($this->isValidToken($request)) {
            $queryParams = $request->getQueryParams();
            $fileUid = isset($request->getParsedBody()['file']) ? $request->getParsedBody()['file'] : $queryParams['file'];
            $image = null;
            if (MathUtility::canBeInterpretedAsInteger($fileUid)) {
                try {
                    $image = ResourceFactory::getInstance()->getFileObject($fileUid);
                } catch (FileDoesNotExistException $e) {
                }
            }

            $view = $this->getFluidTemplateObject($this->templatePath . 'Wizards/ImageManipulationWizard.html');
            $view->assign('image', $image);
            $view->assign('zoom', (bool)$queryParams['zoom']);
            $view->assign('ratios', $this->getAvailableRatios($request));
            $content = $view->render();

            $response->getBody()->write($content);
            return $response;
        } else {
            return $response->withStatus(403);
        }
    }

    /**
     * Check if hmac token is correct
     *
     * @param ServerRequestInterface $request the request with the GET parameters
     * @return bool
     */
    protected function isValidToken(ServerRequestInterface $request)
    {
        $parameters = [
            'zoom'   => $request->getQueryParams()['zoom'] ? '1' : '0',
            'ratios' => $request->getQueryParams()['ratios'] ?: '',
            'file'   => $request->getQueryParams()['file'] ?: '',
        ];

        $token = GeneralUtility::hmac(implode('|', $parameters), 'ImageManipulationWizard');
        return $token === $request->getQueryParams()['token'];
    }

    /**
     * Get available ratios
     *
     * @param ServerRequestInterface $request
     * @return array
     */
    protected function getAvailableRatios(ServerRequestInterface $request)
    {
        $ratios = json_decode($request->getQueryParams()['ratios']);
        // Json transforms an array with string keys to an array,
        // we need to transform this to an array for the fluid ForViewHelper
        if (is_object($ratios)) {
            $ratios = get_object_vars($ratios);
        }
        return $ratios;
    }

    /**
     * Returns a new standalone view, shorthand function
     *
     * @param string $templatePathAndFileName optional the path to set the template path and filename
     * @return StandaloneView
     */
    protected function getFluidTemplateObject($templatePathAndFileName = null)
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        if ($templatePathAndFileName) {
            $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templatePathAndFileName));
        }
        return $view;
    }
}
