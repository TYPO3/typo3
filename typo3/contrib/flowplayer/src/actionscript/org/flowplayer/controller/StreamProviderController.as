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
    import org.flowplayer.config.Config;
    import org.flowplayer.controller.AbstractDurationTrackingController;
    import org.flowplayer.controller.MediaController;
    import org.flowplayer.controller.StreamProvider;
    import org.flowplayer.model.Clip;
    import org.flowplayer.model.ClipEvent;
    import org.flowplayer.model.ClipEventType;
    import org.flowplayer.model.ClipType;
    import org.flowplayer.model.Playlist;
    import org.flowplayer.util.Log;

    import flash.display.DisplayObject;
    import flash.media.Video;

    /**
     * Video controller is responsible for loading and showing video.
     * It's also responsible for scaling and resizing the video screen.
     * It receives the cuePoints and metaData from the loaded video data.
     *
     * @author anssi
     */
    internal class StreamProviderController extends AbstractDurationTrackingController implements MediaController {
        private var _config:Config;
        private var _controllerFactory:MediaControllerFactory;
//		private var _metadataDispatched:Boolean;

        public function StreamProviderController(controllerFactory:MediaControllerFactory, volumeController:VolumeController, config:Config, playlist:Playlist) {
            super(volumeController, playlist);
            _controllerFactory = controllerFactory;
            _config = config;
            var filter:Function = function(clip:Clip):Boolean {
                //allow for chromeless swf video players to be added into the filter
                return clip.type == ClipType.VIDEO || clip.type == ClipType.AUDIO || clip.type == ClipType.API;
            };
           playlist.onBegin(initContent, filter, true);
           playlist.onStart(initContent, filter, true);
        }

        private function initContent(event:ClipEvent):void {
            var clip:Clip = event.target as Clip;
            log.info("onBegin, initializing content for clip " + clip);
            var video:DisplayObject = clip.getContent();
            if (video && video is Video) {
                getProvider(clip).attachStream(video);
            } else {
                video = getProvider(clip).getVideo(clip);
                if (video && video is Video) {
                    getProvider(clip).attachStream(video);
                    if (!video) throw new Error("No video object available for clip " + clip);
                    clip.setContent(video);
                } else if (video) {
                    //we have a chromeless swf video player, add it's display object to the clip content
                    clip.setContent(video);
                }
            }
        }

        override protected function doLoad(event:ClipEvent, clip:Clip, pauseAfterStart:Boolean = false):void {
            getProvider().load(event, clip, pauseAfterStart);
        }

        override protected function doPause(event:ClipEvent):void {
            getProvider().pause(event);
        }

        override protected function doResume(event:ClipEvent):void {
            getProvider().resume(event);
        }

        override protected function doStop(event:ClipEvent, closeStream:Boolean):void {
            getProvider().stop(event, closeStream);
        }

        override protected function doSeekTo(event:ClipEvent, seconds:Number):void {
            durationTracker.time = seconds;
            getProvider().seek(event, seconds);
        }

        override protected function doSwitchStream(event:ClipEvent, clip:Clip, netStreamPlayOptions:Object = null):void {
            var provider:StreamProvider = getProvider();
            provider.switchStream(event, clip, netStreamPlayOptions);
        }

        public override function get time():Number {
            return getProvider().time;
        }

        override protected function get bufferStart():Number {
            return getProvider().bufferStart;
        }

        override protected function get bufferEnd():Number {
            return getProvider().bufferEnd;
        }

        override protected function get fileSize():Number {
            return getProvider().fileSize;
        }

        override protected function get allowRandomSeek():Boolean {
            return getProvider().allowRandomSeek;
        }

        override protected function onDurationReached():void {
            // pause silently
            if (clip.durationFromMetadata > clip.duration) {
                getProvider().pause(null);
            }
        }

        public function getProvider(clipParam:Clip = null):StreamProvider {
            if (!(clipParam || clip)) return null;
            var provider:StreamProvider = _controllerFactory.getProvider(clipParam || clip);
            provider.playlist = playlist;
            return provider;
        }
    }
}
