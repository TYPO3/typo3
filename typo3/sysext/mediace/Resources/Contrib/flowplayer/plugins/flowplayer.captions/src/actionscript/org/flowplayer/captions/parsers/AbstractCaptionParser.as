/*
 * This file is part of Flowplayer, http://flowplayer.org
 *
 * By: Anssi Piirainen, Flowplayer Oy
 * Copyright (c) 2009-2011 Flowplayer Oy
 *
 * Released under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 */
package org.flowplayer.captions.parsers {
    import org.flowplayer.captions.Caption;
    import org.flowplayer.model.Cuepoint;
    import org.flowplayer.view.FlowStyleSheet;

    public class AbstractCaptionParser implements CaptionParser {
        private var _styles:FlowStyleSheet;
        private var _textTemplate:String;

        public function AbstractCaptionParser(textTemplate:String) {
            _textTemplate = textTemplate;
        }

        public final function parse(data:Object):Array {
            var captions:Array = parseCaptions(data);
            for (var i:int = 0; i < captions.length; i++) {
                captions[i]["__caption"] = true;
            }
            return captions;
        }

        protected function parseCaptions(data:Object):Array {
            return null;
        }

        public function get styles():FlowStyleSheet {
            return _styles;
        }

        public function set styles(value:FlowStyleSheet):void {
            _styles = value;
        }

        protected function createCuepoint(time:Number, duration:Number, text:String, name:String = null, style:Object = null):Cuepoint {
            var cue:Object = Cuepoint.createDynamic(time, "embedded"); // creates a dynamic
            // convert to milliseconds
            Cuepoint(cue).time = timeValue(time);
            Cuepoint(cue).name = name ? name : "caption" + time;
            Cuepoint(cue).parameters = new Caption(_textTemplate, time,  timeValue(duration), text,  style ? style : styles.rootStyleName);
            cue["captionType"] = "external";
            return cue as Cuepoint;
        }

        private function timeValue(time:Number):Number {
            return time * (timesInMillis ? 1000 : 1);
        }

        protected function get timesInMillis():Boolean {return true;}
    }

}