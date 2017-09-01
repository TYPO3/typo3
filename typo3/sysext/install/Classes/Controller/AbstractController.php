<?php
namespace TYPO3\CMS\Install\Controller;

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
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Controller\Action\Common\LoginForm;

/**
 * Controller abstract for shared parts of Tool, Step and Ajax controller
 */
class AbstractController
{
    /**
     * @var array List of valid action names that need authentication
     */
    protected $authenticationActions = [];

    /**
     * Show login form
     *
     * @param ServerRequestInterface $request
     * @param FlashMessage $message Optional status message
     * @return string Rendered HTML
     */
    protected function loginForm(ServerRequestInterface $request, FlashMessage $message = null)
    {
        /** @var LoginForm $action */
        $action = GeneralUtility::makeInstance(LoginForm::class);
        $action->setController('common');
        $action->setAction('login');
        $action->setContext($request->getAttribute('context', 'standalone'));
        $action->setToken($this->generateTokenForAction('login'));
        $action->setPostValues($request->getParsedBody()['install'] ?? []);
        if ($message) {
            $action->setMessages([$message]);
        }
        return $action->handle();
    }

    /**
     * Generate token for specific action
     *
     * @param string $action Action name
     * @return string Form protection token
     * @throws Exception
     */
    protected function generateTokenForAction($action = null)
    {
        if ($action === '') {
            throw new Exception(
                'Token must have a valid action name',
                1369326592
            );
        }
        /** @var $formProtection \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection */
        $formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get(
            \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection::class
        );
        return $formProtection->generateToken('installTool', $action);
    }

    /**
     * Check given action name is one of the allowed actions.
     *
     * @param string $action Given action to validate
     * @throws Exception
     */
    protected function validateAuthenticationAction($action)
    {
        if (!in_array($action, $this->authenticationActions)) {
            throw new Exception(
                $action . ' is not a valid authentication action',
                1369345838
            );
        }
    }

    /**
     * Retrieve parameter from GET or POST and sanitize
     *
     * @throws Exception
     * @param string $action requested action
     * @return string Empty string if no action is given or sanitized action string
     */
    protected function sanitizeAction($action = '')
    {
        if ($action !== ''
            && $action !== 'login'
            && $action !== 'loginForm'
            && $action !== 'logout'
            && !in_array($action, $this->authenticationActions)
        ) {
            throw new Exception(
                'Invalid action ' . $action,
                1369325619
            );
        }
        return $action;
    }

    /**
     * HTTP redirect to self, preserving allowed GET variables.
     * WARNING: This exits the script execution!
     *
     * @param string $controller Can be set to 'tool' to redirect from step to tool controller
     * @param string $action Set specific action for next request, used in step controller to specify next step
     * @throws Exception\RedirectLoopException
     */
    public function redirect($controller = '', $action = '')
    {
        $getPostValues = GeneralUtility::_GP('install');

        $parameters = [];

        // Current redirect count
        if (isset($getPostValues['redirectCount'])) {
            $redirectCount = (int)$getPostValues['redirectCount'] + 1;
        } else {
            $redirectCount = 0;
        }
        if ($redirectCount >= 10) {
            // Abort a redirect loop by throwing an exception. Calling this method
            // some times in a row is ok, but break a loop if this happens too often.
            throw new Exception\RedirectLoopException(
                'Redirect loop aborted. If this message is shown again after a reload,' .
                    ' your setup is so weird that the install tool is unable to handle it.' .
                    ' Please make sure to remove the "install[redirectCount]" parameter from your request or' .
                    ' restart the install tool from the backend navigation.',
                1380581244
            );
        }
        $parameters[] = 'install[redirectCount]=' . $redirectCount;

        // Add controller parameter
        $controllerParameter = 'install[controller]=step';
        if ((isset($getPostValues['controller']) && $getPostValues['controller'] === 'tool')
            || $controller === 'tool'
        ) {
            $controllerParameter = 'install[controller]=tool';
        }
        $parameters[] = $controllerParameter;

        // Add action if specified
        if ((string)$action !== '') {
            $parameters[] = 'install[action]=' . $action;
        }

        $redirectLocation = GeneralUtility::getIndpEnv('TYPO3_REQUEST_SCRIPT') . '?' . implode('&', $parameters);

        \TYPO3\CMS\Core\Utility\HttpUtility::redirect(
            $redirectLocation,
            \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_303
        );
    }

    /**
     * Creates a PSR-7 response
     *
     * @param string $content
     * @return ResponseInterface
     */
    public function output($content = ''): ResponseInterface
    {
        $response = new Response('php://temp', 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Pragma' => 'no-cache'
        ]);
        $response->getBody()->write($content);
        return $response;
    }
}
