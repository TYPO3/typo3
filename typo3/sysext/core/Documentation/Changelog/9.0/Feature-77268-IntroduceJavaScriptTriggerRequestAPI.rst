.. include:: /Includes.rst.txt

==========================================================
Feature: #77268 - Introduce JavaScript trigger request API
==========================================================

See :issue:`77268`

Description
===========

JavaScript event handling the backend of the TYPO3 core is based on the optimistic
assumption, that most executions can be executed sequentially and are processed
just in time. This concept does not consider the fact that other nested components
can defer the execution based on additional user input e.g. as used in confirmation
dialogs.

That's why a trigger request API is introduced to first inform dependent components
about a planned action which will defer the regular execution based on specific
application state logic of registered components. In the current implementation,
FormEngine's edit forms register themselves to be notified, thus accidentally
closing modified forms by clicking e.g. the module menu any other page in the
page tree can be handled.

Registering component
~~~~~~~~~~~~~~~~~~~~~

The following code attaches or detaches a particular component (a **consumer**)
to be notified.

.. code-block:: javascript

	// FormEngine must implement the Consumable interface,
        // thus having a function named consume(interactionRequest)
	top.TYPO3.Backend.consumerScope.attach(FormEngine);
	top.TYPO3.Backend.consumerScope.detach(FormEngine);

Invoking consumers
~~~~~~~~~~~~~~~~~~

Registered consumers are invoked with a specific interaction request that has a
defined action type and optionally additional information about the parent call
(e.g. some client event issued by users). Invocations return a jQuery.Deferred()
object that resolves when no consumers are registered or every consumer sends a
resolve command as well - if only one consumer rejects, the collective invocation
promise is rejected as well.

.. code-block:: javascript

	var deferred = TYPO3.Backend.consumerScope.invoke(
		new TriggerRequest('typo3.setUrl', interactionRequest)
	);
	deferred
		.then(function() { console.log('consumers are resolved'); })
		.fail(function() { console.log('some consumer was rejected'); });

Creating interaction requests
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Currently there are two types of requests, `ClientRequest` that is based on some
client event (e.g. `click` event) and `TriggerRequest` which may be based on some
parent request of type `InteractionRequest` - this is used to cascade actions.

.. code-block:: javascript

   var clickRequest = new ClientRequest('typo3.showModule', event);
   var triggerRequestA = new TriggerRequest('typo3.a', clickRequest);
   var triggerRequestB = new TriggerRequest('typo3.b', triggerRequestA);

In the example `triggerRequestB` has all information from the initial click
event down to the specific `typo3.b` action type. The first request can be
resolved from the most specific request by `triggerRequestB.outerMostRequest`
and will return `clickRequest` in this case.

Working with interaction requests
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

+ `triggerRequestB.concerns(clickRequest)` checks whether `clickRequest` is an
  ancestor request in the cascade of `triggerRequestB` (which is true, based on
  the previous example)
+ `triggerRequestB.concernsType('typo3.showModule')` checks whether `typo3.showModule`
  is the type of some ancestor request in the cascade of `triggerRequestB` (which
  is true, based on the previous example)
+ `triggerRequestB.outerMostRequest.setProcessedData({response: true})` sets the
  property evaluated by `clickRequest.isProcessed()` to `true` and stores any
  custom user response (e.g. from some confirmation dialog) at the outer-most
  interaction request

Impact
======

Using interaction requests requires some modifications in the JavaScript processing
logic which changes from sequential processing to possibly deferred asynchronous
processing. This is required since e.g. user input is required first to be able
to continue the processing. The created promises are based on `jQuery.Deferred`.

.. index:: Backend, JavaScript
