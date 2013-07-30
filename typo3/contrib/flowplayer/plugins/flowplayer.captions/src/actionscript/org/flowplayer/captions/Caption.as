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
    import flash.utils.clearInterval;
    import flash.utils.setInterval;

    import org.flowplayer.model.Cuepoint;
    import org.flowplayer.util.Log;
    import org.flowplayer.util.TimeUtil;

    public class Caption {
        private var log:Log = new Log(this);
        private var _textTemplate:String;
        private var _style:Object;
        private var _time:Number = 0;
        private var _duration:Number = 0;
        private var _text:String;
        private var _endTime:Number = 0;
        private var _durationInterval:uint;

        public function Caption(textTemplate:String,  time:Number, duration:Number, text:String, style:Object) {
            _textTemplate = textTemplate;
            _style = style;
            _time = time;
            _duration = duration || 50000;
            _text = text;
        }

        public function get style():Object {
            return _style;
        }

        public function get time():Number {
            return _time;
        }

        public function get duration():Number {
            return _duration;
        }

        public function get text():String {
            return _text;
        }

        public function getHtml(cuepoint:Cuepoint):String {
            var text:String = (_textTemplate ? parseTemplate() : _text);
            text = text.replace(/\n/, '<br>');
            return "<p class='" + _style + "'>" + text + "</p>";
        }

        protected function parseTemplate():String {
            var result:String = _textTemplate;
            result = result.replace("{time}", time);
            result = result.replace("{text}", text);
            return result;
        }

        /**
         * Creates an interval after for the captions duration. Once the caption's
         * duration has passed the specified callback is called.
         *
         * @param time the current playhead time
         * @param callback
         */
        public function setDurationInterval(time:Number, callback:Function):void {
            if (_duration == 0) return;
            _endTime = time * 1000 + duration;
            log.debug("setDurationInterval(), endTime == " + _endTime + ", time == " + time);
            _durationInterval = setInterval(callback, duration);
        }

        public function clearDurationInterval():void {
            clearInterval(_durationInterval);
        }

        public function resumeDurationInterval(time:Number, callback:Function):void {
            if (_endTime == 0) return
            var newDuration:Number = _endTime - time * 1000;
            log.debug("resumeDurationInterval(), new interval duration == " + newDuration + ", endTime == " + _endTime + ", time == " + time);
            if (newDuration > 0) {
                _durationInterval = setInterval(callback, newDuration);
            }
        }
    }
}
