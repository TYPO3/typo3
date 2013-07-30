/*
 * This file is part of Flowplayer, http://flowplayer.org
 *
 * By: Anssi Piirainen, <api@iki.fi>
 *
 * Copyright (c) 2008-2011 Flowplayer Oy
 *
 * Released under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 */
package org.flowplayer.captions {
    import flash.display.DisplayObject;
    import flash.events.MouseEvent;

    import org.flowplayer.layout.LayoutEvent;

    import org.flowplayer.model.DisplayPluginModel;
    import org.flowplayer.model.PlayerEvent;
    import org.flowplayer.model.PlayerEventType;
    import org.flowplayer.util.Log;
    import org.flowplayer.view.FlowStyleSheet;
    import org.flowplayer.view.Flowplayer;
    import org.flowplayer.view.Styleable;

    internal class CaptionViewDelegate {
        private var log:Log = new Log(this);
        private var _config:Config;
        private var _viewModel:DisplayPluginModel;
        private var _player:Flowplayer;
        private var _view:*;
        private var _plugin:CaptionPlugin;
        private var _button:CCButton;
        private var _captionHeightRatio:Number;
        private var _captionWidthRatio:Number;
        private var _captionFontSizes:Object;
        private var _viewOrigWidth:int = 0;

        public function CaptionViewDelegate(plugin:CaptionPlugin, player:Flowplayer, config:Config) {
            _plugin = plugin;
            _player = player;
            _config = config;
            initCaptionView();
            initCCButton();
            addListeners();
        }

        private function addListeners():void {
            _player.onFullscreen(resizeCaptionView);
            _player.onFullscreenExit(resizeCaptionView);
            _player.onBeforeFullscreen(function(event:PlayerEvent):void {
                _viewOrigWidth = _view.width;
            });        }

        protected function initCaptionView():void {
            log.debug("creating content view");
            if (_config.captionTarget) {
                log.info("Loading caption target plugin: " + _config.captionTarget);

                _viewModel = _player.pluginRegistry.getPlugin(_config.captionTarget) as DisplayPluginModel;
                if (_viewModel != null) {
                    _view = _viewModel.getDisplayObject() as Styleable;
                }

                if (_config.autoLayout) {
                    _view.css(_plugin.getDefaultConfig());
                }
            } else {
                throw new Error("No caption target specified, please configure a Content plugin instance to be used as target");
            }

            if (! _viewModel.visible) {
                _view.alpha = 0;
                _player.togglePlugin(_config.captionTarget);
                setCaptionViewRatios();
                _player.togglePlugin(_config.captionTarget);
                _view.alpha = 1;
            }
            setCaptionViewRatios();
        }

        private function setCaptionViewRatios():void {
            _captionHeightRatio = _view.height / _player.screen.getDisplayObject().height;
            _captionWidthRatio = _view.width / _player.screen.getDisplayObject().width;
            log.debug("setCaptionViewRatios(): " + _captionWidthRatio + "x" + _captionHeightRatio);
        }

        private function initCCButton():void {
            log.debug("button", _config.button);
            if (_config.button) {
                _button = new CCButton(_player, _config.button["label"]);
                _player.addToPanel(_button, _config.button);

                _button.isDown = _viewModel.visible;
                _button.clickArea.addEventListener(MouseEvent.CLICK, function(event:MouseEvent):void {
                    _button.isDown = _player.togglePlugin(_config.captionTarget);
                });
            }
        }

        private function resizeCaptionView(event:PlayerEvent):void {
            var newWidth:Number = _player.screen.getDisplayObject().width * _captionWidthRatio;
            var newHeight:Number = _player.screen.getDisplayObject().height * _captionHeightRatio;

            //	log.info("resizing, width:" +_player.screen.getDisplayObject().width + " * "+_captionWidthRatio+" = "+ newWidth);
            //	log.info("resizing, width:" +_player.screen.getDisplayObject().height + " * "+_captionHeightRatio+" = "+ newHeight);

            if (event.type == (PlayerEventType.FULLSCREEN).name) {
                log.debug("setting font size for fullscreen");
                _captionFontSizes = {};
                var styleNames:Array = _view.style.styleSheet.styleNames;
                for (var i:int = 0; i < styleNames.length; i++) {
                    if (_view.style.getStyle(styleNames[i]).fontSize) {
                        log.debug("found fontSize style");
                        var style:Object = _view.style.getStyle(styleNames[i]);

                        _captionFontSizes[styleNames[i]] = style.fontSize;

                        //	log.info ("current font size "+style.fontSize+", ratio "+ newWidth+"/"+_view.width+" = "+(newWidth / _viewWidth));
                        style.fontSize = style.fontSize * newWidth / _viewOrigWidth;
                        log.debug("new fontSize == " + style.fontSize);
                        _view.style.setStyle(styleNames[i], style);
                    }
                }
            }
            else {    // setting back fontsizes ..
                for (var styleName:String in _captionFontSizes) {
                    style = _view.style.getStyle(styleName);
                    style.fontSize = _captionFontSizes[styleName];
                    _view.style.setStyle(styleName, style);
                }
            }

            var newY:Number = _view.y;
            if (newY > _player.screen.getDisplayObject().height / 2)
                newY = _view.y - (newHeight - _view.height);

            var newX:Number = _view.x - (newWidth - _view.width);

            _player.css(_config.captionTarget, {y: newY, x: newX, height: newHeight, width: newWidth});
        }

        private function onPlayerResized(event:LayoutEvent):void {
            log.debug("onPlayerResized");
            _button.x = _view.x + _view.width + 3;
            _button.y = _view.y;
        }

        internal function get style():FlowStyleSheet {return _view.style;}

        internal function set html(html:String):void {_view.html = html;}

        internal function css(styleObj:Object):void {
            _view.css(styleObj);
        }
    }
}
