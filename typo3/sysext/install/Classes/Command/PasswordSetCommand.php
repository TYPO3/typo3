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
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Exception\InvalidPasswordRulesException;

final class PasswordSetCommand extends Command
{
    public function __construct(
        string $name,
        protected readonly PasswordHashFactory $passwordHashFactory,
        protected readonly ConfigurationManager $configurationManager,
        protected readonly Random $random
    ) {
        parent::__construct($name);
    }

    public function configure(): void
    {
        $this
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
        $dryRun = $input->getOption('dry-run');
        $noInteraction = $input->getOption('no-interaction');

        $password = !$noInteraction
            ? $this
                ->getQuestionHelper()
                ->ask($input, $output, (new Question('Password (leave empty for auto generation): '))->setHidden(true))
            : null;

        if ($password === null) {
            $password = $this->random->generateRandomPassword([]);
            $output->writeln(sprintf('Generated password: <info>%s</info>', $password));
        }

        $passwordHashed = $this->passwordHashFactory->getDefaultHashInstance('BE')->getHashedPassword($password);

        if ($dryRun) {
            $output->writeln(sprintf('Password hashed (dry run): <info>%s</info>', $passwordHashed));
        } else {
            $this
                ->configurationManager
                ->setLocalConfigurationValueByPath('BE/installToolPassword', $passwordHashed);

            $output->writeln('<info>Install Tool password updated.</info>');
        }

        return Command::SUCCESS;
    }

    protected function getQuestionHelper(): QuestionHelper
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        return $helper;
    }
}
