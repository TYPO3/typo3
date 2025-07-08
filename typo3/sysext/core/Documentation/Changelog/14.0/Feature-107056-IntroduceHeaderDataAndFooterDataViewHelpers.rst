..  include:: /Includes.rst.txt

..  _feature-107056-1753012116:

==================================================================
Feature: #107056 - Introduce headerData and footerData ViewHelpers
==================================================================

See :issue:`107056`

Description
===========

Two new Fluid ViewHelpers have been introduced to allow injecting arbitrary
content into the HTML :php:`<head>` or before the closing :php:`</body>` tag
of a rendered page:

* :php:`<f:page.headerData>` – injects content into the :php:`<head>` section.
* :php:`<f:page.footerData>` – injects content before the closing :php:`</body>` tag.

The ViewHelpers internally use the :php:`PageRenderer` API  and are useful when
the existing ViewHelpers like :php:`<f:asset.css>` or :php:`<f:asset.script>` do
not support all required attributes or use cases (e.g. :php:`dns-prefetch`,
:php:`preconnect`, tracking scripts, or inline JavaScript).

Example usage for :php:`headerData`:

.. code-block:: html

   <f:page.headerData>
       <link rel="preload" href="/fonts/myfont.woff2" as="font" type="font/woff2" crossorigin="anonymous">
       <link rel="dns-prefetch" href="//example-cdn.com">
       <link rel="preconnect" href="https://example-cdn.com">
   </f:page.headerData>

Example usage for `footerData`:

.. code-block:: html

   <f:page.footerData>
       <script>
           var _paq = window._paq = window._paq || [];
           _paq.push(['trackPageView']);
           _paq.push(['enableLinkTracking']);
           (function() {
               var u = "https://your-matomo-domain.example.com/";
               _paq.push(['setTrackerUrl', u + 'matomo.php']);
               _paq.push(['setSiteId', '1']);
               var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
               g.async = true; g.src = u + 'matomo.js'; s.parentNode.insertBefore(g, s);
           })();
       </script>
   </f:page.footerData>

Both ViewHelpers output given content as is. Possible user supplied input for
the ViewHelpers must manually be escaped in order to prevent a Cross-Site
Scripting vulnerability.


Impact
======

Extension authors and integrators can use the new ViewHelpers to add raw HTML
content, such as :php:`<link>` or :php:`<script>` tags, directly into the
rendered output.

..  index:: Fluid, ext:fluid
