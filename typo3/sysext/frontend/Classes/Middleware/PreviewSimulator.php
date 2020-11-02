<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Frontend\Middleware;

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
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Middleware for handling preview settings
 * used when simulating / previewing pages or content through query params when
 * previewing access or time restricted content via for example backend preview links
 */
class PreviewSimulator implements MiddlewareInterface
{
    /**
     * @var \TYPO3\CMS\Core\Context\Context
     */
    private $context;

    public function __construct()
    {
        $this->context = GeneralUtility::makeInstance(Context::class);
    }

    /**
     * Evaluates preview settings if a backend user is logged in
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ((bool)$this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false)) {
            $simulatingDate = $this->simulateDate($request);
            $simulatingGroup = $this->simulateUserGroup($request);
            $showHiddenRecords = ($this->context->hasAspect('visibility') ? $this->context->getAspect('visibility')->includeHidden() : false);
            $GLOBALS['TSFE']->fePreview = ($simulatingDate || $simulatingGroup || $showHiddenRecords);
        }

        return $handler->handle($request);
    }

    /**
     * Simulate dates for preview functionality
     * When previewing a time restricted page from the backend, the parameter ADMCMD_simTime it added containing
     * a timestamp with the time to preview. The globals 'SIM_EXEC_TIME' and 'SIM_ACCESS_TIME' and the 'DateTimeAspect'
     * are used to simulate rendering at that point in time.
     * Ideally the global access is removed in future versions.
     * This functionality needs to be loaded after BackendAuthenticator as it is only relevant for
     * logged in backend users and needs to be done before any page resolving starts.
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function simulateDate(ServerRequestInterface $request): bool
    {
        $simulatedDate = null;
        $queryTime = $request->getQueryParams()['ADMCMD_simTime'] ?? false;
        if (!$queryTime) {
            return false;
        }

        $simulatedDate = new \DateTimeImmutable('@' . $queryTime);
        if (!$simulatedDate) {
            return false;
        }

        $GLOBALS['SIM_EXEC_TIME'] = $queryTime;
        $GLOBALS['SIM_ACCESS_TIME'] = $queryTime - $queryTime % 60;
        $this->context->setAspect(
            'date',
            GeneralUtility::makeInstance(
                DateTimeAspect::class,
                $simulatedDate
            )
        );
        return true;
    }

    /**
     * Simulate user group for preview functionality
     * When previewing a page with a usergroup restriction, the parameter ADMCMD_simUser = <groupId> will be added
     * to the preview url. Simulation happens.
     * legacy: via TSFE member variables (->fe_user->user[<groupColumn>])
     * new: via Context::UserAspect
     * This functionality needs to be loaded after BackendAuthenticator as it is only relevant for
     * logged in backend users and needs to be done before any page resolving starts.
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function simulateUserGroup(ServerRequestInterface $request): bool
    {
        $simulateUserGroup = (int)($request->getQueryParams()['ADMCMD_simUser'] ?? 0);
        if (!$simulateUserGroup) {
            return false;
        }

        $frontendUser = $GLOBALS['TSFE']->fe_user;
        $frontendUser->user[$frontendUser->usergroup_column] = $simulateUserGroup;
        // let's fake having a user with that group, too
        $frontendUser->user['uid'] = PHP_INT_MAX;
        $this->context->setAspect(
            'frontend.user',
            GeneralUtility::makeInstance(
                UserAspect::class,
                $frontendUser,
                [$simulateUserGroup]
            )
        );
        return true;
    }
}
