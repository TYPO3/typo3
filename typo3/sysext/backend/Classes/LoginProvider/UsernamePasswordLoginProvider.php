<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\LoginProvider;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Authentication\PasswordReset;
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * The default username + password based backend login form.
 *
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
#[Autoconfigure(public: true)]
final readonly class UsernamePasswordLoginProvider implements LoginProviderInterface
{
    public function __construct(
        private PasswordReset $passwordReset,
    ) {}

    /**
     * @deprecated Remove in v14 when method is removed from LoginProviderInterface
     */
    public function render(StandaloneView $view, PageRenderer $pageRenderer, LoginController $loginController): void
    {
        throw new \RuntimeException('Legacy interface implementation. Should not be called', 1724768908);
    }

    public function modifyView(ServerRequestInterface $request, ViewInterface $view): string
    {
        if ($request->getAttribute('normalizedParams')->isHttps()) {
            $view->assignMultiple([
                'presetUsername' => $request->getParsedBody()['u'] ?? $request->getQueryParams()['u'] ?? null,
                'presetPassword' => $request->getParsedBody()['p'] ?? $request->getQueryParams()['p'] ?? null,
            ]);
        }
        $view->assign('enablePasswordReset', $this->passwordReset->isEnabled());
        return 'Login/UserPassLoginForm';
    }
}
