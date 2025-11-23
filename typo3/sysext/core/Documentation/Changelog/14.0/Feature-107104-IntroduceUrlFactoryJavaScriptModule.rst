..  include:: /Includes.rst.txt

..  _feature-107104-1752673630:

===========================================================
Feature: #107104 - Introduce UrlFactory JavaScript module
===========================================================

See :issue:`107104`

Description
===========

TYPO3 already uses the native :js:`URL` and :js:`URLSearchParams` objects when
working with URLs. The module :js:`@typo3/core/factory/url-factory.js` has been
introduced to provide a consistent and convenient way to create and manage these
objects.

Impact
======

:js:`URL` and :js:`URLSearchParams` objects can now be created by the factory's
:js:`createUrl()` and :js:`createSearchParams()` methods.

The method :js:`createUrl()` creates a full :js:`URL` object and automatically
sets its base. It accepts the following arguments:

*   :js:`url` - string
*   :js:`parameters` - mixed

If provided, the :js:`parameters` value is passed to
:js:`createSearchParams()` (described below).

The method :js:`createSearchParams()` creates a :js:`URLSearchParams` object and
accepts the following argument:

*   :js:`parameters` - mixed

Parameters can be passed as plain string values or nested objects.
Passing a plain array is **not** supported.

..  note::

    When a nested object is passed, values of type :js:`null` or
    :js:`undefined` are discarded.

Examples
--------

The following examples assume the existence of
:js:`TYPO3.settings.ajaxUrls.my_dedicated_endpoint`, pointing to the route
`/custom_endpoint`, while being on `https://localhost` for documentation
purposes.

..  code-block:: js
    :caption: Create a URL object

    import { UrlFactory } from '@typo3/core/factory/url-factory.js';

    const url = UrlFactory.createUrl(
        TYPO3.settings.ajaxUrls.my_dedicated_endpoint
    );
    console.log(url.toString());
    // https://localhost/custom_endpoint

..  code-block:: js
    :caption: Create a URL object containing a query string from a nested object

    import { UrlFactory } from '@typo3/core/factory/url-factory.js';

    const url = UrlFactory.createUrl(
        TYPO3.settings.ajaxUrls.my_dedicated_endpoint,
        {
            foo: 'bar',
            baz: {
                hello: 'world',
            },
        }
    );
    console.log(url.toString());
    // https://localhost/custom_endpoint?foo=bar&baz[hello]=world

..  code-block:: js
    :caption: Create a URLSearchParams object from a string input

    import { UrlFactory } from '@typo3/core/factory/url-factory.js';

    const urlSearchParams = UrlFactory.createSearchParams(
        'foo=bar&baz=bencer'
    );
    console.log(urlSearchParams.toString());
    // foo=bar&baz=bencer

..  code-block:: js
    :caption: Create a URLSearchParams object from an object input

    import { UrlFactory } from '@typo3/core/factory/url-factory.js';

    const urlSearchParams = UrlFactory.createSearchParams({
        foo: 'bar',
        baz: 'bencer',
    });
    console.log(urlSearchParams.toString());
    // foo=bar&baz=bencer

..  note::

    Unlike the native :js:`URLSearchParams` constructor, the
    :js:`UrlFactory.createSearchParams()` method does not support JavaScript
    :js:`entries` objects directly. If needed, entries can be converted into an
    object by using :js:`Object.fromEntries()`.

..  index:: JavaScript, ext:core
