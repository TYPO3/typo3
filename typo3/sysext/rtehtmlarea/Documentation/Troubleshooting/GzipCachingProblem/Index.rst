.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _caching-problem-gzip:

Internet Explorer caching problem with Apache mod\_gzip module
--------------------------------------------------------------

This article by `Jan Wulff <mailto:messages@janwulff.de>`_ describes
the problems encountered with Internet Explorer 4/5/6 and the Apache
server with mod\_gzip activated. It describes how to work around these
problems.


.. _caching-problem-gzip-problem:

Problem:
""""""""

If the Apache module mod\_gzip is activated on your server, you may
encounter the problem that Internet Explorer denies any caching for
the whole site, thus stopping the block style and text style selctor
lists of htmlArea RTE to work correctly. Besides, it may even slow
down some other features of TYPO3, like graphical JavaScript menus.
This effect does not depend on the gzip compression itself. Internet
Explorer is indeed able to handle compressed files. The problem is
IE's handling of one of the HTTP response headers sent with every
served document.


.. _caching-problem-gzip-background:

Background:
"""""""""""

The HTTP Vary response header indicates whether a cache is permitted
to use the response to reply to a subsequent request without re-
validating the document. This is necessary if a document is not
suitable for all clients and is served in multiple different versions
according to the HTTP headers the client sends with his request.

For example, with activated mod\_gzip, every document is at least
available in two versions, compressed and uncompressed. If a browser
with gzip support requests such a document, it will receive the
compressed version. A proxy between the client and the server may
cache this file. Now, another browser without gzip support requests
the same document via the same proxy. Without the Vary header the
proxy would not know if the compressed document may be delivered to
the new client, because it can't compare the HTTP headers of the
second browser with the Vary header. If it would nevertheless serve
it, the client would receive a bunch of data, without any idea, how to
process it. Therefore mod\_gzip sends a Vary header with each response
with at least 'Accept-Encoding' as content.

The problems arise when the Internet Explorer enters the stage. IE 4,
5 and 6 recognizes only one kind of Vary header: 'User-agent', used to
distinguish between versions for different browsers. Every other Vary
header will be interpreted as it would have a single '\*' as content.
Because this does not compare with the headers send by any client, it
forbids any caching of documents received with this header.


.. _caching-problem-gzip-solution:

Solution:
"""""""""

There is more than one approach to handle this problem. The following
configuration directives all have to be set in the Apache
configuration file or in a .htaccess file which has to be located in
your TYPO3 root.

**Easy going:**

So, you have no need for any gzip support? Fine, just deactivate the
module and your problems are gone. Use this directive:

::

   mod_gzip_on No

**Complex approach:**

You do have a lot of big code or text files, or you have to save as
much transfer bandwidth as possible? Anyway, deactivating mod\_gzip is
no option for you? Then, you should first check what release of
mod\_gzip your server is using. If you don't know how, ask your
provider, or just use the solution for releases from 1.3.19.2a till
1.3.26.1a.

- mod\_gzip release < 1.3.19.2a

Releases before this version didn't send Vary headers, so there
shouldn't be any problem. But because you're reading this, you most
probably don't use these versions.

- mod\_gzip release 1.3.19.2a <> 1.3.26.1a

These releases all use Vary headers. However, they send these headers
without verifying if the document is really checked for compression.
The only recommended way to get around this, is to deactivate
mod\_gzip. But thanks to Apache, you can deactivate mod\_gzip
separately for chosen files, and let it do it's work for the rest. You
could use this to deactivate mod\_gzip for all css files:

::

   <FilesMatch "\.css$">
   mod_gzip_on No
   </FilesMatch>

Or going even further, you could also include image files:

::

   <FilesMatch "\.(css|gif|jpe?g|png)$">
   mod_gzip_on No
   </FilesMatch>

By the way, there is another possibility. You could deactivate Vary
headers in mod\_gzip with this:

::

   mod_gzip_send_vary Off

But there is a reason why mod\_gzip, since 1.3.19.2a, uses Vary
headers. As described above, you could badly mess up proxy servers, by
serving compressed files without Vary headers. Therefore, I strongly
discourage this approach.

- mod\_gzip release > 1.3.26.1a

Since release 1.3.26.1a, mod\_gzip is a bit more discriminate. It only
sends Vary headers with documents which were checked for compression.
So you can tell mod\_gzip to exclude some files. This approach is not
so much different from the former solution, but it is cleaner because
it addresses mod\_gzip firsthand. To exclude CSS files from
compression, use this directive:

::

   mod_gzip_item_exclude file \.css$

If you would like to add images and Javascript files, you could use
this:

::

   mod_gzip_item_exclude file \.css$
   mod_gzip_item_exclude file \.png$
   mod_gzip_item_exclude file \.gif$
   mod_gzip_item_exclude file \.jpg$
   mod_gzip_item_exclude file \.jpeg$
   mod_gzip_item_exclude file \.js$

This is just a short survey of the caching problems with Internet
Explorer and mod\_gzip. I wrote it with best intent and hope it may be
helpful. If you find any mistakes, please let me know at
<messages@janwulff.de>. I'm in no way responsible for any consequences
that may come forth by the use of this information.



