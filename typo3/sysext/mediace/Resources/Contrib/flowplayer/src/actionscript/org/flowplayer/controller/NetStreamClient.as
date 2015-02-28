/*
 *    Copyright 2008 Anssi Piirainen
 *
 *    This file is part of FlowPlayer.
 *
 *    FlowPlayer is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    FlowPlayer is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with FlowPlayer.  If not, see <http://www.gnu.org/licenses/>.
 */

package org.flowplayer.controller {


    import flash.utils.Dictionary;

    import org.flowplayer.config.Config;
    import org.flowplayer.model.Clip;
    import org.flowplayer.model.ClipEventType;
    import org.flowplayer.util.Log;
    import org.flowplayer.util.ObjectConverter;

    /**
     * @author api
     */
    public dynamic class NetStreamClient implements NetStreamCallbacks {

        private var log:Log = new Log(this);
        private var _config:Config;
        private var _clip:Clip;
        private var _previousUrl:String;

        public function NetStreamClient(clip:Clip, config:Config, streamCallbacks:Dictionary) {
            _clip = clip;
            _config = config;
            for (var key:Object in streamCallbacks) {
                addStreamCallback(key as String, streamCallbacks[key]);
            }
        }

        public function onMetaData(infoObject:Object):void {

            log.info("onMetaData, current clip " + _clip);

            log.debug("onMetaData, data for clip " + _clip + ":");
            var metaData:Object = new Object();
            for (var key:String in infoObject) {
                if (key == "duration" && _clip && _clip.metaData && _clip.metaData.duration) {
                    log.debug("Already got duration, reusing old one");
                    metaData.duration = _clip.metaData.duration;
                } else {
                    var cKey:String = new ObjectConverter(key).convertKey();

                    metaData[cKey] = new Object();
                    if (infoObject[key] is Array)
                        metaData[cKey] = new Array();

                    if (needsRecursing(infoObject[key])) {
                        for (var subKey:String in infoObject[key]) {
                            var cSubKey:String = new ObjectConverter(subKey).convertKey();
                            metaData[cKey][cSubKey] =
                                    (needsRecursing(infoObject[key][subKey]))
                                            ? checkChild(infoObject[key][subKey])
                                            : infoObject[key][subKey];
                        }
                    } else {
                        metaData[cKey] = infoObject[key];
                    }
                }
            }

            log.debug("metaData : ", metaData);


            if (metaData.cuePoints && _clip.cuepoints.length == 0) {
                log.debug("clip has embedded cuepoints");
                _clip.addCuepoints(_config.createCuepoints(metaData.cuePoints, "embedded", _clip.cuepointMultiplier));
            }

            _previousUrl = _clip.url;

            //#50 if we have metadata already set it is being updated during seeks and switching, dispatch metadata change events instead.
            if (_clip.metaData) {
                _clip.metaData = metaData;
                _clip.dispatch(ClipEventType.METADATA_CHANGED);
            } else {
                _clip.metaData = metaData;
                _clip.dispatch(ClipEventType.METADATA);
            }

            log.info("metaData parsed and injected to the clip");
        }

        private function checkChild(obj:Object):Object {
            var objToReturn:Object = new Object();
            if (obj is Array)
                objToReturn = new Array();

            for (var key:String in obj) {
                var cKey:String = new ObjectConverter(key).convertKey();
                if (needsRecursing(obj[key]))
                    objToReturn[cKey] = checkChild(obj[key]);
                else
                    objToReturn[cKey] = obj[key];
            }
            return objToReturn;
        }

        private function needsRecursing(newVal:*):Boolean {
            return ! (newVal is Number || newVal is String || newVal is Boolean);
        }

        public function onXMPData(infoObject:Object):void {
            _clip.dispatchNetStreamEvent("onXMPData", infoObject);
        }

        public function onCaption(cps:String, spk:Number):void {
            _clip.dispatchNetStreamEvent("onCaption", { 'cps': cps, 'spk': spk });
        }

        public function onCaptionInfo(infoObject:Object):void {
            _clip.dispatchNetStreamEvent("onCaptionInfo", infoObject);
        }

        public function onImageData(infoObject:Object):void {
            _clip.dispatchNetStreamEvent("onImageData", infoObject);
        }

        public function RtmpSampleAccess(infoObject:Object):void {
            _clip.dispatchNetStreamEvent("RtmpSampleAccess", infoObject);
        }

        public function onTextData(infoObject:Object):void {
            _clip.dispatchNetStreamEvent("onTextData", infoObject);
        }

        public function onPlayStatus(...rest):void {
            //some wowza servers use different arguments.
            var info:Object = rest.length > 1 ? rest[2] : rest[0];
            _clip.dispatch(ClipEventType.PLAY_STATUS, info);
        }

        private function addStreamCallback(name:String, listener:Function):void {
            log.debug("registering callback " + name);
            this[name] = listener;
        }

        public function registerCallback(name:String):void {
            _clip.dispatchNetStreamEvent("registerCallback", name);
        }

//
//        public function onCuePoint(infoObject:Object):void {
//            _clip.dispatchNetStreamEvent("onCuePoint", infoObject);
//        }
    }
}