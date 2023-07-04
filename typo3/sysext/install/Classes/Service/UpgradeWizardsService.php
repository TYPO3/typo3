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

namespace TYPO3\CMS\Install\Service;

use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\StreamOutput;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\ConfirmableInterface;
use TYPO3\CMS\Install\Updates\RepeatableInterface;
use TYPO3\CMS\Install\Updates\RowUpdater\RowUpdaterInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardRegistry;

/**
 * Service class helps to manage upgrade wizards.
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
final class UpgradeWizardsService
{
    private StreamOutput $output;

    public function __construct(
        private readonly UpgradeWizardRegistry $upgradeWizardRegistry,
        private readonly Registry $registry,
    ) {
        $fileName = 'php://temp';
        if (($stream = fopen($fileName, 'wb')) === false) {
            throw new \RuntimeException('Unable to open stream "' . $fileName . '"', 1598341765);
        }
        $this->output = new StreamOutput($stream, Output::VERBOSITY_NORMAL, false);
    }

    /**
     * @return array List of wizards marked as done in registry
     */
    public function listOfWizardsDone(): array
    {
        $wizardsDoneInRegistry = [];
        foreach ($this->upgradeWizardRegistry->getUpgradeWizards() as $identifier => $serviceName) {
            if ($this->registry->get('installUpdate', $serviceName, false)) {
                $wizardsDoneInRegistry[] = [
                    'class' => $serviceName,
                    'identifier' => $identifier,
                    // @todo fetching the service to get the title should be improved
                    'title' => $this->upgradeWizardRegistry->getUpgradeWizard($identifier)->getTitle(),
                ];
            }
        }
        return $wizardsDoneInRegistry;
    }

    /**
     * @return array List of row updaters marked as done in registry
     * @throws \RuntimeException
     */
    public function listOfRowUpdatersDone(): array
    {
        $rowUpdatersDoneClassNames = $this->registry->get('installUpdateRows', 'rowUpdatersDone', []);
        $rowUpdatersDone = [];
        foreach ($rowUpdatersDoneClassNames as $rowUpdaterClassName) {
            // Silently skip non-existing DatabaseRowsUpdateWizards
            if (!class_exists($rowUpdaterClassName)) {
                continue;
            }
            /** @var RowUpdaterInterface $rowUpdater */
            $rowUpdater = GeneralUtility::makeInstance($rowUpdaterClassName);
            if (!$rowUpdater instanceof RowUpdaterInterface) {
                throw new \RuntimeException(
                    'Row updater must implement RowUpdaterInterface',
                    1484152906
                );
            }
            $rowUpdatersDone[] = [
                'class' => $rowUpdaterClassName,
                'identifier' => $rowUpdaterClassName,
                'title' => $rowUpdater->getTitle(),
            ];
        }
        return $rowUpdatersDone;
    }

    /**
     * Mark one wizard as undone. This can be a "casual" wizard
     * or a single "row updater".
     *
     * @param string $identifier Wizard or RowUpdater identifier
     * @return bool True if wizard has been marked as undone
     * @throws \RuntimeException
     */
    public function markWizardUndone(string $identifier): bool
    {
        $this->assertIdentifierIsValid($identifier);

        $aWizardHasBeenMarkedUndone = false;
        foreach ($this->listOfWizardsDone() as $wizard) {
            if ($wizard['identifier'] === $identifier) {
                $aWizardHasBeenMarkedUndone = true;
                $this->registry->set('installUpdate', $wizard['class'], 0);
            }
        }
        if (!$aWizardHasBeenMarkedUndone) {
            $rowUpdatersDoneList = $this->listOfRowUpdatersDone();
            $registryArray = $this->registry->get('installUpdateRows', 'rowUpdatersDone', []);
            foreach ($rowUpdatersDoneList as $rowUpdater) {
                if ($rowUpdater['identifier'] === $identifier) {
                    $aWizardHasBeenMarkedUndone = true;
                    foreach ($registryArray as $rowUpdaterMarkedAsDonePosition => $rowUpdaterMarkedAsDone) {
                        if ($rowUpdaterMarkedAsDone === $rowUpdater['class']) {
                            unset($registryArray[$rowUpdaterMarkedAsDonePosition]);
                            break;
                        }
                    }
                    $this->registry->set('installUpdateRows', 'rowUpdatersDone', $registryArray);
                }
            }
        }
        return $aWizardHasBeenMarkedUndone;
    }

    /**
     * Get list of registered upgrade wizards not marked done.
     *
     * @return array List of upgrade wizards in correct order with detail information
     */
    public function getUpgradeWizardsList(): array
    {
        $wizards = [];
        foreach (array_keys($this->upgradeWizardRegistry->getUpgradeWizards()) as $identifier) {
            if ($this->isWizardDone($identifier)) {
                continue;
            }

            $wizards[] = $this->getWizardInformationByIdentifier($identifier);
        }
        return $wizards;
    }

    public function getWizardInformationByIdentifier(string $identifier): array
    {
        $this->assertIdentifierIsValid($identifier);

        if (is_subclass_of($identifier, RowUpdaterInterface::class)) {
            return [
                'class' => $identifier,
                'identifier' => $identifier,
                'title' => $identifier,
                'shouldRenderWizard' => false,
                'explanation' => '',
            ];
        }

        $wizard = $this->upgradeWizardRegistry->getUpgradeWizard($identifier);

        if ($wizard instanceof ChattyInterface) {
            $wizard->setOutput($this->output);
        }

        return [
            'class' => $wizard::class,
            'identifier' => $identifier,
            'title' => $wizard->getTitle(),
            'shouldRenderWizard' => $wizard->updateNecessary(),
            'explanation' => $wizard->getDescription(),
        ];
    }

    /**
     * Execute the "get user input" step of a wizard
     *
     * @throws \RuntimeException
     */
    public function getWizardUserInput(string $identifier): array
    {
        $this->assertIdentifierIsValid($identifier);

        $wizard = $this->upgradeWizardRegistry->getUpgradeWizard($identifier);
        $wizardHtml = '';
        if ($wizard instanceof ConfirmableInterface) {
            $markup = [];
            $radioAttributes = [
                'type' => 'radio',
                'class' => 'btn-check',
                'name' => 'install[values][' . $identifier . '][install]',
                'value' => '0',
            ];
            $markup[] = '<div class="panel panel-danger">';
            $markup[] = '   <div class="panel-heading">';
            $markup[] = htmlspecialchars($wizard->getConfirmation()->getTitle());
            $markup[] = '    </div>';
            $markup[] = '    <div class="panel-body">';
            $markup[] = '        <p>' . nl2br(htmlspecialchars($wizard->getConfirmation()->getMessage())) . '</p>';
            $markup[] = '        <div class="btn-group">';
            if (!$wizard->getConfirmation()->isRequired()) {
                $denyChecked = $wizard->getConfirmation()->getDefaultValue() === false ? ' checked' : '';
                $markup[] = '        <input ' . GeneralUtility::implodeAttributes($radioAttributes, true) . $denyChecked . ' id="upgrade-wizard-deny">';
                $markup[] = '        <label class="btn btn-default" for="upgrade-wizard-deny">' . $wizard->getConfirmation()->getDeny() . '</label>';
            }
            $radioAttributes['value'] = '1';
            $confirmChecked = $wizard->getConfirmation()->getDefaultValue() === true ? ' checked' : '';
            $markup[] = '            <input ' . GeneralUtility::implodeAttributes($radioAttributes, true) . $confirmChecked . ' id="upgrade-wizard-confirm">';
            $markup[] = '            <label class="btn btn-default" for="upgrade-wizard-confirm">' . $wizard->getConfirmation()->getConfirm() . '</label>';
            $markup[] = '        </div>';
            $markup[] = '    </div>';
            $markup[] = '</div>';
            $wizardHtml = implode('', $markup);
        }

        $result = [
            'identifier' => $identifier,
            'title' => $wizard->getTitle(),
            'description' => $wizard->getDescription(),
            'wizardHtml' => $wizardHtml,
        ];

        return $result;
    }

    /**
     * Execute a single update wizard
     *
     * @throws \RuntimeException
     */
    public function executeWizard(string $identifier, array $values): FlashMessageQueue
    {
        $performResult = false;
        $this->assertIdentifierIsValid($identifier);

        $wizard = $this->upgradeWizardRegistry->getUpgradeWizard($identifier);

        if ($wizard instanceof ChattyInterface) {
            $wizard->setOutput($this->output);
        }
        $messages = new FlashMessageQueue('install');

        if ($wizard instanceof ConfirmableInterface) {
            // value is set in request but is empty
            $isSetButEmpty = isset($values[$identifier]['install']) && empty($values[$identifier]['install']);
            $checkValue = (int)$values[$identifier]['install'];

            if ($checkValue === 1) {
                // confirmation = yes, we do the update
                $performResult = $wizard->executeUpdate();
            } elseif ($wizard->getConfirmation()->isRequired()) {
                // confirmation = no, but is required, we do *not* the update and fail
                $performResult = false;
            } elseif ($isSetButEmpty) {
                // confirmation = no, but it is *not* required, we do *not* the update, but mark the wizard as done
                $this->output->writeln('No changes applied, marking wizard as done.');
                // confirmation was set to "no"
                $performResult = true;
            }
        } else {
            // confirmation yes or non-confirmable
            $performResult = $wizard->executeUpdate();
        }

        $stream = $this->output->getStream();
        rewind($stream);
        if ($performResult) {
            if (!$wizard instanceof RepeatableInterface) {
                // mark wizard as done if it's not repeatable and was successful
                $this->markWizardAsDone($wizard);
            }
            $messages->enqueue(
                new FlashMessage(
                    (string)stream_get_contents($stream),
                    'Update successful'
                )
            );
        } else {
            $messages->enqueue(
                new FlashMessage(
                    (string)stream_get_contents($stream),
                    'Update failed!',
                    ContextualFeedbackSeverity::ERROR
                )
            );
        }
        return $messages;
    }

    /**
     * Marks some wizard as being "seen" so that it not shown again.
     * Writes the info in system/settings.php
     *
     * @throws \RuntimeException
     */
    public function markWizardAsDone(UpgradeWizardInterface $upgradeWizard): void
    {
        $this->registry->set('installUpdate', $upgradeWizard::class, 1);
    }

    /**
     * Checks if this wizard has been "done" before
     *
     * @return bool TRUE if wizard has been done before, FALSE otherwise
     * @throws \RuntimeException
     */
    public function isWizardDone(string $identifier): bool
    {
        $this->assertIdentifierIsValid($identifier);

        return (bool)$this->registry->get(
            'installUpdate',
            $this->upgradeWizardRegistry->getUpgradeWizard($identifier)::class,
            false
        );
    }

    /**
     * Wrapper to catch \UnexpectedValueException for backwards compatibility reasons
     */
    public function getUpgradeWizard(string $identifier): ?UpgradeWizardInterface
    {
        try {
            return $this->upgradeWizardRegistry->getUpgradeWizard($identifier);
        } catch (\UnexpectedValueException) {
            return null;
        }
    }

    public function getUpgradeWizardIdentifiers(): array
    {
        return array_keys($this->upgradeWizardRegistry->getUpgradeWizards());
    }

    public function getNonRepeatableUpgradeWizards(): array
    {
        $nonRepeatableUpgradeWizards = [];
        foreach ($this->upgradeWizardRegistry->getUpgradeWizards() as $identifier => $updateClassName) {
            if (!in_array(RepeatableInterface::class, class_implements($updateClassName) ?: [], true)) {
                $nonRepeatableUpgradeWizards[$identifier] = $updateClassName;
            }
        }
        return $nonRepeatableUpgradeWizards;
    }

    /**
     * Validate identifier exists in upgrade wizard list
     *
     * @throws \RuntimeException
     */
    private function assertIdentifierIsValid(string $identifier): void
    {
        if ($identifier === '') {
            throw new \RuntimeException('Empty upgrade wizard identifier given', 1650579934);
        }
        if (!is_subclass_of($identifier, RowUpdaterInterface::class)
            && !$this->upgradeWizardRegistry->hasUpgradeWizard($identifier)
        ) {
            throw new \RuntimeException(
                'The upgrade wizard identifier "' . $identifier . '" must either be registered as upgrade wizard or it must implement TYPO3\CMS\Install\Updates\RowUpdater\RowUpdaterInterface',
                1650546252
            );
        }
    }
}
