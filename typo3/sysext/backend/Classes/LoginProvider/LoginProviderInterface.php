<?php
namespace TYPO3\CMS\Backend\LoginProvider;

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

use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Interface for Backend Login providers
 */
interface LoginProviderInterface
{
    /**
     * Render the login HTML
     *
     * Implement this method and set the template for your form.
     * This is also the right place to assign data to the view
     * and add necessary JavaScript resources to the page renderer.
     *
     * A good example is EXT:openid
     *
     * Example:
     *    $view->setTemplatePathAndFilename($pathAndFilename);
     *    $view->assign('foo', 'bar');
     *
     * @param StandaloneView $view
     * @param PageRenderer $pageRenderer
     * @param LoginController $loginController
     */
    public function render(StandaloneView $view, PageRenderer $pageRenderer, LoginController $loginController);
}
