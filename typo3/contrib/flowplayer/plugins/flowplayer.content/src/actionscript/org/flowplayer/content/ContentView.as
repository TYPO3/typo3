/*
 * This file is part of Flowplayer, http://flowplayer.org
 *
 * By: Anssi Piirainen, <support@flowplayer.org>
 * Copyright (c) 2008-2011 Flowplayer Oy *
 * Released under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 */
package org.flowplayer.content {
    import flash.filters.GlowFilter;

    import org.flowplayer.model.DisplayPluginModel;
    import org.flowplayer.view.FlowStyleSheet;
    import org.flowplayer.view.Flowplayer;
    import org.flowplayer.view.StyleableSprite;

    import flash.display.BlendMode;
    import flash.display.DisplayObject;
    import flash.display.Sprite;
    import flash.events.MouseEvent;
    import flash.text.AntiAliasType;
    import flash.text.TextField;
    import flash.text.TextFieldAutoSize;

    /**
     * @author api
     */
    public class ContentView extends StyleableSprite {

        private var _text:TextField;
        private var _textMask:Sprite;
        private var _htmlText:String;
        private var _player:Flowplayer;

        public function ContentView(player:Flowplayer) {
            super(null, player, player.createLoader());
            _player = player;
        }

        override protected function onSetStyle(style:FlowStyleSheet):void {
            log.debug("onSetStyle");
            createTextField(_text ? _text.htmlText : null);
        }

        override protected function onSetStyleObject(styleName:String, style:Object):void {
            log.debug("onSetStyleObject");
            createTextField(_text ? _text.htmlText : null);
        }

        public function set html(htmlText:String):void {
            _htmlText = htmlText;
            if (! _htmlText) {
                _htmlText = "";
            }
            _text.htmlText = "<body>" + _htmlText + "</body>";
            log.debug("set html to " + _text.htmlText);
        }

        public function get html():String {
            return _htmlText;
        }

        public function append(htmlText:String):String {
            html = _htmlText + htmlText;
            log.debug("appended html to " + _text.htmlText);
            return _htmlText;
        }

        private function createTextField(htmlText:String = null):void {
            log.debug("creating text field for text " + htmlText);
            if (_text) {
                removeChild(_text);
            }
            _text = _player.createTextField();
            _text.blendMode = BlendMode.LAYER;
            _text.autoSize = TextFieldAutoSize.CENTER;
            _text.wordWrap = true;
            _text.multiline = true;
            _text.antiAliasType = AntiAliasType.ADVANCED;
            _text.condenseWhite = true;

            log.info("style.textDecoration " + style.textDecoration);
            if (style.textDecoration == "outline") {
                log.debug("setting textDecoration")
                var glow:GlowFilter = new GlowFilter(0, .80, 2, 4, 6);
                var filters:Array = [glow];
                _text.filters = filters;
            }

            addChild(_text);
            if (style) {
                _text.styleSheet = style.styleSheet;
            }
            if (htmlText) {
                log.debug("setting html to " + htmlText);
                html = htmlText;
            }
            _textMask = createMask();
            addChild(_textMask);
            _text.mask = _textMask;
            arrangeText();
        }

        private function arrangeText():void {
            if (! (_text && style)) return;
            var padding:Array = style.padding;
            log.debug("arranging text with padding " + padding + " height is " + height);
            // only reset values if they change, otherwise there will be visual "blinking" of text/images
            setTextProperty("y", padding[0]);
            setTextProperty("x", padding[3]);
            setTextProperty("height", height - padding[0] - padding[2]);
            setTextProperty("width", width - padding[1] - padding[3]);
        }

        private function setTextProperty(prop:String, value:Number):void {
            if (_text[prop] != value) {
                log.debug("setting text property " + prop + " to value " + value);
                _text[prop] = value;
            }
        }

        override protected function onRedraw():void {
              arrangeText();
        }

        override protected function onResize():void {
            if (_textMask) {
                _textMask.width = width;
                _textMask.height = height;
            }
        }

        override public function set alpha(value:Number):void {
            super.alpha = value;
            if (! _text) return;
            _text.alpha = value;
        }
    }
}
