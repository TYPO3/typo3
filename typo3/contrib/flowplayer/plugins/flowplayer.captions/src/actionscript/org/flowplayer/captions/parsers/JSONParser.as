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
    import org.flowplayer.config.ConfigParser;
    import org.flowplayer.flow_internal;
    import org.flowplayer.util.Log;

    use namespace flow_internal;

    public class JSONParser extends AbstractCaptionParser {
        protected var log:Log = new Log(this);
        private var _arr:Array = new Array();

        public function JSONParser(textTemplate:String) {
            super(textTemplate);
        }

        // { text: 'captionText', time: 10, duration: 3 }
        override protected function parseCaptions(data:Object):Array {
            if (data is String) data = ConfigParser.parse(String(data));


            (data as Array).forEach(function(item:*, index:int, array:Array):void {
                _arr.push(createCuepoint(Number(item.time), item.duration, item.text));
            });
            return _arr;
        }
    }
}