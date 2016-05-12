/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

function configureWizardApplication() {
	var basicdeps = [
		//'TYPO3',
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Elements/ButtonGroup',
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Button',
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Checkbox',
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Fieldset',
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Fileupload',
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Hidden',
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Password',
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Radio',
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Reset',
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Select',
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Submit',
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Textarea',
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Textline'
	];
	requirejs.config({shim: {
		//'extjs': {exports: 'Ext'},
		//'TYPO3': {exports: 'TYPO3'},
		'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.isemptyobject': {exports: 'Ext.isemptyobject', deps: []},
		'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.merge': {exports: 'Ext.merge', deps: []},
		'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.spinner': {exports: 'Ext.ux.Spinner', deps: []},
		'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.form.textfieldsubmit': {exports: 'Ext.ux.form.textfieldsubmit', deps: []},
		'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.form.spinnerfield': {exports: 'Ext.ux.form.SpinnerField', deps: ['TYPO3/CMS/Form/Wizard/Ux/Ext.ux.spinner']},
		'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.form.FakeFormPanel': {exports: 'Ext.ux.form.FakeFormPanel', deps: []},
		'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.form.ValueCheckbox': {exports: 'Ext.ux.form.ValueCheckbox', deps: []},
		'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.grid.CheckColumn': {exports: 'Ext.ux.grid.CheckColumn', deps: []},
		'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.grid.SingleSelectCheckColumn': {exports: 'Ext.ux.grid.SingleSelectCheckColumn', deps: ['TYPO3/CMS/Form/Wizard/Ux/Ext.ux.grid.CheckColumn']},
		'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.grid.ItemDeleter': {exports: 'Ext.ux.grid.ItemDeleter', deps: []},
		'TYPO3/CMS/Form/Wizard/Settings': {exports: 'TYPO3.Form.Wizard.Settings', deps: []}, // defined during require callback
		'TYPO3/CMS/Form/Wizard/Helpers/History': {exports: 'TYPO3.Form.Wizard.Helpers.History', deps: []},
		'TYPO3/CMS/Form/Wizard/Helpers/Element': {exports: 'TYPO3.Form.Wizard.Helpers.Element', deps: []},
		'TYPO3/CMS/Form/Wizard/Elements/Dummy': {exports: 'TYPO3.Form.Wizard.Elements.Dummy', deps: ['TYPO3/CMS/Form/Wizard/Elements/Elements']},
		'TYPO3/CMS/Form/Wizard/Elements/ButtonGroup': {exports: 'TYPO3.Form.Wizard.ButtonGroup', deps: []},
		'TYPO3/CMS/Form/Wizard/Elements/Container': {exports: 'TYPO3.Form.Wizard.Container', deps: ['TYPO3/CMS/Form/Wizard/Elements/Dummy', 'TYPO3/CMS/Form/Wizard/Elements/Content/Header', 'TYPO3/CMS/Form/Wizard/Elements/Predefined/RadioGroup', 'TYPO3/CMS/Form/Wizard/Elements/Predefined/Email', 'TYPO3/CMS/Form/Wizard/Elements/Predefined/CheckboxGroup', 'TYPO3/CMS/Form/Wizard/Elements/Predefined/Name']},
		'TYPO3/CMS/Form/Wizard/Elements/Elements': {exports: 'TYPO3.Form.Wizard.Elements', deps: ['TYPO3/CMS/Form/Wizard/Helpers/Element', 'TYPO3/CMS/Form/Wizard/Helpers/History', 'TYPO3/CMS/Form/Wizard/Elements/ButtonGroup']},
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Form': {exports: 'TYPO3.Form.Wizard.Elements.Basic.Form', deps: ['TYPO3/CMS/Form/Wizard/Elements/Elements', 'TYPO3/CMS/Form/Wizard/Elements/Container']},
		'TYPO3/CMS/Form/Wizard/Viewport/Right': {exports: 'TYPO3.Form.Wizard.Viewport.Right', deps: ['TYPO3/CMS/Form/Wizard/Viewport', 'TYPO3/CMS/Form/Wizard/Elements/Basic/Form']},
		'TYPO3/CMS/Form/Wizard/Elements/Content/Header':        {exports: 'TYPO3.Form.Wizard.Elements.Content.Header',        deps: ['TYPO3/CMS/Form/Wizard/Elements/Elements']},
		'TYPO3/CMS/Form/Wizard/Elements/Content/Textblock':     {exports: 'TYPO3.Form.Wizard.Elements.Content.Textblock',     deps: ['TYPO3/CMS/Form/Wizard/Elements/Elements']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Elements/Content': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Elements.Content', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Elements/ButtonGroup', 'TYPO3/CMS/Form/Wizard/Elements/Content/Header', 'TYPO3/CMS/Form/Wizard/Elements/Content/Textblock']},
		'TYPO3/CMS/Form/Wizard/Elements/Predefined/Email':         {exports: 'TYPO3.Form.Wizard.Elements.Predefined.Email',         deps: ['TYPO3/CMS/Form/Wizard/Elements/Basic/Textline']},
		'TYPO3/CMS/Form/Wizard/Elements/Predefined/CheckboxGroup': {exports: 'TYPO3.Form.Wizard.Elements.Predefined.CheckboxGroup', deps: ['TYPO3/CMS/Form/Wizard/Elements/Basic/Fieldset', 'TYPO3/CMS/Form/Wizard/Elements/Basic/Checkbox']},
		'TYPO3/CMS/Form/Wizard/Elements/Predefined/Name':          {exports: 'TYPO3.Form.Wizard.Elements.Predefined.Name',          deps: ['TYPO3/CMS/Form/Wizard/Elements/Basic/Fieldset', 'TYPO3/CMS/Form/Wizard/Elements/Basic/Textline']},
		'TYPO3/CMS/Form/Wizard/Elements/Predefined/RadioGroup':    {exports: 'TYPO3.Form.Wizard.Elements.Predefined.RadioGroup',    deps: ['TYPO3/CMS/Form/Wizard/Elements/Basic/Fieldset', 'TYPO3/CMS/Form/Wizard/Elements/Basic/Radio', 'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.merge']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Elements/Predefined': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Elements.Predefined', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Elements/ButtonGroup']},
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Textline':   {exports: 'TYPO3.Form.Wizard.Elements.Basic.Textline',   deps: ['TYPO3/CMS/Form/Wizard/Elements/Elements']},
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Textarea':   {exports: 'TYPO3.Form.Wizard.Elements.Basic.Textarea',   deps: ['TYPO3/CMS/Form/Wizard/Elements/Elements']},
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Submit':     {exports: 'TYPO3.Form.Wizard.Elements.Basic.Submit',     deps: ['TYPO3/CMS/Form/Wizard/Elements/Elements']},
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Select':     {exports: 'TYPO3.Form.Wizard.Elements.Basic.Select',     deps: ['TYPO3/CMS/Form/Wizard/Elements/Elements']},
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Reset':      {exports: 'TYPO3.Form.Wizard.Elements.Basic.Reset',      deps: ['TYPO3/CMS/Form/Wizard/Elements/Elements']},
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Radio':      {exports: 'TYPO3.Form.Wizard.Elements.Basic.Radio',      deps: ['TYPO3/CMS/Form/Wizard/Elements/Elements']},
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Password':   {exports: 'TYPO3.Form.Wizard.Elements.Basic.Password',   deps: ['TYPO3/CMS/Form/Wizard/Elements/Elements']},
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Hidden':     {exports: 'TYPO3.Form.Wizard.Elements.Basic.Hidden',     deps: ['TYPO3/CMS/Form/Wizard/Elements/Elements']},
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Fileupload': {exports: 'TYPO3.Form.Wizard.Elements.Basic.Fileupload', deps: ['TYPO3/CMS/Form/Wizard/Elements/Elements']},
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Fieldset':   {exports: 'TYPO3.Form.Wizard.Elements.Basic.Fieldset',   deps: ['TYPO3/CMS/Form/Wizard/Elements/Elements']},
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Checkbox':   {exports: 'TYPO3.Form.Wizard.Elements.Basic.Checkbox',   deps: ['TYPO3/CMS/Form/Wizard/Elements/Elements']},
		'TYPO3/CMS/Form/Wizard/Elements/Basic/Button':     {exports: 'TYPO3.Form.Wizard.Elements.Basic.Button',     deps: ['TYPO3/CMS/Form/Wizard/Elements/Elements']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Elements/Basic': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Elements.Basic', deps: basicdeps},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Elements/ButtonGroup': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Elements.ButtonGroup', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Elements']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Elements': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Elements', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Options':    {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Options',    deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.grid.SingleSelectCheckColumn', 'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.grid.ItemDeleter']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Label':      {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Label', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.form.FakeFormPanel']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Dummy': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Dummy', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Filter': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Alphabetic':    {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Alphabetic',    deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Filter']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Alphanumeric':  {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Alphanumeric',  deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Filter']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Currency':      {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Currency',      deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Filter']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Digit':         {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Digit',         deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Filter']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Integer':       {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Integer',       deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Filter']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/LowerCase':     {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.LowerCase',     deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Filter']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/RegExp':        {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.RegExp',        deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Filter']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/StripNewLines': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.StripNewLines', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Filter']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/TitleCase':     {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.TitleCase',     deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Filter']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Trim':          {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Trim',          deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Filter']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/UpperCase':     {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.UpperCase',     deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Filter']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Dummy']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Legend':     {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Legend', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.form.FakeFormPanel']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule':     {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Rule',     deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation', 'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.form.FakeFormPanel']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Required': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Required', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Dummy':    {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Dummy',    deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Alphabetic':       {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Alphabetic', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Alphanumeric':     {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Alphanumeric', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Between':          {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Between', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Date':             {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Date', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Digit':            {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Digit', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Email':            {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Email', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Equals':           {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Equals', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/FileAllowedTypes': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.FileAllowedTypes', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/FileMaximumSize':  {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.FileMaximumSize', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/FileMinimumSize':  {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.FileMinimumSize', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Float':            {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Float', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/GreaterThan':      {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.GreaterThan', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/InArray':          {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.InArray', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Integer':          {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Integer', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Ip':               {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Ip', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Length':           {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Length', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/LessThan':         {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.LessThan', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/RegExp':           {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.RegExp', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Uri':              {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Uri', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Rule']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Dummy']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Various':    {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Various', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.form.FakeFormPanel']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Attributes': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Attributes', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options', 'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.form.ValueCheckbox', 'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.form.FakeFormPanel', 'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.form.spinnerfield']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Panel': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Panel', deps: [
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Legend',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Label',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Alphabetic',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Alphanumeric',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Currency',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Digit',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Integer',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/LowerCase',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/RegExp',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/StripNewLines',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/TitleCase',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/Trim',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters/UpperCase',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Filters',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Various',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Alphabetic',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Alphanumeric',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Between',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Date',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Digit',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Email',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Equals',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/FileAllowedTypes',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/FileMaximumSize',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/FileMinimumSize',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Float',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/GreaterThan',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/InArray',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Integer',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Ip',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Length',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/LessThan',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/RegExp',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Required',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation/Uri',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Validation',
			'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Options'
		]},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Dummy': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options.Dummy', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Options': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Options', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Form/PostProcessors/Dummy': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.Dummy', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Form']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Form/PostProcessors/PostProcessor': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.PostProcessor', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Form']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Form/PostProcessors/Mail': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.Mail', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Form', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Form/PostProcessors/PostProcessor']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Form/PostProcessors/Redirect': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.Redirect', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Form', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Form/PostProcessors/PostProcessor']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Form/PostProcessor': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessor', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Form', 'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.isemptyobject', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Form/PostProcessors/Dummy', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Form/PostProcessors/Mail', 'TYPO3/CMS/Form/Wizard/Viewport/Left/Form/PostProcessors/Redirect']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Form/Attributes': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Form.Attributes', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Attributes']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Form/Prefix': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Form.Prefix', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Form', 'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.form.textfieldsubmit', 'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.form.FakeFormPanel']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Form/Behaviour': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Form.Behaviour', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left/Form', 'TYPO3/CMS/Form/Wizard/Ux/Ext.ux.form.FakeFormPanel']},
		'TYPO3/CMS/Form/Wizard/Viewport/Left/Form': {exports: 'TYPO3.Form.Wizard.Viewport.Left.Form', deps: ['TYPO3/CMS/Form/Wizard/Viewport/Left'/*, 'TYPO3/CMS/Form/Wizard/Settings'*/]},
		'TYPO3/CMS/Form/Wizard/Viewport/Left': {exports: 'TYPO3.Form.Wizard.Viewport.Left', deps: ['TYPO3/CMS/Form/Wizard/Viewport'/*, 'TYPO3/CMS/Form/Wizard/Settings'*/]},
		'TYPO3/CMS/Form/Wizard/Viewport': {exports: 'TYPO3.Form.Wizard.Viewport', deps: []}
	}});
}
configureWizardApplication();

