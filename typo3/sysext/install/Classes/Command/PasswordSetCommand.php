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

namespace TYPO3\CMS\Install\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Exception\InvalidPasswordRulesException;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\PasswordPolicy\PasswordService;

final class PasswordSetCommand extends Command
{
    public function __construct(
        string $name,
        private readonly PasswordHashFactory $passwordHashFactory,
        private readonly ConfigurationManager $configurationManager,
        private readonly Random $random,
        private readonly LanguageServiceFactory $languageServiceFactory,
        private readonly PasswordService $passwordService,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'password-length',
                'p',
                InputOption::VALUE_REQUIRED,
                'Specify the length of auto-generated passwords.',
                8,
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'If this option is set, the password would only be shown but not saved in settings. This also reveals the resulting hash.'
            );
    }

    /**
     * Creates an install-tool password (either auto-generated or manual) and stores the hash (if not --dry-run)
     *
     * @throws InvalidPasswordHashException
     * @throws InvalidPasswordRulesException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!Bootstrap::checkIfEssentialConfigurationExists($this->configurationManager)) {
            $io->error('Setting an Install Tool password requires a working installation (configuration files are missing).');
            return Command::FAILURE;
        }

        $currentSettingsWithoutAdditionalParsing = $this->configurationManager->getMergedLocalConfiguration();
        if (($currentSettingsWithoutAdditionalParsing['BE']['installToolPassword'] ?? '') !== ($GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] ?? '')) {
            $io->error('Your Install Tool password is different in settings.php and additional.php. This command can only effectively change the password for "settings.php" and therefore any changes would not take effect.');
            return Command::FAILURE;
        }

        $dryRun = $input->getOption('dry-run');
        $noInteraction = $input->getOption('no-interaction');
        $length = (int)$input->getOption('password-length');

        $password = !$noInteraction
            ? $this
                ->getQuestionHelper()
                ->ask($input, $output, (new Question('Password (leave empty for auto generation): '))->setHidden(true))
            : null;

        if ($password === null) {
            // Password length is defined in the method itself as a proper fallback
            $password = $this->random->generateRandomPassword([
                'specialCharacters' => true,
                'lowerCaseCharacters' => true,
                'digitCharacters' => true,
                'upperCaseCharacters' => true,
                'length' => $length,
            ]);
            $output->writeln(sprintf('Password length: %d characters', $length));
            $output->writeln(sprintf('Generated password: <info>%s</info>', $password));
        }

        // Validation error messages require a valid LANG object to operate on (or a backend user context, which we don't need here)
        $GLOBALS['LANG'] = $this->languageServiceFactory->create('en');
        $validationResultErrors = $this->passwordService->getValidationErrorsForInstallToolUpdate($password);
        if ($validationResultErrors !== []) {
            $output->writeln('Your password could not be used. The following validation rules did not pass:');
            foreach ($validationResultErrors as $validatorKey => $message) {
                $output->writeln(sprintf(' - <error>%s</error> (%s)', $message, $validatorKey));
            }
            return Command::FAILURE;
        }

        $passwordHashed = $this->passwordHashFactory->getDefaultHashInstance('BE')->getHashedPassword($password);

        if ($dryRun) {
            $output->writeln(sprintf('Password hashed (dry run): <info>%s</info>', $passwordHashed));
        } else {
            $this
                ->configurationManager
                ->setLocalConfigurationValueByPath('BE/installToolPassword', $passwordHashed);

            $output->writeln('<info>Install Tool password updated in "settings.php".</info>');
            $output->writeln('<comment>Please note that a custom override of this password in "additional.php" will have higher priority.</comment>');
        }

        return Command::SUCCESS;
    }

    private function getQuestionHelper(): QuestionHelper
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        return $helper;
    }
}
