.. include:: ../../Includes.txt


.. _form-objects:

============
FORM objects
============

The editor is not bound to FORM objects shown below. Whenever FORM will be
put in TypoScript, the contents of this property will be sent to the
FORM plugin. However, one can use regular TYPO3 content objects (cObjects)
as well. This means the integrator has the possibility to add COA, TEXT or
even HMENU in the FORM TypoScript.

Due to technical limitations it is **not** possible to nest form objects
inside content objects. The following nesting will not work:
:ts:`FORM` > :ts:`COA` > :ts:`TEXTLINE`.

Furthermore, using cObjects is only allowed when **not** using the form
content element/ wizard in the backend. This is due to security reasons.
The functionality is only available when embedding a form directly in the
TypoScript setup.

.. toctree::
    :maxdepth: 5
    :titlesonly:
    :glob:

    ObjectAttributes/Index
    Button/Index
    Checkbox/Index
    Fieldset/Index
    Fileupload/Index
    Form/Index
    Header/Index
    Hidden/Index
    Imagebutton/Index
    Optgroup/Index
    Option/Index
    Password/Index
    Radio/Index
    Reset/Index
    Select/Index
    Submit/Index
    Textarea/Index
    Textblock/Index
    Textline/Index

============== ================================================= ================================================= ================================================= ================================================= ====================================================== ===================================================
Element        BUTTON                                            CHECKBOX                                          FIELDSET                                          FILEUPLOAD                                        FORM                                                   HEADER
============== ================================================= ================================================= ================================================= ================================================= ====================================================== ===================================================
accept                                                                                                                                                                                                                 :ref:`X <reference-objects-attributes-accept>`
accept-charset                                                                                                                                                                                                         :ref:`X <reference-objects-attributes-accept-charset>`
accesskey      :ref:`X <reference-objects-attributes-accesskey>` :ref:`X <reference-objects-attributes-accesskey>`                                                   :ref:`X <reference-objects-attributes-accesskey>`
action                                                                                                                                                                                                                 :ref:`X <reference-objects-attributes-action>`
alt            :ref:`X <reference-objects-attributes-alt>`       :ref:`X <reference-objects-attributes-alt>`                                                         :ref:`X <reference-objects-attributes-alt>`
checked                                                          :ref:`X <reference-objects-attributes-checked>`
class          :ref:`X <reference-objects-attributes-class>`     :ref:`X <reference-objects-attributes-class>`     :ref:`X <reference-objects-attributes-class>`     :ref:`X <reference-objects-attributes-class>`     :ref:`X <reference-objects-attributes-class>`
content                                                                                                                                                                                                                                                                       :ref:`X <reference-objects-attributes-content>`
cols
data
dir            :ref:`X <reference-objects-attributes-dir>`       :ref:`X <reference-objects-attributes-dir>`       :ref:`X <reference-objects-attributes-dir>`       :ref:`X <reference-objects-attributes-dir>`       :ref:`X <reference-objects-attributes-dir>`
disabled       :ref:`X <reference-objects-attributes-disabled>`  :ref:`X <reference-objects-attributes-disabled>`                                                    :ref:`X <reference-objects-attributes-disabled>`
enctyp                                                                                                                                                                                                                 :ref:`X <reference-objects-attributes-enctype>`
filters
headingSize                                                                                                                                                                                                                                                                   :ref:`X <reference-objects-attributes-headingSize>`
id             :ref:`X <reference-objects-attributes-id>`        :ref:`X <reference-objects-attributes-id>`        :ref:`X <reference-objects-attributes-id>`        :ref:`X <reference-objects-attributes-id>`        :ref:`X <reference-objects-attributes-id>`
label          :ref:`X <reference-objects-attributes-label>`     :ref:`X <reference-objects-attributes-label>`                                                       :ref:`X <reference-objects-attributes-label>`
lang           :ref:`X <reference-objects-attributes-lang>`      :ref:`X <reference-objects-attributes-lang>`      :ref:`X <reference-objects-attributes-lang>`      :ref:`X <reference-objects-attributes-lang>`      :ref:`X <reference-objects-attributes-lang>`
layout         :ref:`X <reference-layout>`                       :ref:`X <reference-layout>`                       :ref:`X <reference-layout>`                       :ref:`X <reference-layout>`                       :ref:`X <reference-layout>`
legend                                                                                                             :ref:`X <reference-objects-attributes-legend>`
maxlength
method                                                                                                                                                                                                                 :ref:`X <reference-objects-attributes-method>`
multiple
name           :ref:`X <reference-objects-attributes-name>`      :ref:`X <reference-objects-attributes-name>`                                                        :ref:`X <reference-objects-attributes-name>`      :ref:`X <reference-objects-attributes-name>`
postProcessor                                                                                                                                                                                                          :ref:`X <reference-objects-attributes-postProcessor>`
prefix                                                                                                                                                                                                                 :ref:`X <reference-objects-attributes-prefix>`
readonly
rows
rules                                                                                                                                                                                                                  :ref:`X <reference-objects-attributes-rules>`
selected
size                                                                                                                                                                 :ref:`X <reference-objects-attributes-size>`
src
style          :ref:`X <reference-objects-attributes-style>`     :ref:`X <reference-objects-attributes-style>`     :ref:`X <reference-objects-attributes-style>`     :ref:`X <reference-objects-attributes-style>`     :ref:`X <reference-objects-attributes-style>`
tabindex       :ref:`X <reference-objects-attributes-tabindex>`  :ref:`X <reference-objects-attributes-tabindex>`                                                    :ref:`X <reference-objects-attributes-tabindex>`
title          :ref:`X <reference-objects-attributes-title>`     :ref:`X <reference-objects-attributes-title>`                                                       :ref:`X <reference-objects-attributes-title>`     :ref:`X <reference-objects-attributes-title>`
type           :ref:`X <reference-objects-attributes-type>`      :ref:`X <reference-objects-attributes-type>`                                                        :ref:`X <reference-objects-attributes-type>`
value          :ref:`X <reference-objects-attributes-value>`     :ref:`X <reference-objects-attributes-value>`
============== ================================================= ================================================= ================================================= ================================================= ====================================================== ===================================================

