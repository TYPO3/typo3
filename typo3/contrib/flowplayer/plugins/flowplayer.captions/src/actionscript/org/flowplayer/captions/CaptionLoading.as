/*    
 *    Author: Anssi Piirainen, <api@iki.fi>
 *
 *    Copyright (c) 2010 Flowplayer Oy
 *
 *    This file is part of Flowplayer.
 *
 *    Flowplayer is licensed under the GPL v3 license with an
 *    Additional Term, see http://flowplayer.org/license_gpl.html
 */
package org.flowplayer.captions {
    import org.flowplayer.view.FlowStyleSheet;
    import org.flowplayer.model.Clip;

    internal interface CaptionLoading {

         function loadClipCaption(clip:Clip, loadedCallback:Function):void;
        /**
         * Loads all required caption files and keeps the loaded data.
         */
        function load(loadedCallback:Function):void;

        /**
         * Are all caption files loaded?
         */
        function get loaded():Boolean;

        /**
         * Sets the root style to be used in parsing caption data.
         */
        function set rootStyle(style:FlowStyleSheet):void;

        /**
         * Parses all loaded caption data and adds clip cuepoints corresponding
         * to the parsed captions.
         */
        function parse():void;
    }

}