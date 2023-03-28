.. include:: /Includes.rst.txt

.. _feature-99803-1675373908:

===========================================================
Feature: #99803 - New PSR-14 BeforeRedirectMatchDomainEvent
===========================================================

See :issue:`99803`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Redirects\Event\BeforeRedirectMatchDomainEvent`
is introduced to the :php:`\TYPO3\CMS\Redirects\Service\RedirectService`, allowing extension authors to implement a
custom redirect matching upon the loaded redirects or return matched redirect
record from other sources.

This event features following methods:

-   :php:`getDomain()`: Returns the domain for which redirects should be
    checked for, "*" for all domains.
-   :php:`getPath()`: Returns the path which should be checked.
-   :php:`getQuery()`: Returns the query part which should be checked.
-   :php:`getMatchDomainName()`: Returns current check domain name.
-   :php:`getMatchedRedirect()`: Returns the matched :sql:`sys_redirect` record,
    set by another event listener or null.
-   :php:`setMatchedRedirect()`: Can be used to clear prior matched redirect
    by setting it to :php:`null` or set a matched :sql:`sys_redirect` record.

..  note::

    Full :sql:`sys_redirect` record must be set using `setMatchedRedirect()` method.
    Otherwise later Core code would fail, as it expects, for example, the uid of the record
    to set the `X-Redirect-By` response header. Therefore, the `getMatchedRedirect()`
    method returns null or a full :sql:`sys_redirect` record.

..  note::

    The :php:`BeforeRedirectMatchDomainEvent` is dispatched before cached redirects
    are retrieved. That means, that the event does not contain any :sql:`sys_redirect`
    records. Internal redirect cache may vanish eventually if possible. Therefore,
    it is left out to avoid a longer bound state to the event by properly deprecate it.

Example:
--------

Registration of the event listener:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\Redirects\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-extension/before-redirect-match-domain'

The corresponding event listener class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Redirects/MyEventListener.php

    namespace MyVendor\MyExtension\Redirects;

    use TYPO3\CMS\Backend\Utility\BackendUtility;
    use TYPO3\CMS\Redirects\Event\BeforeRedirectMatchDomainEvent;

    final class MyEventListener
    {
        public function __invoke(BeforeRedirectMatchDomainEvent $event): void
        {
            $matchedRedirectRecord = $this->customRedirectMatching($event);
            if ($matchedRedirectRecord !== null) {
                $event->setMatchedRedirect($matchedRedirectRecord);
            }
        }

        private function customRedirectMatching(
            BeforeRedirectMatchDomainEvent $event
        ): ?array {

            // @todo Implement custom redirect record loading and matching. If
            //       a redirect based on custom logic is determined, return the
            //       :sql:`sys_redirect` tables conform redirect record.

            // Note: Below is simplified example code with no real value.
            $record = BackendUtility::getRecord('sys_redirect', 123);

            // Do custom matching logic against the record and return matched
            // record - if there is one.
            if ($record
                && /* custom condition against the record */
            ) {
                return $record;
            }

            // return null to indicate that no matched redirect could be found
            return null;
        }
    }

Impact
======

With the new :php:`BeforeRedirectMatchDomainEvent` it is now possible to
implement custom redirect matching methods before core matching is processed.

.. index:: PHP-API, ext:redirects
