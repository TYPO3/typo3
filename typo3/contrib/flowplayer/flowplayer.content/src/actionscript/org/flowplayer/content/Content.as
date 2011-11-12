/* * This file is part of Flowplayer, http://flowplayer.org * * By: Anssi Piirainen, <support@flowplayer.org> * Copyright (c) 2008, 2009 Flowplayer Oy * * Released under the MIT License: * http://www.opensource.org/licenses/mit-license.php */package org.flowplayer.content {    import org.flowplayer.util.Assert;    import org.flowplayer.model.DisplayPluginModel;    import flash.display.DisplayObject;    import flash.display.DisplayObjectContainer;    import flash.events.Event;    import flash.events.MouseEvent;    import org.flowplayer.content.ContentView;    import org.flowplayer.controller.ResourceLoader;    import org.flowplayer.model.Plugin;    import org.flowplayer.model.PluginEventType;    import org.flowplayer.model.PluginModel;    import org.flowplayer.util.Log;    import org.flowplayer.view.AbstractSprite;    import org.flowplayer.view.FlowStyleSheet;    import org.flowplayer.view.Flowplayer;    import org.flowplayer.view.Styleable;    import org.flowplayer.view.StyleableSprite;    /**     * Content plugin.     *     * @author api     */    public class Content extends AbstractSprite implements Plugin, Styleable {        private var _styleSheetFile:String;        private var _player:Flowplayer;        private var _model:PluginModel;        private var _contentView:ContentView;        private var _html:String;        private var _closeButton:Boolean = false;        private var _closeImage:String;        public function Content() {            addListeners();        }        internal function addListeners():void {            addEventListener(MouseEvent.ROLL_OVER, onMouseOver);            addEventListener(MouseEvent.ROLL_OUT, onMouseOut);            addEventListener(MouseEvent.CLICK, onClick);        }        internal function removeListeners():void {            removeEventListener(MouseEvent.ROLL_OVER, onMouseOver);            removeEventListener(MouseEvent.ROLL_OUT, onMouseOut);            removeEventListener(MouseEvent.CLICK, onClick);        }        override protected function onResize():void {            if (!_contentView) return;            _contentView.setSize(width, height);            _contentView.x = 0;            _contentView.y = 0;        }        /**         * Sets the plugin model. This gets called before the plugin         * has been added to the display list and before the player is set.         * @param plugin         */        public function onConfig(plugin:PluginModel):void {            _model = plugin;            if (plugin.config) {                log.debug("config object received with html " + plugin.config.html + ", stylesheet " + plugin.config.stylesheet);                _styleSheetFile = plugin.config.stylesheet;                _html = plugin.config.html;                _closeButton = plugin.config.closeButton;                _closeImage = plugin.config.closeImage;            }        }        /**         * Sets the Flowplayer interface. The interface is immediately ready to use, all         * other plugins have been loaded an initialized also.         * @param player         */        public function onLoad(player:Flowplayer):void {            log.info("set player");            _player = player;            if (_styleSheetFile || _closeImage) {                loadResources(_styleSheetFile, _closeImage);            } else {                createContentView(null, null);                _model.dispatchOnLoad();            }        }        /**         * Sets the HTML content.         * @param htmlText         */        [External]        public function set html(htmlText:String):void {            log.debug("set hetml()");            _contentView.html = htmlText;        }        public function get html():String {            log.debug("get hetml()");            return _contentView.html;        }        /**         * Appends HTML text to the content.         * @param htmlText         * @return the new text after append         */        [External]        public function append(htmlText:String):String {            log.debug("apped()");            return _contentView.append(htmlText);        }        /**         * Loads a new stylesheet and changes the style from the loaded sheet.         */        [External]        public function loadStylesheet(styleSheetFile:String):void {            if (! styleSheetFile) return;            log.info("loading stylesheet from " + styleSheetFile);            loadResources(styleSheetFile);        }        /**         * Sets style properties.         */        public function css(styleProps:Object = null):Object {            var result:Object = _contentView.css(styleProps);            return result;        }        public function get style():FlowStyleSheet {            return _contentView ? _contentView.style : null;        }        public function set style(value:FlowStyleSheet):void {            Assert.notNull(_contentView, "content view not created yet");            _contentView.style = value;        }        private function loadResources(styleSheetFile:String = null, imageFile:String = null):void {            var loader:ResourceLoader = _player.createLoader();            if (styleSheetFile) {                log.debug("loading stylesheet from file " + _styleSheetFile);            }            if (imageFile) {                log.debug("loading closeImage from file " + _closeImage);            }            if (styleSheetFile) {                loader.addTextResourceUrl(styleSheetFile);            }            if (imageFile) {                loader.addBinaryResourceUrl(imageFile);            }            loader.load(null, onResourcesLoaded);        }        private function onResourcesLoaded(loader:ResourceLoader):void {            if (_contentView) {                if (_styleSheetFile) {                    _contentView.style = createStyleSheet(loader.getContent(_styleSheetFile) as String);                }                if (_closeImage) {                    _contentView.closeImage = loader.getContent(_closeImage) as DisplayObject;                }            } else {                createContentView(_styleSheetFile ? loader.getContent(_styleSheetFile) as String : null, _closeImage ? loader.getContent(_closeImage) as DisplayObject : null);            }            _model.dispatchOnLoad();        }        private function createStyleSheet(cssText:String = null):FlowStyleSheet {            var styleSheet:FlowStyleSheet = new FlowStyleSheet("#content", cssText);            // all root style properties come in config root (backgroundImage, backgroundGradient, borderRadius etc)            addRules(styleSheet, _model.config);            // style rules for the textField come inside a style node            addRules(styleSheet, _model.config.style);            return styleSheet;        }        private function addRules(styleSheet:FlowStyleSheet, rules:Object):void {            var rootStyleProps:Object;            for (var styleName:String in rules) {                log.debug("adding additional style rule for " + styleName);                if (FlowStyleSheet.isRootStyleProperty(styleName)) {                    if (! rootStyleProps) {                        rootStyleProps = new Object();                    }                    log.debug("setting root style property " + styleName + " to value " + rules[styleName]);                    rootStyleProps[styleName] = rules[styleName];                } else {                    styleSheet.setStyle(styleName, rules[styleName]);                }            }            styleSheet.addToRootStyle(rootStyleProps);        }        private function createContentView(cssText:String = null, closeImage:DisplayObject = null):void {            log.debug("creating content view");            _contentView = new ContentView(_model as DisplayPluginModel, _player, _closeButton);            if (closeImage) {                _contentView.closeImage = closeImage;            }            log.debug("callign onResize");            onResize(); // make it correct size before adding to display list (avoids unnecessary re-arrangement)            log.debug("setting stylesheet " + cssText);            _contentView.style = createStyleSheet(cssText);            log.debug("setting html");            _contentView.html = _html;            log.debug("adding to display list");            addChild(_contentView);        }        public override function set alpha(value:Number):void {            super.alpha = value;            if (!_contentView) return;            _contentView.alpha = value;        }        private function onMouseOver(event:MouseEvent):void {            if (!_model) return;            if (_contentView.redrawing) return;            _model.dispatch(PluginEventType.PLUGIN_EVENT, "onMouseOver");        }        private function onMouseOut(event:MouseEvent):void {            if (!_model) return;            _model.dispatch(PluginEventType.PLUGIN_EVENT, "onMouseOut");        }        private function onClick(event:MouseEvent):void {            if (!_model) return;            _model.dispatch(PluginEventType.PLUGIN_EVENT, "onClick");        }        public function getDefaultConfig():Object {            return { top: 10, left: '50%', width: '95%', height: 50, opacity: 0.9, borderRadius: 10, backgroundGradient: 'low' };        }        public function animate(styleProps:Object):Object {            return _contentView.animate(styleProps);        }        public function onBeforeCss(styleProps:Object = null):void {        }        public function onBeforeAnimate(styleProps:Object):void {        }    }}