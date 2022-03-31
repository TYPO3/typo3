.. include:: /Includes.rst.txt

======================================
Feature: #90471 - JavaScript Event API
======================================

See :issue:`90471`

Description
===========

A new Event API enables JavaScript developers to have a stable event listening
interface. The API takes care of common pitfalls like event delegation and clean
event unbinding.


Impact
======

Event Binding
-------------

Each event strategy (see below) has two ways to bind a listener to an event:

Direct Binding
^^^^^^^^^^^^^^

The event listener is bound to the element that triggers the event. This is done
by using the method :js:`bindTo()`, which accepts any element, :js:`document` and
:js:`window`.

Example:

.. code-block:: js

   require(['TYPO3/CMS/Core/Event/RegularEvent'], function (RegularEvent) {
     new RegularEvent('click', function (e) {
       // Do something
     }).bindTo(document.querySelector('#my-element'));
   });


Event Delegation
^^^^^^^^^^^^^^^^

The event listener is called if the event was triggered to any matching element
inside its bound element.

Example:

.. code-block:: js

   require(['TYPO3/CMS/Core/Event/RegularEvent'], function (RegularEvent) {
     new RegularEvent('click', function (e) {
       // Do something
     }).delegateTo(document, 'a[data-action="toggle"]');
   });

The event listener is now called every time the element matching the selector
:js:`a[data-action="toggle"]` within :js:`document` is clicked.


Release an event
^^^^^^^^^^^^^^^^

Since each event is an object instance, it's sufficient to call :js:`release()` to
detach the event listener.

Example:

.. code-block:: js

   require(['TYPO3/CMS/Core/Event/RegularEvent'], function (RegularEvent) {
     const clickEvent = new RegularEvent('click', function (e) {
       // Do something
     }).delegateTo(document, 'a[data-action="toggle"]');

     // Do more stuff

     clickEvent.release();
   });


Event Strategies
----------------

The Event API brings several strategies to handle event listeners:

RegularEvent
^^^^^^^^^^^^

The :js:`RegularEvent` attaches a simple event listener to an event and element
and has no further tweaks. This is the common use-case for event handling.

Arguments:

* :js:`eventName` (string) - the event to listen on
* :js:`callback` (function) - the event listener

Example:

.. code-block:: js

   require(['TYPO3/CMS/Core/Event/RegularEvent'], function (RegularEvent) {
     new RegularEvent('click', function (e) {
       e.preventDefault();
       window.location.reload();
     }).bindTo(document.querySelector('#my-element'));
   });


DebounceEvent
^^^^^^^^^^^^^

The :js:`DebounceEvent` is most suitable if an event is triggered rather often
but executing the event listener may called only once after a certain wait time.

Arguments:

* :js:`eventName` (string) - the event to listen on
* :js:`callback` (function) - the event listener
* :js:`wait` (number) - the amount of milliseconds to wait before the event listener is called
* :js:`immediate` (boolean) - if true, the event listener is called right when the event started

Example:

.. code-block:: js

   require(['TYPO3/CMS/Core/Event/DebounceEvent'], function (DebounceEvent) {
     new DebounceEvent('mousewheel', function (e) {
       console.log('Triggered once after 250ms!');
     }, 250).bindTo(document);
   });


ThrottleEvent
^^^^^^^^^^^^^

Arguments:

* :js:`eventName` (string) - the event to listen on
* :js:`callback` (function) - the event listener
* :js:`limit` (number) - the amount of milliseconds to wait before the event listener is called

The :js:`ThrottleEvent` is similar to the :js:`DebounceEvent`. The important
difference is that the event listener is called after the configured wait time
during the overall event time.

If an event time is about 2000ms and the wait time is configured to be 100ms,
the event listener gets called up to 20 times in total (2000 / 100).

Example:

.. code-block:: js

   require(['TYPO3/CMS/Core/Event/ThrottleEvent'], function (ThrottleEvent) {
     new ThrottleEvent('mousewheel', function (e) {
       console.log('Triggered every 100ms!');
     }, 100).bindTo(document);
   });


RequestAnimationFrameEvent
^^^^^^^^^^^^^^^^^^^^^^^^^^

The :js:`RequestAnimationFrameEvent` binds its execution to the browser's
:js:`RequestAnimationFrame` API. It is suitable for event listeners that
manipulate the DOM.

Arguments:

* :js:`eventName` (string) - the event to listen on
* :js:`callback` (function) - the event listener

Example:

.. code-block:: js

   require(['TYPO3/CMS/Core/Event/RequestAnimationFrameEvent'], function (RequestAnimationFrameEvent) {
     new RequestAnimationFrameEvent('mousewheel', function (e) {
       console.log('Triggered every 16ms (= 60 FPS)!');
     });
   });


.. index:: JavaScript, ext:core
