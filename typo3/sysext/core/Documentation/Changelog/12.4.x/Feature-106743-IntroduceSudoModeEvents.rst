..  include:: /Includes.rst.txt

..  _feature-106743-1747931468:

=============================================
Feature: #106743 - Introduce Sudo-Mode Events
=============================================

See :issue:`106743`

Description
===========

The fix for the security advisory `TYPO3-CORE-SA-2025-013 <https://typo3.org/security/advisory/typo3-core-sa-2025-013>`_
requires step-up authentication when attempting to manipulate backend user accounts.
However, this behavior may pose challenges when integrating remote single sign-on (SSO)
providers, as these typically do not support a dedicated step-up authentication process.

To address this, new PSR-14 events have been introduced:
* :php:`TYPO3\CMS\Backend\Security\SudoMode\Event\SudoModeRequiredEvent` is triggered before
  showing the sudo-mode verification dialog
* :php:`TYPO3\CMS\Backend\Security\SudoMode\Event\SudoModeVerifyEvent` is triggered before
  actually verifying the submitted password

This event allows developers to conditionally bypass and adjust the step-up authentication
process based on custom logic, such as identifying users authenticated through an SSO system.

Example
-------

The following example demonstrates how to use an event listener to skip the step-up authentication
for persisted `be_users` records with an active `is_sso` flag:


..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    services:
      Vendor\MyExtension\EventListener\SkipSudoModeDialog:
        tags:
          - name: event.listener
            identifier: 'ext-myextension/skip-sudo-mode-dialog'
      Vendor\MyExtension\EventListener\StaticPasswordVerification:
        tags:
          - name: event.listener
            identifier: 'ext-myextension/static-password-verification'

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/SkipSudoModeDialog.php

    <?php
    declare(strict_types=1);

    namespace Vendor\MyExtension\EventListener;

    use TYPO3\CMS\Backend\Hooks\DataHandlerAuthenticationContext;
    use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessSubjectInterface;
    use TYPO3\CMS\Backend\Security\SudoMode\Access\TableAccessSubject;
    use TYPO3\CMS\Backend\Security\SudoMode\Event\SudoModeRequiredEvent;
    use TYPO3\CMS\Backend\Utility\BackendUtility;
    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Utility\MathUtility;

    final class SkipSudoModeDialog
    {
        public function __invoke(SudoModeRequiredEvent $event): void
        {
            // Ensure the event context matches DataHandler operations
            if ($event->getClaim()->origin !== DataHandlerAuthenticationContext::class) {
                return;
            }

            // Filter for TableAccessSubject types only
            $tableAccessSubjects = array_filter(
                $event->getClaim()->subjects,
                static fn (AccessSubjectInterface $subject): bool => $subject instanceof TableAccessSubject
            );

            // Abort if there are unhandled subject types
            if ($event->getClaim()->subjects !== $tableAccessSubjects) {
                return;
            }

            /** @var list<TableAccessSubject> $tableAccessSubjects */
            foreach ($tableAccessSubjects as $subject) {
                // Expecting format: tableName.fieldName.id
                if (substr_count($subject->getSubject(), '.') !== 2) {
                    return;
                }

                [$tableName, $fieldName, $id] = explode('.', $subject->getSubject());

                // Only handle be_users table
                if ($tableName !== 'be_users') {
                    return;
                }

                // Skip if ID is not a valid integer (e.g., 'NEW' records)
                if (!MathUtility::canBeInterpretedAsInteger($id)) {
                    continue;
                }

                $record = BackendUtility::getRecord($tableName, $id);

                // Abort if any record does not use SSO
                if (empty($record['is_sso'])) {
                    return;
                }
            }

            // All conditions met — disable verification
            $event->setVerificationRequired(false);
        }
    }

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/StaticPasswordVerification.php

    <?php
    declare(strict_types=1);

    namespace Example\Demo\EventListener;

    use TYPO3\CMS\Backend\Security\SudoMode\Event\SudoModeVerifyEvent;

    final class StaticPasswordVerification
    {
        public function __invoke(SudoModeVerifyEvent $event): void
        {
            $calculatedHash = hash('sha256', $event->getPassword());
            // static hash of `dontdothis` - just used as proof-of-concept
            // side-note: in production, make use of strong salted password
            $expectedHash = '3382f2e21a5471b52a85bc32ab59ab2c467f6e3cb112aef295323874f423994c';

            if (hash_equals($expectedHash, $calculatedHash)) {
                $event->setVerified(true);
            }
        }
    }


Impact
======

This feature provides extension developers with a flexible mechanism to skip or adjust
step-up authentication during sensitive backend operations. It is especially useful in
environments utilizing SSO, where enforcing additional verification might not be feasible
or necessary. By hooking into the new :php:`SudoModeRequiredEvent` and :php:`SudoModeVerifyEvent`
custom logic and behavior can be applied on a case-by-case basis.

..  index:: Backend, ext:backend
