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

    public interface CaptionParser {

        function parse(data:Object):Array;

        function set styles(style:FlowStyleSheet):void;

        function get styles():FlowStyleSheet;

    }
}