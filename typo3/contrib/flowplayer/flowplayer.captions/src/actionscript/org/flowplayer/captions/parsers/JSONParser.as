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
    import org.flowplayer.model.Cuepoint;
    import org.flowplayer.util.Log;
    import org.flowplayer.config.ConfigParser;
    import org.flowplayer.flow_internal;
    import org.flowplayer.view.FlowStyleSheet;
	
	use namespace flow_internal;
	
    public class JSONParser extends AbstractCaptionParser
    {

        protected var log:Log = new Log(this);
        private var _arr:Array = new Array();
        private var cueRow:int = 0;

        private function parseRows(item:*, index:int, array:Array):void
        {

            var time:int = Number(item.time);
            var cue:Object = Cuepoint.createDynamic(time, "embedded"); // creates a dynamic
            var parameters:Object = new Object();
            var name:String = (item.name ? item.name : "cue" + cueRow);
            cue.time = time;
            cue.name = name;
            cue.type = "event";

            if (item.parameters)
            {

                for (var param:String in item.parameters)
                {
                    parameters[param] = item.parameters[param];
                }
            }

            parameters.style = styles.rootStyleName;
            parameters.begin = item.parameters.begin;
            parameters.end = item.parameters.end - item.parameters.begin;
            cue.parameters = parameters;
            _arr.push(cue);
            cueRow++;
        }

        override protected function parseCaptions(data:Object):Array {
        	
        	if (!data is Array) data = ConfigParser.parse(String(data));
            (data as Array).forEach(parseRows);
            return _arr;
        }
    }
}