============== ================================================= ================================================= ================================================= ================================================= ================================================= =================================================
Element        HIDDEN                                            IMAGEBUTTON                                       OPTGROUP                                          OPTION                                            PASSWORD                                          RADIO
============== ================================================= ================================================= ================================================= ================================================= ================================================= =================================================
accept
accept-charset
accesskey                                                        :ref:`X <reference-objects-attributes-accesskey>`                                                                                                     :ref:`X <reference-objects-attributes-accesskey>` :ref:`X <reference-objects-attributes-accesskey>`
action
alt                                                              :ref:`X <reference-objects-attributes-alt>`                                                                                                           :ref:`X <reference-objects-attributes-alt>`       :ref:`X <reference-objects-attributes-alt>`
checked                                                                                                                                                                                                                                                                  :ref:`X <reference-objects-attributes-checked>`
class          :ref:`X <reference-objects-attributes-class>`     :ref:`X <reference-objects-attributes-class>`     :ref:`X <reference-objects-attributes-class>`     :ref:`X <reference-objects-attributes-class>`     :ref:`X <reference-objects-attributes-class>`     :ref:`X <reference-objects-attributes-class>`
content
cols
data                                                                                                                                                                 :ref:`X <reference-objects-attributes-data>`
dir                                                              :ref:`X <reference-objects-attributes-dir>`                                                                                                           :ref:`X <reference-objects-attributes-dir>`       :ref:`X <reference-objects-attributes-dir>`
disabled                                                         :ref:`X <reference-objects-attributes-disabled>`  :ref:`X <reference-objects-attributes-disabled>`  :ref:`X <reference-objects-attributes-disabled>`  :ref:`X <reference-objects-attributes-disabled>`  :ref:`X <reference-objects-attributes-disabled>`
enctype
filters        :ref:`X <reference-objects-attributes-filters>`                                                                                                                                                         :ref:`X <reference-objects-attributes-filters>`
headingSize
id             :ref:`X <reference-objects-attributes-id>`        :ref:`X <reference-objects-attributes-id>`        :ref:`X <reference-objects-attributes-id>`        :ref:`X <reference-objects-attributes-id>`        :ref:`X <reference-objects-attributes-id>`        :ref:`X <reference-objects-attributes-id>`
label                                                            :ref:`X <reference-objects-attributes-label>`     :ref:`X <reference-objects-attributes-label>`     :ref:`X <reference-objects-attributes-label>`     :ref:`X <reference-objects-attributes-label>`     :ref:`X <reference-objects-attributes-label>`
lang           :ref:`X <reference-objects-attributes-lang>`      :ref:`X <reference-objects-attributes-lang>`      :ref:`X <reference-objects-attributes-lang>`      :ref:`X <reference-objects-attributes-lang>`      :ref:`X <reference-objects-attributes-lang>`      :ref:`X <reference-objects-attributes-lang>`
layout         :ref:`X <reference-layout>`                       :ref:`X <reference-layout>`                       :ref:`X <reference-layout>`                       :ref:`X <reference-layout>`                       :ref:`X <reference-layout>`                       :ref:`X <reference-layout>`
legend
maxlength                                                                                                                                                                                                              :ref:`X <reference-objects-attributes-maxlength>`
method
multiple
name           :ref:`X <reference-objects-attributes-name>`      :ref:`X <reference-objects-attributes-name>`                                                                                                          :ref:`X <reference-objects-attributes-name>`      :ref:`X <reference-objects-attributes-name>`
postProcessor
prefix
readonly                                                                                                                                                                                                               :ref:`X <reference-objects-attributes-readonly>`
rows
rules
selected                                                                                                                                                             :ref:`X <reference-objects-attributes-selected>`
size                                                                                                                                                                                                                   :ref:`X <reference-objects-attributes-size>`
src                                                              :ref:`X <reference-objects-attributes-src>`
style          :ref:`X <reference-objects-attributes-style>`     :ref:`X <reference-objects-attributes-style>`     :ref:`X <reference-objects-attributes-style>`     :ref:`X <reference-objects-attributes-style>`     :ref:`X <reference-objects-attributes-style>`     :ref:`X <reference-objects-attributes-style>`
tabindex                                                         :ref:`X <reference-objects-attributes-tabindex>`                                                                                                      :ref:`X <reference-objects-attributes-tabindex>`  :ref:`X <reference-objects-attributes-tabindex>`
title                                                            :ref:`X <reference-objects-attributes-title>`     :ref:`X <reference-objects-attributes-title>`     :ref:`X <reference-objects-attributes-title>`     :ref:`X <reference-objects-attributes-title>`     :ref:`X <reference-objects-attributes-title>`
type           :ref:`X <reference-objects-attributes-type>`      :ref:`X <reference-objects-attributes-type>`                                                                                                          :ref:`X <reference-objects-attributes-type>`      :ref:`X <reference-objects-attributes-type>`
value          :ref:`X <reference-objects-attributes-value>`     :ref:`X <reference-objects-attributes-value>`                                                       :ref:`X <reference-objects-attributes-value>`     :ref:`X <reference-objects-attributes-value>`     :ref:`X <reference-objects-attributes-value>`
============== ================================================= ================================================= ================================================= ================================================= ================================================= =================================================

