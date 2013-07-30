/*
 * This file is part of Flowplayer, http://flowplayer.org
 *
 * By: Daniel Rossi, <electroteque@gmail.com>
 * Copyright (c) 2009 Electroteque Multimedia
 *
 * Released under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 */

package org.flowplayer.captions.parsers {
    import org.flowplayer.util.Log;
    import org.flowplayer.util.TimeUtil;
    import org.flowplayer.view.FlowStyleSheet;

    public class TTXTParser extends AbstractCaptionParser {
        private var _tt:Namespace = new Namespace("http://www.w3.org/2006/10/ttaf1");

        private var _bodyStyle:String;
        private var _simpleFormatting:Boolean = false;
        private var _cueRow:int = 0;
        internal static const SIMPLE_FORMATTING_PROPS:Array = ["fontStyle", "fontWeight", "textAlign"];

        protected var log:Log = new Log(this);

        public function TTXTParser(textTemplate:String) {
            super(textTemplate);
            default xml namespace = _tt;
        }

        public function get simpleFormatting():Boolean {
            return _simpleFormatting;
        }

        public function set simpleFormatting(simpleFormatting:Boolean):void {
            _simpleFormatting = simpleFormatting;
        }

        private function getStyleObj(style:String):Object {
            return styles.getStyle("." + style);
        }

        override protected function parseCaptions(data:Object):Array {
            var xml:XML = new XML(data);
            log.debug("got data " + xml);
            log.debug("body " + xml.body);
            log.debug("div " + xml.body.div);
            parseStyles(xml.head.styling.style);
            _bodyStyle = xml.body.hasOwnProperty("@style") ? xml.body.@style : styles.rootStyleName;

            var arr:Array = new Array();
            var i:int = 0;

            var div:XMLList = xml.body.div;
            for each (var property:XML in div) {
                log.debug("found div");
                var divStyle:String = property.hasOwnProperty("@style") ? property.@style : _bodyStyle;
                var parent:XML = div.parent().parent();
//                var lang:String = property.hasOwnProperty("@lang") ? property.@*::lang : parent.@*::lang;
                var begin:Number;
                var end:Number;

                if (property.hasOwnProperty("@begin")) {
                    begin = TimeUtil.seconds(property.@begin);
                    end = property.hasOwnProperty("@dur") ? TimeUtil.seconds(property.@dur) : TimeUtil.seconds(property.@end) - begin;
                }

                for each (var p:XML in property.p) {
                    log.debug("found paragraph (p tag)");
                    var time:int = begin ? begin : TimeUtil.seconds(p.@begin);

                    var pStyle:String = getStyleObj(p.@style).hasOwnProperty("color") ? p.@style : divStyle;
                    var duration:int = end ? end : (p.hasOwnProperty("@dur") ? TimeUtil.seconds(p.@dur) : TimeUtil.seconds(p.@end) - time);
                    var name:String = p.hasOwnProperty("@id") ? p.@*::id : (property.hasOwnProperty("@id") ? property.@*::id : "cue" + _cueRow);

                    var content:String = "";
                    for each (var child:XML in p.children()) {
                        if (child.localName() == "br") {
                            content += "<br/>";
                        } else {
                            content += child.toString();
                        }
                    }

                    arr.push(createCuepoint(time,  duration,  content,  name,  pStyle));
                    _cueRow++;
                }
            }
            return arr;
        }


        public function parseStyles(style:XMLList):FlowStyleSheet {

            for each (var styleProperty:XML in style) {
                var styleObj:Object = styleProperty.hasOwnProperty("@style")
                        ? styles.getStyle("." + styleProperty.@style)
                        : {};

                for each (var attr:XML in styleProperty.@*) {
                    var name:String = attr.name().localName;
                    log.debug("style name " + name + ": " + SIMPLE_FORMATTING_PROPS.indexOf(name));
                    if (! _simpleFormatting || SIMPLE_FORMATTING_PROPS.indexOf(name) >= 0) {
                        log.debug("applied style " + name + " to value " + attr);
                        styleObj[name] = attr;
                    }
                }

                styles.setStyle("." + styleProperty.@id, styleObj);
            }
            return styles;
        }

        override protected function get timesInMillis():Boolean {return false;}
    }
}