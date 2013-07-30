/*    
 *    Copyright (c) 2008-2011 Flowplayer Oy *
 *    This file is part of Flowplayer.
 *
 *    Flowplayer is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    Flowplayer is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with Flowplayer.  If not, see <http://www.gnu.org/licenses/>.
 */

package org.flowplayer.controller {
    import flash.net.NetConnection;
    import flash.net.NetStream;
    import flash.utils.Dictionary;

    import org.flowplayer.model.Clip;
    import org.flowplayer.model.ClipEvent;
    import org.flowplayer.model.Playlist;

    import flash.display.DisplayObject;

    /**
     * StreamProviders are used to load video content into the player. They are used to
     * integrate to different streaming servers and Content Delivery Networks (CDNs).
     *
     * Usually in the Flash platform providers are implemented using
     * <a href="flash.net.NetStream">http://livedocs.adobe.com/flash/9.0/ActionScriptLangRefV3/flash/net/NetStream.html</a>.
     */
    public interface StreamProvider {

        /**
         * Starts loading the specivied clip. Once video data is available the provider
         * must set it to the clip using <code>clip.setContent()</code>. Typically the video
         * object passed to the clip is an instance of <a href="http://livedocs.adobe.com/flash/9.0/ActionScriptLangRefV3/flash/media/Video.html">flash.media.Video</a>.
         *
         * @param event the event that this provider should dispatch once loading has successfully started,
         * once dispatched the player will call <code>getVideo()</code>
         * @param clip the clip to load
         * @param pauseAfterStart if <code>true</code> the playback is paused on first frame and
         * buffering is continued
         * @see Clip#setContent()
         * @see #getVideo()
         */
        function load(event:ClipEvent, clip:Clip, pauseAfterStart:Boolean = true):void;

        /**
         * Gets the <a href="http://livedocs.adobe.com/flash/9.0/ActionScriptLangRefV3/flash/media/Video.html">Video</a> object.
         * A stream will be attached to the returned video object using <code>attachStream()</code>.
         * @param clip the clip for which the Video object is queried for
         * @see #attachStream()
         */
        function getVideo(clip:Clip):DisplayObject;

        /**
         * Attaches a stream to the specified display object.
         * @param video the video object that was originally retrieved using <code>getVideo()</code>.
         * @see #getVideo()
         */
        function attachStream(video:DisplayObject):void;

        /**
         * Pauses playback.
         * @param event the event that this provider should dispatch once loading has been successfully paused
         */
        function pause(event:ClipEvent):void;

        /**
         * Resumes playback.
         * @param event the event that this provider should dispatch once loading has been successfully resumed
         */
        function resume(event:ClipEvent):void;

        /**
         * Stops and rewinds to the beginning of current clip.
         * @param event the event that this provider should dispatch once loading has been successfully stopped
         */
        function stop(event:ClipEvent, closeStream:Boolean = false):void;

        /**
         * Seeks to the specified point in the timeline.
         * @param event the event that this provider should dispatch once the seek is in target
         * @param seconds the target point in the timeline
         */
        function seek(event:ClipEvent, seconds:Number):void;

        /**
         * File size in bytes.
         */
        function get fileSize():Number;

        /**
         * Current playhead time in seconds.
         */
        function get time():Number;

        /**
         * The point in timeline where the buffered data region begins, in seconds.
         */
        function get bufferStart():Number;

        /**
         * The point in timeline where the buffered data region ends, in seconds.
         */
        function get bufferEnd():Number;

        /**
         * Does this provider support random seeking to unbuffered areas in the timeline?
         */
        function get allowRandomSeek():Boolean;

        /**
         * Volume controller used to control the video volume.
         */
        function set volumeController(controller:VolumeController):void;

        /**
         * Is this provider in the process of stopping the stream?
         * When stopped the provider should not dispatch any events resulting from events that
         * might get triggered by the underlying streaming implementation.
         */
        function get stopping():Boolean;

        /**
         * The playlist instance.
         */
        function set playlist(playlist:Playlist):void;

        function get playlist():Playlist;

        /**
         * Adds a callback function to the NetConnection instance. This function will fire ClipEvents whenever
         * the callback is invoked in the connection.
         * @param name
         * @param listener
         * @return
         * @see ClipEventType#CONNECTION_EVENT
         */
        function addConnectionCallback(name:String, listener:Function):void;

        /**
         * Adds a callback function to the NetStream object. This function will fire a ClipEvent of type StreamEvent whenever
         * the callback has been invoked on the stream. The invokations typically come from a server-side app running
         * on RTMP server.
         * @param name
         * @param listener
         * @return
         * @see ClipEventType.NETSTREAM_EVENT
         */
        function addStreamCallback(name:String, listener:Function):void;

        /**
         * Get the current stream callbacks.
         * @return a dictionary of callbacks, keyed using callback names and values being the callback functions
         */
        function get streamCallbacks():Dictionary;

        /**
         * Gets the underlying NetStream object.
         * @return the netStream currently in use, or null if this provider has not started streaming yet
         */
        function get netStream():NetStream;

        /**
         * Gets the underlying netConnection object.
         * @return the netConnection currently in use, or null if this provider has not started streaming yet
         */
        function get netConnection():NetConnection;


        /**
         * Sets a time provider to be used by this StreamProvider. Normally the playhead time is queried from
         * the NetStream.time property.
         *
         * @param timeProvider
         */
        function set timeProvider(timeProvider:TimeProvider):void;

        /**
         * Gets the type of StreamProvider either http, rtmp, psuedo.
         */
        function get type():String;

        /**
         * Switch the stream in realtime with / without dynamic stream switching support
         *
         * @param event ClipEvent the clip event
         * @param clip Clip the clip to switch to
         * @param netStreamPlayOptions Object the NetStreamPlayOptions object to enable dynamic stream switching
         */
        function switchStream(event:ClipEvent, clip:Clip, netStreamPlayOptions:Object = null):void;
    }
}
