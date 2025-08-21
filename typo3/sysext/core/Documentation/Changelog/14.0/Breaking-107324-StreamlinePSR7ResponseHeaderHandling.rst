..  include:: /Includes.rst.txt

..  _breaking-107324-1755973900:

=============================================================
Breaking: #107324 - Streamline PSR-7 Response Header Handling
=============================================================

See :issue:`107324`

Description
===========

The handling of PSR-7 response headers in TYPO3 Core and Extbase has been
unified. Previously, different mechanisms caused inconsistent behavior:

* **Extbase** only kept the *last* value of a header, discarding all previous
  values (e.g. only one ``Set-Cookie`` header was possible).
* **Core** allowed multiple ``Set-Cookie`` headers, but merged all other headers
  with multiple values into a single comma-separated string.
  According to RFC 9110, this is only valid for headers that explicitly
  support comma-separated lists.

With this change, TYPO3 now preserves multiple header values by default.
Each value is emitted as a separate header line, while single values remain
a single-line header.

Impact
======

* Multiple header values are now always emitted as multiple header lines.
* Extbase and Core responses can now properly emit multiple headers with the
  same name (e.g. ``Set-Cookie``, ``WWW-Authenticate``, ``Link``, ``xkey``).
* Extensions that relied on the old merging or overwriting behavior may need
  to be adapted.

Affected installations
======================

Installations are affected if they:

* Relied on headers being merged into a comma-separated string.
* Relied on only the last header value being retained in Extbase responses

Migration
=========

If your use case requires *merged* header values, you must now implement this
explicitly:

.. code-block:: php

    $response = new \TYPO3\CMS\Core\Http\Response();
    $values = ['foo', 'bar', 'baz'];
    $response = $response->withHeader('X-Foo-Bar', implode(', ', $values));

If your use case requires that only the *last* header value is retained, you
must also handle this explicitly in your code.

.. code-block:: php

    $response = new \TYPO3\CMS\Core\Http\Response();
    $values = ['foo', 'bar', 'baz'];
    $response = $response->withHeader('X-Foo-Bar', end($values));

Note there is another edge case not affected by this change: Multiple extbase
plugins can not set multiple same header values (you can not have two extbase
plugins both setting a 'set-cookie' header). The latter will override the former.
Instances facing this need must deal with the scenario by adding an own
middleware.


.. index:: Frontend, PHP-API, ext:core, ext:extbase, ext:frontend, NotScanned
