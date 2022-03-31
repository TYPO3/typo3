.. include:: /Includes.rst.txt

===========================================
Important: #90897 - Remove bootstrap-slider
===========================================

See :issue:`90897`

Description
===========

The internally used library `bootstrap-slider` has been removed. HTML input
fields using `type="range"` are used as substitution.

Extension relying on that internal library may be dysfunctional now.

Example:

.. code-block:: html

   <div class="slider-wrapper">
       <input type="range" class="slider" min="10" max="50" step="5">
   </div>


If the value of the `range` field is changed, the `input` event is emitted which
can be listened to by registering an event listener.

.. important::

   Extension authors are encouraged to not use libraries that are not explicitly
   marked as public API.

.. index:: Backend, JavaScript, ext:backend
