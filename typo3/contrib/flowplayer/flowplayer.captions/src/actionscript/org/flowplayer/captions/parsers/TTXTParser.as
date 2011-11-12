/*
 * This file is part of Flowplayer, http://flowplayer.org
 *
 * By: Daniel Rossi, <electroteque@gmail.com>
 * Copyright (c) 2009 Electroteque Multimedia
 *
 * Released under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 */

package org.flowplayer.captions.parsers
{
    import org.flowplayer.captions.NumberFormatter;
    import org.flowplayer.model.Cuepoint;
    import org.flowplayer.util.Log;
    import org.flowplayer.view.FlowStyleSheet;

    public class TTXTParser extends AbstractCaptionParser
    {
        private var tt:Namespace = new Namespace("http://www.w3.org/2006/10/ttaf1");
        private var tts:Namespace = new Namespace("http://www.w3.org/2006/04/ttaf1#styling");
        private var ttm:Namespace = new Namespace("http://www.w3.org/2006/10/ttaf1#metadata");

        private var bodyStyle:String;
        private var _simpleFormatting:Boolean = false;
        private var cueRow:int = 0;
        internal static const SIMPLE_FORMATTING_PROPS:Array = ["fontStyle", "fontWeight", "textAlign"];

        protected var log:Log = new Log(this);

        public function TTXTParser()
        {
        default xml namespace = tt
            ;
        }

        public function get simpleFormatting():Boolean {
            return _simpleFormatting;
        }

        public function set simpleFormatting(simpleFormatting:Boolean):void {
            _simpleFormatting = simpleFormatting;
        }

        private function getStyleObj(style:String):Object
        {
            return styles.getStyle("." + style);
        }


        override protected function parseCaptions(data:Object):Array {
            var xml:XML = new XML(data);
            log.debug("got data " + xml);
            log.debug("body " + xml.body);
            log.debug("div " + xml.body.div);
            parseStyles(xml.head.styling.style);
            bodyStyle = xml.body.hasOwnProperty("@style") ? xml.body.@style : styles.rootStyleName;

            var arr:Array = new Array();
            var i:int = 0;

            var div:XMLList = xml.body.div;
            for each (var property:XML in div)
            {
                log.debug("found div");
                var divStyle:String = property.hasOwnProperty("@style") ? property.@style : bodyStyle;
                var parent:XML = div.parent().parent();
                var lang:String = property.hasOwnProperty("@lang") ? property.@*::lang : parent.@*::lang;
                var begin:Number;
                var end:Number;

                if (property.hasOwnProperty("@begin"))
                {
                    begin = NumberFormatter.seconds(property.@begin);
                    end = property.hasOwnProperty("@dur") ? NumberFormatter.seconds(property.@dur) : NumberFormatter.seconds(property.@end) - begin;
                }

                for each (var p:XML in property.p)
                {
                    log.debug("found paragraph (p tag)");
                    var time:int = begin ? begin : NumberFormatter.seconds(p.@begin);
                    var cue:Object = Cuepoint.createDynamic(time, "embedded");
                    var parameters:Object = new Object();
                    var pStyle:String = getStyleObj(p.@style).hasOwnProperty("color") ? p.@style : divStyle;
                    var endTime:int = end ? end : (p.hasOwnProperty("@dur") ? NumberFormatter.seconds(p.@dur) : NumberFormatter.seconds(p.@end) - time);
                    var name:String = p.hasOwnProperty("@id") ? p.@*::id : (property.hasOwnProperty("@id") ? property.@*::id : "cue" + cueRow);

                    cue.captionType = "external";
                    cue.time = time;

                    cue.name = name;
                    cue.type = "event";
                    parameters.begin = time;
                    parameters.end = endTime;
                    parameters.lang = lang;
                    parameters.style = pStyle;

                    var content:String = "";
                    for each (var child:XML in p.children()) {
                        if (child.localName() == "br") {
                            content += "<br/>";
                        } else {
                            content += child.toString();
                        }
                    }

                    parameters.text = content;
                    cue.parameters = parameters;
                    arr.push(cue);
                    log.debug("added cuepoint " + cue + " with text " + parameters.text);
                    cueRow++;
                }

            }

            return arr;
        }


        public function parseStyles(style:XMLList):FlowStyleSheet
        {

            for each (var styleProperty:XML in style)
            {
                var styleObj:Object = styleProperty.hasOwnProperty("@style")
                        ? styles.getStyle("." + styleProperty.@style)
                        : {};

                for each (var attr:XML in styleProperty.@*)
                {
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

    }
}