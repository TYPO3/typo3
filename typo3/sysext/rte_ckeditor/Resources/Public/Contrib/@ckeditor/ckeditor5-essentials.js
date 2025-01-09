import{Plugin as r}from"@ckeditor/ckeditor5-core";import{Clipboard as t}from"@ckeditor/ckeditor5-clipboard";import{Enter as i,ShiftEnter as e}from"@ckeditor/ckeditor5-enter";import{SelectAll as o}from"@ckeditor/ckeditor5-select-all";import{Typing as c}from"@ckeditor/ckeditor5-typing";import{Undo as d}from"@ckeditor/ckeditor5-undo";import{AccessibilityHelp as m}from"@ckeditor/ckeditor5-ui";
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */class k extends r{static get requires(){return[m,t,i,o,e,c,d]}static get pluginName(){return"Essentials"}static get isOfficialPlugin(){return!0}}export{k as Essentials};