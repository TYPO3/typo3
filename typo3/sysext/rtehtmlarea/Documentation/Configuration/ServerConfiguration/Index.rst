.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _server-configuration:

Server Configuration
--------------------

Internet Explorer has caching problems that may affect the performance
of the htmlArea RTE. These problems may be worked around with the
following server configuration recommendations.


.. _apache-configuration:

Apache configuration:
^^^^^^^^^^^^^^^^^^^^^

Add the following lines to your Apache httpd.conf file or in the
.htaccess file of the root directory of your site:

::

   BrowserMatch "MSIE" brokenvary=1
   BrowserMatch "Mozilla/4.[0-9]{2}" brokenvary=1
   BrowserMatch "Opera" !brokenvary
   SetEnvIf brokenvary 1 force-no-vary
   
   ExpiresActive On
   ExpiresByType image/gif "access plus 1 month"
   ExpiresByType image/png "access plus 1 month"

The last two statements require the  **mod\_expires** Apache module to
be installed. For information on this module, see:

`http://httpd.apache.org/docs/2.4/mod/mod\_expires.html
<http://httpd.apache.org/docs/2.4/mod/mod_expires.html>`_


.. _microsoft-iis-configuration:

Microsoft IIS configuration:
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

See:
`http://www.aspnetresources.com/blog/cache\_control\_extensions.aspx
<http://www.aspnetresources.com/blog/cache_control_extensions.aspx>`_


.. _more-information:

More information:
^^^^^^^^^^^^^^^^^

For more information on this subject, see the following articles:

`http://dean.edwards.name/my/flicker.html
<http://dean.edwards.name/my/flicker.html>`_

`http://httpd.apache.org/docs/2.4/mod/mod\_expires.html
<http://httpd.apache.org/docs/2.4/mod/mod_expires.html>`_

`http://fivesevensix.com/studies/ie6flicker/
<http://fivesevensix.com/studies/ie6flicker/>`_

See also the Troubleshooting section of the present document for
information on IE caching problems in relation with the Apache
mod\_gzip module.


