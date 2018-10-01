<?php
namespace TYPO3\CMS\Backend\Controller;

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
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Core\Http\HtmlResponse;

/**
 * '/empty' routing target returns dummy content.
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class DummyController
{
    /**
     * Return simple dummy content
     *
     * @return ResponseInterface the response with the content
     */
    public function mainAction(): ResponseInterface
    {
        $documentTemplate = $this->getDocumentTemplate();
        $content = $documentTemplate->startPage('Dummy document') . $documentTemplate->endPage();
        return new HtmlResponse($content);
    }

    /**
     * Returns an instance of DocumentTemplate
     *
     * @return DocumentTemplate
     */
    protected function getDocumentTemplate()
    {
        return $GLOBALS['TBE_TEMPLATE'];
    }
}
