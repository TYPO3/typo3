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
    import org.flowplayer.view.FlowStyleSheet;
    import org.flowplayer.view.FlowStyleSheet;

    public class AbstractCaptionParser implements CaptionParser {
        private var _styles:FlowStyleSheet;

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
    }

}