.. include:: ../../Includes.txt

======================================================================
Important: #87980 - "Page is being generated" message has been removed
======================================================================

See :issue:`87980`

Description
===========

The "Page is being generated" message and the corresponding
temporary 503 response have been removed.

Instead of offloading the work to wait for the final page content,
concurrent requests now wait for the real page content to be
rendered (and deliver the content from cache once ready) instead
of sending a 503 response code and the famous "Page is being
generated" message.


The motivation for this change:
The 503 status code together with the "Page is being generated message"
does not only occur for slow or high traffic sites. It will be displayed
even for two concurrent requests, no matter how fast the page rendered
or how low the current traffic is.
The requests only need to (nearly) arrive at the same time.

Note: In case the increased number of waiting requests has a negative
impact on highly frequented servers, an additional proxy cache should be
considered in front of the server to make sure clients are served a valid
response without waiting until new content is ready.

.. index:: Frontend, ext:frontend
