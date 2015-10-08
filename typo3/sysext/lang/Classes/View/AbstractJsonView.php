<?php
namespace TYPO3\CMS\Lang\View;

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

/**
 * Base class for JSON views
 */
abstract class AbstractJsonView extends \TYPO3\CMS\Extbase\Mvc\View\AbstractView
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Render template content
     *
     * @return void
     */
    public function render()
    {
        $result = $this->getReponseData();
        $this->sendResponse($result);
    }

    /**
     * Returns the response data
     *
     * @return array The response data
     */
    abstract protected function getReponseData();

    /**
     * Send response to browser
     *
     * @param array $data The response data
     * @return void
     */
    protected function sendResponse(array $data)
    {
        $response = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Web\Response::class);
        $response->setHeader('Content-Type', 'application/json; charset=utf-8');
        $response->setContent(json_encode($data));
        $response->sendHeaders();
        $response->send();
        exit;
    }
}
