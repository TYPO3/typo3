..  include:: /Includes.rst.txt

..  _deprecation-109171-1741254000:

===========================================
Deprecation: #109171 - Bootstrap tab events
===========================================

See :issue:`109171`

Description
===========

Bootstrap's tab JavaScript has been replaced with a custom implementation
tailored to TYPO3. The Bootstrap tab events :js:`show.bs.tab` and
:js:`shown.bs.tab` are now deprecated and will be removed in TYPO3 v15.

The following new custom events are available as replacements:

*   :js:`typo3:tab:show` — dispatched before a tab switch, cancelable via
    :js:`event.preventDefault()`
*   :js:`typo3:tab:shown` — dispatched after a tab switch

Both events bubble from the tab button and carry a
:js:`detail.relatedTarget` property that points to the previously active tab
button or :js:`null`.

Impact
======

Listening for :js:`show.bs.tab` or :js:`shown.bs.tab` events will continue to
work in TYPO3 v14 but will stop working in TYPO3 v15, when the backward-
compatibility events will be removed.

Affected installations
======================

All extensions that listen to :js:`show.bs.tab` or :js:`shown.bs.tab` events
on tab buttons are affected.

Migration
=========

Replace Bootstrap tab event listeners with the new TYPO3 tab events.

Before:

..  code-block:: js

    document.addEventListener('show.bs.tab', (e) => {
      console.log(
        'Tab is about to show',
        e.target,
        e.detail.relatedTarget
      );
    });

    document.addEventListener('shown.bs.tab', (e) => {
      console.log(
        'Tab was shown',
        e.target,
        e.detail.relatedTarget
      );
    });

After:

..  code-block:: js

    document.addEventListener('typo3:tab:show', (e) => {
      console.log('Tab is about to show', e.target, e.detail.relatedTarget);
    });

    document.addEventListener('typo3:tab:shown', (e) => {
      console.log('Tab was shown', e.target, e.detail.relatedTarget);
    });

..  index:: Backend, JavaScript, NotScanned, ext:backend
