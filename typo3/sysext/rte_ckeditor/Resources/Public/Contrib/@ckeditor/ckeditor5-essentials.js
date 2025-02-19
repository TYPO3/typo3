import{Plugin as t}from"@ckeditor/ckeditor5-core";import{Clipboard as r}from"@ckeditor/ckeditor5-clipboard";import{Enter as i,ShiftEnter as e}from"@ckeditor/ckeditor5-enter";import{SelectAll as o}from"@ckeditor/ckeditor5-select-all";import{Typing as s}from"@ckeditor/ckeditor5-typing";import{Undo as m}from"@ckeditor/ckeditor5-undo";import{AccessibilityHelp as l}from"@ckeditor/ckeditor5-ui";/**
* @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/class n extends t{static get requires(){return[l,r,i,o,e,s,m]}static get pluginName(){return"Essentials"}static get isOfficialPlugin(){return!0}}export{n as Essentials};
