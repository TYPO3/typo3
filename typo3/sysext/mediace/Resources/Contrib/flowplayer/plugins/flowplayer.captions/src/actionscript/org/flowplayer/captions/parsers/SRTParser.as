/*
 * This file is part of Flowplayer, http://flowplayer.org
 *
 * Subrip Parsing thanks to the as3subtitle Project http://code.google.com/p/as3subtitle/
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

    public class SRTParser extends AbstractCaptionParser {

        protected var log:Log = new Log(this);
        private var _arr:Array = new Array();
        private var cueRow:int = 0;

        public function SRTParser(textTemplate:String) {
            super(textTemplate);
        }

        private function parseRows(item:*, index:int, array:Array):void {
            if (!item) return;
            log.debug("parsing " + item);
            var rows:Array = item.split(/\r?\n/);
            var time_pattern:RegExp = /(\d{2}:\d{2}:\d{2}(?:,\d*)?) --> (\d{2}:\d{2}:\d{2}(?:,\d*)?)/;
            var hasValidTime:Boolean = time_pattern.test(rows[1]);

            if (!hasValidTime) {
                log.error("Invalid time format for #" + (rows[0]) + item);
                return;
            }

            var time:Array = time_pattern.exec(rows[1]);
            var text:String = rows.slice(2, rows.length).join("\n");
            var begin:Number = TimeUtil.seconds(time[1]);
            var duration:Number = (TimeUtil.seconds(time[2]) - begin);
            log.debug("" + duration);
            var name:String = (rows[0] ? rows[0] : "cue" + cueRow);

            _arr.push(createCuepoint(begin, duration, text, name));
            cueRow++;
        }

        override protected function parseCaptions(data:Object):Array {
            log.debug("parseCaptions");
            var line_break:RegExp = /\n\r?\n/;
            var subtitles:Array = String(data).split(line_break);
            subtitles.forEach(parseRows);
            return _arr;
        }

        override protected function get timesInMillis():Boolean {return false;}
    }
}