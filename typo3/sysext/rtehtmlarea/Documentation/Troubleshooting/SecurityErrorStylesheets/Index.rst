.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _security-error-stylesheets:

Security error when accessing the stylesheets
---------------------------------------------

This article by Carsten Emde describes a problem that may arise when
the RTE tries to access the stylesheets.

.. _security-error-stylesheets-problem:

Problem:
""""""""

In Firefox, the following error message is written to the JavaScript
console:

"[A security error occurred. Make sure all stylesheets are accessed
from the same domain/subdomain and using the same protocol as the
current script."


.. _security-error-stylesheets-background:

Background:
"""""""""""

In order to prevent the error, everything of a web page needs to be in
the same domain, in the same subdomain and, more importantly, be
transmitted with the same protocol. This is not a special feature of
Firefox; IE8, Safari, Chrome, Opera and friends are behaving
similarly.

Initially, a user is connecting to our Web site
"http://www.mydomain.org", and the content of the Web site including
CSS files is loaded. In order to use the calendar and trouble ticket
extensions, the user needs to login. As required for this purpose, the
login page is accessed via https and some content is then transmitted
using this protocol. Any further attempt to run RTE in this situation,
irrespective of whether subsequent content is transmitted via http or
https, crashes with the security error. This is the result of the
browser storing the transmission protocol and the domain of the
content, so it can refuse to load dynamic pages, if they do not match
the available content, or if there is no coherent origin and protocol
of the content.


.. _security-error-stylesheets-solution:

Solution:
"""""""""

I therefore changed the baseURL of the page to
"https://www.mydomain.org" to force a coherent protocol throughout an
entire session - even when it is not needed. Unfortunately, it still
did not work, because I simply forgot to flush the browser cache. Of
course, I flushed the server caches (as always), but in this special
case, it is important that the browser cache be flushed as well to
remove any non-https content at the client site. Only if the entire
content of a Web page has been transmitted using the same protocol, it
is considered safe. After I flushed the browser cache, RTE popped up
and started to work as I was used to it from the backend experience.