============== ================================================= ================================================= ================================================= ================================================= ================================================= =================================================
Element        RESET                                             SELECT                                            SUBMIT                                            TEXTAREA                                          TEXTBLOCK                                         TEXTLINE
============== ================================================= ================================================= ================================================= ================================================= ================================================= =================================================
accept
accept-charset
accesskey      :ref:`X <reference-objects-attributes-accesskey>`                                                   :ref:`X <reference-objects-attributes-accesskey>` :ref:`X <reference-objects-attributes-accesskey>`                                                   :ref:`X <reference-objects-attributes-accesskey>`
action
alt            :ref:`X <reference-objects-attributes-alt>`                                                         :ref:`X <reference-objects-attributes-alt>`                                                                                                           :ref:`X <reference-objects-attributes-alt>`
checked
class          :ref:`X <reference-objects-attributes-class>`     :ref:`X <reference-objects-attributes-class>`     :ref:`X <reference-objects-attributes-class>`     :ref:`X <reference-objects-attributes-class>`                                                       :ref:`X <reference-objects-attributes-class>`
content                                                                                                                                                                                                                :ref:`X <reference-objects-attributes-content>`
cols                                                                                                                                                                 :ref:`X <reference-objects-attributes-cols>`
data
dir            :ref:`X <reference-objects-attributes-dir>`                                                         :ref:`X <reference-objects-attributes-dir>`       :ref:`X <reference-objects-attributes-dir>`                                                         :ref:`X <reference-objects-attributes-dir>`
disabled       :ref:`X <reference-objects-attributes-disabled>`  :ref:`X <reference-objects-attributes-disabled>`  :ref:`X <reference-objects-attributes-disabled>`  :ref:`X <reference-objects-attributes-disabled>`                                                    :ref:`X <reference-objects-attributes-disabled>`
enctype
filters
headingSize
id             :ref:`X <reference-objects-attributes-id>`        :ref:`X <reference-objects-attributes-id>`        :ref:`X <reference-objects-attributes-id>`        :ref:`X <reference-objects-attributes-id>`                                                          :ref:`X <reference-objects-attributes-id>`
label          :ref:`X <reference-objects-attributes-label>`     :ref:`X <reference-objects-attributes-label>`     :ref:`X <reference-objects-attributes-label>`     :ref:`X <reference-objects-attributes-label>`                                                       :ref:`X <reference-objects-attributes-label>`
lang           :ref:`X <reference-objects-attributes-lang>`      :ref:`X <reference-objects-attributes-lang>`      :ref:`X <reference-objects-attributes-lang>`      :ref:`X <reference-objects-attributes-lang>`                                                        :ref:`X <reference-objects-attributes-lang>`
layout         :ref:`X <reference-layout>`                       :ref:`X <reference-layout>`                       :ref:`X <reference-layout>`
legend
maxlength                                                                                                                                                                                                                                                                :ref:`X <reference-objects-attributes-maxlength>`
method
multiple                                                         :ref:`X <reference-objects-attributes-multiple>`
name           :ref:`X <reference-objects-attributes-name>`      :ref:`X <reference-objects-attributes-name>`      :ref:`X <reference-objects-attributes-name>`      :ref:`X <reference-objects-attributes-name>`                                                        :ref:`X <reference-objects-attributes-name>`
postProcessor
prefix
readonly                                                                                                                                                             :ref:`X <reference-objects-attributes-readonly>`                                                    :ref:`X <reference-objects-attributes-readonly>`
rows                                                                                                                                                                 :ref:`X <reference-objects-attributes-rows>`
rules
selected
size                                                             :ref:`X <reference-objects-attributes-size>`                                                                                                                                                            :ref:`X <reference-objects-attributes-size>`
src
style          :ref:`X <reference-objects-attributes-style>`     :ref:`X <reference-objects-attributes-style>`     :ref:`X <reference-objects-attributes-style>`     :ref:`X <reference-objects-attributes-style>`                                                       :ref:`X <reference-objects-attributes-style>`
tabindex       :ref:`X <reference-objects-attributes-tabindex>`  :ref:`X <reference-objects-attributes-tabindex>`  :ref:`X <reference-objects-attributes-tabindex>`  :ref:`X <reference-objects-attributes-tabindex>`                                                    :ref:`X <reference-objects-attributes-tabindex>`
title          :ref:`X <reference-objects-attributes-title>`     :ref:`X <reference-objects-attributes-title>`     :ref:`X <reference-objects-attributes-title>`     :ref:`X <reference-objects-attributes-title>`                                                       :ref:`X <reference-objects-attributes-title>`
type           :ref:`X <reference-objects-attributes-type>`                                                        :ref:`X <reference-objects-attributes-type>`
value          :ref:`X <reference-objects-attributes-value>`                                                       :ref:`X <reference-objects-attributes-value>`                                                                                                         :ref:`X <reference-objects-attributes-value>`
============== ================================================= ================================================= ================================================= ================================================= ================================================= =================================================

