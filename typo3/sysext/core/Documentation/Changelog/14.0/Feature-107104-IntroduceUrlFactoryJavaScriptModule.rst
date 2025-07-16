..  include:: /Includes.rst.txt

..  _feature-107104-1752673630:

===========================================================
Feature: #107104 - Introduce `UrlFactory` JavaScript module
===========================================================

See :issue:`107104`

Description
===========

TYPO3 already uses the native :js:`URL` and :js:`URLSearchParams` objects when
working with URLs. To make the generation of said objects easier, the module
:js:`@typo3/core/factory/url-factory.js` is introduced.

Impact
======

:js:`URL` and :js:`URLSearchParams` objects can be created by the factory's
:js:`createUrl` and :js:`createSearchParams` methods.

The method :js:`createUrl()` creates a full :js:`URL` object while taking care
of setting its base automatically. The method accepts the following arguments:

* :js:`url` – string
* :js:`parameters` – mixed

Any value of :js:`parameters`, if not :js:`undefined`, will be handed to
:js:`createSearchParams()`, being documented below.

The method :js:`createSearchParams()` creates a :js:`URLSearchParams` object and
accepts the following arguments:

* :js:`parameters` – mixed

Parameters can be passed as plain string values and nested objects. Passing a
plain array is **not** supported.

..  note::

    When a nested object is passed, values of type `null` or `undefined` are discarded!

Examples
--------

The following examples assume the existence of `TYPO3.settings.ajaxUrls.my_dedicated_endpoint`,
pointing to the route `/custom_endpoint`, while being on `https://localhost` for documentation purposes.


..  code-block:: js
    :caption: Create a simple :js:`URL` object

    import { UrlFactory } from '@typo3/core/factory/url-factory.js';

    const url = UrlFactory.createUrl(TYPO3.settings.ajaxUrls.my_dedicated_endpoint);
    console.log(url.toString()); // https://localhost/custom_endpoint


..  code-block:: js
    :caption: Create a :js:`URL` object containing a query string from a nested object

    import { UrlFactory } from '@typo3/core/factory/url-factory.js';

    const url = UrlFactory.createUrl(TYPO3.settings.ajaxUrls.my_dedicated_endpoint, {
      foo: 'bar',
      baz: {
        hello: 'world',
      },
    });
    console.log(url.toString()); // https://localhost/custom_endpoint?foo=bar&baz[hello]=world


..  code-block:: js
    :caption: Create a :js:`URLSearchParams` from a string input

    import { UrlFactory } from '@typo3/core/factory/url-factory.js';

    const urlSearchParams = UrlFactory.createSearchParams('foo=bar&baz=bencer');
    console.log(urlSearchParams.toString()); // foo=bar&baz=bencer


..  code-block:: js
    :caption: Create a :js:`URLSearchParams` from an object input

    import { UrlFactory } from '@typo3/core/factory/url-factory.js';

    const urlSearchParams = UrlFactory.createSearchParams({foo: 'bar', baz: 'bencer'});
    console.log(urlSearchParams.toString()); // foo=bar&baz=bencer

..  note::

    Unlike :js:`URLSearchParam`'s constructor, :js:`UrlFactory.create()` does
    not support JavaScript entries as :js:`parameter` argument. If this case is
    required, entries can be converted into an object by using
    :js:`Object.fromEntries()`.

..  index:: JavaScript, ext:core
