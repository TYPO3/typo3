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

namespace TYPO3\CMS\Backend\Command;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Backend\Authentication\PasswordReset;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Triggers the workflow to request a new password for a user.
 */
class ResetPasswordCommand extends Command
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var PasswordReset
     */
    private $passwordReset;

    public function __construct(Context $context, PasswordReset $passwordReset)
    {
        $this->context = $context;
        $this->passwordReset = $passwordReset;
        parent::__construct();
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this
            ->addArgument(
                'backendurl',
                InputArgument::REQUIRED,
                'The URL of the TYPO3 Backend, e.g. https://www.example.com/typo3/'
            )->addArgument(
                'email',
                InputArgument::REQUIRED,
                'The email address of a valid backend user'
            );
    }
    /**
     * Executes the command for sending out an email to reset the password.
     *
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $email = is_string($email) ? $email : '';
        if (!GeneralUtility::validEmail($email)) {
            $io->error('The given email "' . $email . '" is not a valid email address.');
            return 1;
        }
        $backendUrl = $input->getArgument('backendurl');
        $backendUrl = is_string($backendUrl) ? $backendUrl : '';
        if (!GeneralUtility::isValidUrl($backendUrl)) {
            $io->error('The given backend URL "' . $backendUrl . '" is not a valid URL.');
            return 1;
        }
        $request = $this->createFakeWebRequest($backendUrl);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $this->passwordReset->initiateReset($request, $this->context, $email);
        $io->success('Password reset for email address "' . $email . '" initiated.');
        return 0;
    }

    /**
     * This is needed to create a link to the backend properly.
     *
     * @param string $backendUrl
     * @return ServerRequestInterface
     */
    protected function createFakeWebRequest(string $backendUrl): ServerRequestInterface
    {
        $uri = new Uri($backendUrl);
        $request = new ServerRequest(
            $uri,
            'GET',
            'php://input',
            [],
            [
                'HTTP_HOST' => $uri->getHost(),
                'SERVER_NAME' => $uri->getHost(),
                'HTTPS' => $uri->getScheme() === 'https',
                'SCRIPT_FILENAME' => __FILE__,
                'SCRIPT_NAME' => rtrim($uri->getPath(), '/') . '/',
            ]
        );
        $backedUpEnvironment = $this->simulateEnvironmentForBackendEntryPoint();
        $normalizedParams = NormalizedParams::createFromRequest($request);

        // Restore the environment
        Environment::initialize(
            Environment::getContext(),
            Environment::isCli(),
            Environment::isComposerMode(),
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            $backedUpEnvironment['currentScript'],
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );

        return $request
            ->withAttribute('normalizedParams', $normalizedParams)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }

    /**
     * This is a workaround to use "PublicPath . /typo3/index.php" instead of "publicPath . /typo3/sysext/core/bin/typo3"
     * so the the web root is detected properly in normalizedParams.
     */
    protected function simulateEnvironmentForBackendEntryPoint(): array
    {
        $currentEnvironment = Environment::toArray();
        Environment::initialize(
            Environment::getContext(),
            Environment::isCli(),
            Environment::isComposerMode(),
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            // This is ugly, as this change fakes the directory
            dirname(Environment::getCurrentScript(), 4) . DIRECTORY_SEPARATOR . 'index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        return $currentEnvironment;
    }
}
