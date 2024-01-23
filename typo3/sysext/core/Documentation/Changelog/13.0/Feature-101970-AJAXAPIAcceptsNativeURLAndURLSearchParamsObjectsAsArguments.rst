.. include:: /Includes.rst.txt

.. _feature-101970-1695205584:

=======================================================================================
Feature: #101970 - Ajax API accepts native URL and URLSearchParams objects as arguments
=======================================================================================

See :issue:`101970`

Description
===========

The Ajax API (:js:`@typo3/core/ajax/ajax-request`) has been enhanced to accept
native URL-related objects.


Impact
======

The constructor now accepts a :js:`URL` object as argument, along with the
already established `string` type. Also, the :js:`withQueryArguments()` method
accepts an object of type :js:`URLSearchParams` as argument.

Example
-------

.. code-block:: javascript

    import AjaxRequest from '@typo3/core/ajax/ajax-request.js';

    const url = new URL('https://example.com/page/1/2/');
    const queryArguments = new URLSearchParams({
        foo: 'bar',
        baz: 'bencer'
    });

    const request = new AjaxRequest(url).withQueryArguments(queryArguments);
    request.get().then(/* ... */);


.. index:: JavaScript, ext:core
