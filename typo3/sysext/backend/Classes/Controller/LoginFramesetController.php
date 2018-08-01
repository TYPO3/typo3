<?php
declare(strict_types = 1);
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
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class, putting the frameset together.
 * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0. All logic is moved into LoginController.
 */
class LoginFramesetController
{
    /**
     * @var string
     */
    protected $content;

    /**
     * Constructor
     */
    public function __construct()
    {
        trigger_error(__CLASS__ . ' will be removed in TYPO3 v10.0. Request "index.php?loginRefresh=1" directly to work without the frameset.', E_USER_DEPRECATED);
        $GLOBALS['SOBE'] = $this;
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->createFrameset();
        return new HtmlResponse($this->content);
    }

    /**
     * Main function.
     * Creates the header code and the frameset for the two frames.
     */
    public function main()
    {
        $this->createFrameset();
    }
    /**
     * Main function.
     * Creates the header code and the frameset for the two frames.
     */
    protected function createFrameset(): void
    {
        $title = 'TYPO3 Re-Login (' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . ')';
        $this->getDocumentTemplate()->startPage($title);
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        // Create the frameset for the window
        $this->content = $this->getPageRenderer()->render(PageRenderer::PART_HEADER) . '
			<frameset rows="*,1">
				<frame name="login" src="index.php?loginRefresh=1" marginwidth="0" marginheight="0" scrolling="no" noresize="noresize" />
				<frame name="dummy" src="' . htmlspecialchars((string)$uriBuilder->buildUriFromRoute('dummy')) . '" marginwidth="0" marginheight="0" scrolling="auto" noresize="noresize" />
			</frameset>
		</html>';
    }

    /**
     * Returns an instance of DocumentTemplate
     *
     * @return DocumentTemplate
     */
    protected function getDocumentTemplate(): DocumentTemplate
    {
        return $GLOBALS['TBE_TEMPLATE'];
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