/**
 * Initialization script of TYPO3 form Wizard
 */
define('TYPO3/CMS/Form/Wizard', [
	//'extjs',
	//'TYPO3',
	'TYPO3/CMS/Backend/SplitButtons',
	'TYPO3/CMS/Form/Wizard/Settings',
	'TYPO3/CMS/Form/Wizard/Viewport/Left/Elements/Content',
	'TYPO3/CMS/Form/Wizard/Viewport/Left/Elements/Predefined',
	'TYPO3/CMS/Form/Wizard/Viewport/Left/Elements/Basic',
	'TYPO3/CMS/Form/Wizard/Viewport/Left/Elements',
	'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Forms/Options',
	'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Panel',
	'TYPO3/CMS/Form/Wizard/Viewport/Left/Options/Dummy',
	'TYPO3/CMS/Form/Wizard/Viewport/Left/Options',
	'TYPO3/CMS/Form/Wizard/Viewport/Left/Form/PostProcessor',
	'TYPO3/CMS/Form/Wizard/Viewport/Left/Form/Attributes',
	'TYPO3/CMS/Form/Wizard/Viewport/Left/Form/Prefix',
	'TYPO3/CMS/Form/Wizard/Viewport/Left/Form/Behaviour',
	'TYPO3/CMS/Form/Wizard/Viewport/Left/Form',
	'TYPO3/CMS/Form/Wizard/Viewport/Left',
	'TYPO3/CMS/Form/Wizard/Elements/Basic/Form',
	'TYPO3/CMS/Form/Wizard/Viewport/Right',
	'TYPO3/CMS/Form/Wizard/Viewport'
], function (//Ext,
			 //TYPO3,
			 SplitButtons,
			 TYPO3_CMS_Form_Wizard_Settings,
			 TYPO3_CMS_Form_Wizard_Viewport_Left_Elements_Content,
			 TYPO3_CMS_Form_Wizard_Viewport_Left_Elements_Predefined,
			 TYPO3_CMS_Form_Wizard_Viewport_Left_Elements_Basic,
			 TYPO3_CMS_Form_Wizard_Viewport_Left_Elements,
			 TYPO3_CMS_Form_Wizard_Viewport_Left_Options_Forms_Options,
			 TYPO3_CMS_Form_Wizard_Viewport_Left_Options_Panel,
			 TYPO3_CMS_Form_Wizard_Viewport_Left_Options_Dummy,
			 TYPO3_CMS_Form_Wizard_Viewport_Left_Options,
			 TYPO3_CMS_Form_Wizard_Viewport_Left_Form_PostProcessor,
			 TYPO3_CMS_Form_Wizard_Viewport_Left_Form_Attributes,
			 TYPO3_CMS_Form_Wizard_Viewport_Left_Form_Prefix,
			 TYPO3_CMS_Form_Wizard_Viewport_Left_Form_Behaviour,
			 TYPO3_CMS_Form_Wizard_Viewport_Left_Form,
			 TYPO3_CMS_Form_Wizard_Viewport_Left,
			 TYPO3_CMS_Form_Wizard_Elements_Basic_Form,
			 TYPO3_CMS_Form_Wizard_Viewport_Right,
			 TYPO3_CMS_Form_Wizard_Viewport
) {
	/**
	 * called when built as Object with "new"
	 * 
	 * @constructor
	 */
	var Wizard = function() {
		this.initialize();
	};

	Wizard.prototype.initialize = function() {
		Ext.onReady(function() {
			var transportElId = Ext.get('form-wizard-element-container').dom.getAttribute('rel');
			var transportEl = Ext.get(transportElId).dom;
			var viewport = new TYPO3.Form.Wizard.Viewport({
				renderTo: 'form-wizard-element',
				transportEl: transportEl,
				splitButtons: SplitButtons
			});
			// When the window is resized, the viewport has to be resized as well
			Ext.EventManager.onWindowResize(viewport.doLayout, viewport);
			var relayoutFunction = function(ev) {
				// bootstrap tab handling
				var controlsId = ev.target.getAttribute('aria-controls');
				if(controlsId) {
					var wizardEl = ev.target.parentNode.parentNode.parentNode.querySelector('#' + controlsId + ' ' + '#form-wizard-element');
					if(wizardEl) {
						// we are earlier then bootstrap tab
						setTimeout(function(){
							viewport.doLayout();
						}, 200);
					}
				}
			};
			
			// register tab change events
			/** @var tabsLinks {NodeList} */
			var tabsLinks = document.querySelectorAll('a[data-toggle="tab"]');
			/** @see https://code.google.com/p/v8/issues/detail?id=3953 */
			for(var i = 0; i < tabsLinks.length; i++) {
				var e = tabsLinks[i];
				// event not called, maybe jQuery only
				//e.addEventListener('shown.bs.tab', relayoutFunction, false);
				e.addEventListener('click', relayoutFunction, false);
			}
		});
	};

	/**
	 * executed when module required, return value will be 'this'
	 * @return Wizard
	 */
	return function() {
		return new Wizard();
	}();
});
