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
use TYPO3\CMS\Core\Exception\InvalidPasswordRulesException;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\PasswordPolicy\Generator\PasswordGeneratorInterface;
use TYPO3\CMS\Core\PasswordPolicy\PasswordService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class PasswordSetCommand extends Command
{
    public function __construct(
        string $name,
        private readonly PasswordHashFactory $passwordHashFactory,
        private readonly ConfigurationManager $configurationManager,
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
                InputOption::VALUE_OPTIONAL,
                'Specify the length of auto-generated passwords.',
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

        $password = !$noInteraction
            ? $this
                ->getQuestionHelper()
                ->ask($input, $output, (new Question('Password (leave empty for auto generation): '))->setHidden(true))
            : null;

        if ($password === null) {
            $generator = $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']['installTool']['generator'] ?? null;
            if (!class_exists($generator['className'] ?? '') || !is_array($generator['options'] ?? null)) {
                throw new \LogicException(
                    'The TYPO3_CONF_VARS.SYS.passwordPolicies.installTool.generator configuration is misconfigured.'
                    . ' Please ensure that the sub key \'className\' is set, and the sub key \'options\' is an array of required option values.',
                    1770131006
                );
            }

            $passwordGeneratorClassName = $generator['className'];
            $passwordGeneratorOptions = $generator['options'];

            $passwordGenerator = GeneralUtility::makeInstance($passwordGeneratorClassName);
            if (!$passwordGenerator instanceof PasswordGeneratorInterface) {
                throw new \LogicException('Class ' . $passwordGeneratorClassName . ' does not implement PasswordGeneratorInterface', 1770131293);
            }

            if ($input->getOption('password-length') !== null) {
                $passwordGeneratorOptions['length'] = (int)$input->getOption('password-length');
            }
            $length = $passwordGeneratorOptions['length'];
            $password = $passwordGenerator->generate($passwordGeneratorOptions);
            $output->writeln(sprintf('Password length: %d characters', $length));
            // A generated password may contain '<', '>' or '\', which Symfony's
            // OutputFormatter would silently mangle on display. Emit the password via
            // OUTPUT_RAW so it bypasses the formatter entirely, and apply the 'info'
            // style manually when decoration is on to keep the green highlight.
            $coloredPassword = $output->isDecorated()
                ? $output->getFormatter()->getStyle('info')->apply($password)
                : $password;
            $output->write('Generated password: ');
            $output->writeln($coloredPassword, OutputInterface::OUTPUT_RAW);
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
