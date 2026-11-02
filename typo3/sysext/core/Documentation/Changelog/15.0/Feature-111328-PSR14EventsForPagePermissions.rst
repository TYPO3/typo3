.. include:: /Includes.rst.txt

.. _feature-111328-1793598679:

===============================================================
Feature: #111328 - Add PSR-14 events to modify page permissions
===============================================================

See :issue:`111328`

Description
===========

Two new PSR-14 events allow extensions to change the page permissions of
backend users, for example to apply access control lists.

:php:`\TYPO3\CMS\Core\Authentication\Event\ModifyPagePermissionsEvent`
    Grants or revokes the permissions of a user on a single page, after the
    page has been found within a webmount of the user. The event provides the
    page record, with the fields the caller loaded only, and the permissions
    as bitmask of :php:`\TYPO3\CMS\Core\Type\Bitmask\Permission`, which can be
    changed with :php:`setPermissions()`.

:php:`\TYPO3\CMS\Core\Authentication\Event\ModifyPagePermissionConstraintEvent`
    Changes the constraint selecting the pages a user has the given
    permissions for, used by listings such as the page tree. Instead of SQL
    strings, listeners build constraints with the expression builder of the
    event, :php:`getExpressionBuilder()`, and refer to the pages table by
    :php:`getTableAlias()`. :php:`addConstraint()` restricts the selected
    pages further, :php:`setConstraint()` replaces the constraint, for example
    to grant pages in addition with an :php:`or()` of
    :php:`getConstraint()` and a constraint of the listener.

Both events provide the uid, the group uids and the workspace of the user.
They are not dispatched for administrators, who hold all permissions.

A listener of the constraint event must decide in the same way as a listener of
the permissions event, so that listings and checks of single pages agree.
While a listener of the constraint event is registered, the check whether a page
is within the webmounts of a user selects the pages up to the webmount with the
constraint. Whether a single page is shown to a user, as
:php:`\TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess()` checks it,
is decided by the constraint event only.

..  important::
    The permission checks of the :php:`DataHandler` do not dispatch
    :php:`ModifyPagePermissionsEvent` yet, except for the subpages of a page
    deleted recursively, as they did not call the :php:`calcPerms` hook
    either. A listener revoking a permission hides the action in the backend,
    but does not keep the :php:`DataHandler` from executing it. Listeners of
    :php:`ModifyPagePermissionConstraintEvent` restrict the :php:`DataHandler`
    through its check of the webmounts.

    :php:`\TYPO3\CMS\Core\Database\Query\Restriction\PagePermissionRestriction`
    dispatches neither event.

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/RestrictedPagesListener.php

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Backend\Utility\BackendUtility;
    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Authentication\Event\ModifyPagePermissionConstraintEvent;
    use TYPO3\CMS\Core\Authentication\Event\ModifyPagePermissionsEvent;
    use TYPO3\CMS\Core\Type\Bitmask\Permission;

    final class RestrictedPagesListener
    {
        private const int TRAINEES = 42;

        #[AsEventListener]
        public function restrictPage(ModifyPagePermissionsEvent $event): void
        {
            if (in_array(self::TRAINEES, $event->getGroupIds(), true)
                && $this->isRestricted($event->getPageRecord())
            ) {
                $event->setPermissions(Permission::NOTHING);
            }
        }

        #[AsEventListener]
        public function restrictListings(ModifyPagePermissionConstraintEvent $event): void
        {
            if (in_array(self::TRAINEES, $event->getGroupIds(), true)) {
                $event->addConstraint($event->getExpressionBuilder()->eq(
                    $event->getTableAlias() . '.tx_myextension_restricted',
                    0
                ));
            }
        }

        private function isRestricted(array $pageRecord): bool
        {
            // The record holds the fields the caller loaded only
            $pageRecord['tx_myextension_restricted'] ??= BackendUtility::getRecord(
                'pages',
                (int)$pageRecord['uid'],
                'tx_myextension_restricted'
            )['tx_myextension_restricted'] ?? 1;
            return (bool)$pageRecord['tx_myextension_restricted'];
        }
    }

Impact
======

Extensions can change page permissions with PSR-14 events, which replace the
deprecated hooks :php:`getPagePermsClause` and :php:`calcPerms`.

In :php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication`,
:php:`calcPerms()` no longer calls :php:`isInWebMount()`, and
:php:`isInWebMount()` no longer calls :php:`getPagePermsClause()` while a
:php:`getPagePermsClause` hook is registered. A subclass or XCLASS overriding
one of these methods does not change the result of the others anymore; use the
events instead.

..  seealso::
    :ref:`deprecation-111328-1793598679`

.. index:: Backend, PHP-API, ext:core
