.. include:: /Includes.rst.txt

=======================================================================
Feature: #92884 - Applications implement PSR-15 RequestHandlerInterface
=======================================================================

See :issue:`92884`

Description
===========

The TYPO3 core has three application classes: frontend, backend and install tool.
Those are the main entry points to retrieve a PSR-7 response from a PSR-7 request.
These application classes now implement the PSR-15 :php:`RequestHandlerInterface`.


Impact
======

Implementing the interface increases interoperability with third party applications
and allows feeding a PSR-7 request to one of the three applications and
retrieve a PSR-7 response.

Within TYPO3, a first usage is the testing framework: A functional backend test
can call the frontend application to verify if content is rendered as expected.
At the moment, the TYPO3 internal state is still a bit tricky, though. There are
various places that for instance park state in static class properties which can
not be reset easily. Those areas are "dirty" after a request has been handled.
Additionally, TYPO3 still alters various :php:`$GLOBALS` while handling a request.
The testing framework works around that at the moment.

While the situation will improve over time, third party applications should handle
this feature with care for the time being and may need to take care of additional
state clearing steps.

.. index:: PHP-API, ext:core
