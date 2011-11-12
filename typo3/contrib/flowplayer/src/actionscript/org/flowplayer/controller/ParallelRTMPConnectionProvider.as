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
    import flash.events.NetStatusEvent;
    import flash.events.NetStatusEvent;

    import flash.events.TimerEvent;
    import flash.net.NetConnection;

    import flash.utils.Timer;

    import org.flowplayer.controller.ConnectionProvider;
    import org.flowplayer.controller.DefaultRTMPConnectionProvider;
    import org.flowplayer.controller.NetStreamControllingStreamProvider;
    import org.flowplayer.controller.StreamProvider;
    import org.flowplayer.model.Clip;
    import org.flowplayer.model.Clip;
    import org.flowplayer.util.Log;

    /**
     * @author api
     */
    public class ParallelRTMPConnectionProvider implements ConnectionProvider {

        protected var log:Log = new Log(this);
        //		private var _config:Config;

        protected var _successListener:Function;
        protected var _failureListener:Function;
        protected var _connectionClient:Object;
        protected var _connector1:ParallelRTMPConnector;
        protected var _connector2:ParallelRTMPConnector;
        protected var _connection:NetConnection;

        protected var _netConnectionUrl:String;
        protected var _proxyType:String;
        protected var _failOverDelay:int;

        public function ParallelRTMPConnectionProvider(netConnectionUrl:String, proxyType:String = "best", failOverDelay:int = 250) {
            _netConnectionUrl = netConnectionUrl;
            _proxyType = proxyType;
            _failOverDelay = failOverDelay;
        }

        public function connect(ignored:StreamProvider, clip:Clip, successListener:Function, objectEncoding:uint, connectionArgs:Array):void {

            _successListener = successListener;
            _connection = null;

            var configuredUrl:String = getNetConnectionUrl(clip)
            if (! configuredUrl && _failureListener != null) {
                _failureListener("netConnectionURL is not defined");
            }
            var parts:Array = getUrlParts(configuredUrl);
            var connArgs:Array = (clip.getCustomProperty("connectionArgs") as Array) || connectionArgs;

            if (parts && (parts[0] == 'rtmp' || parts[0] == 'rtmpe')) {

                log.debug("will connect using RTMP and RTMPT in parallel, connectionClient " + _connectionClient);
                _connector1 = createConnector((parts[0] == 'rtmp' ? 'rtmp' : 'rtmpe') + '://' + parts[1]);
                _connector2 = createConnector((parts[0] == 'rtmp' ? 'rtmpt' : 'rtmpte') + '://' + parts[1]);

                doConnect(_connector1, _proxyType, objectEncoding, connArgs);

                // RTMPT connect is started after 250 ms
                var delay:Timer = new Timer(_failOverDelay, 1);
                delay.addEventListener(TimerEvent.TIMER, function(event:TimerEvent):void {
                    doConnect(_connector2, _proxyType, objectEncoding, connectionArgs);
                });
                delay.start();

            } else {
                log.debug("connecting to URL " + configuredUrl);
                _connector1 = createConnector(configuredUrl);
                doConnect(_connector1, _proxyType, objectEncoding, connArgs);
            }
        }

        protected function createConnector(url:String):ParallelRTMPConnector {
            return new ParallelRTMPConnector(url, connectionClient, onConnectorSuccess, onConnectorFailure);
        }

        private function doConnect(connector1:ParallelRTMPConnector, proxyType:String, objectEncoding:uint, connectionArgs:Array):void {
            if (connectionArgs.length > 0) {
                connector1.connect(_proxyType, objectEncoding, connectionArgs);
            } else {
                connector1.connect(_proxyType, objectEncoding, null);
            }
        }

        protected function onConnectorSuccess(connector:ParallelRTMPConnector, connection:NetConnection):void {
            log.debug(connector + " established a connection");
            if (_connection) return;
            _connection = connection;

            if (connector == _connector2 && _connector1) {
                _connector1.stop();
            } else if (_connector2) {
                _connector2.stop();
            }
            _successListener(connection);
        }

        protected function onConnectorFailure():void {
            if (isFailedOrNotUsed(_connector1) && isFailedOrNotUsed(_connector2) && _failureListener != null) {
                _failureListener();
            }
        }

        private function isFailedOrNotUsed(connector:ParallelRTMPConnector):Boolean {
            if (! connector) return true;
            return connector.failed;
        }

        private function getUrlParts(url:String):Array {
            var pos:int = url.indexOf('://');
            if (pos > 0) {
                return [url.substring(0, pos), url.substring(pos + 3)];
            }
            return null;
        }

        protected function getNetConnectionUrl(clip:Clip):String {
            if (isRtmpUrl(clip.completeUrl)) {
                log.debug("clip has complete rtmp url");
                var url:String = clip.completeUrl;
                var lastSlashPos:Number = url.lastIndexOf("/");
                return url.substring(0, lastSlashPos);
            }
            if (clip.customProperties && clip.customProperties.netConnectionUrl) {
                log.debug("clip has netConnectionUrl as a property " + clip.customProperties.netConnectionUrl);
                return clip.customProperties.netConnectionUrl;
            }
            log.debug("using netConnectionUrl from config" + _netConnectionUrl);
            return _netConnectionUrl;
        }

        protected function isRtmpUrl(url:String):Boolean {
            return url && url.toLowerCase().indexOf("rtmp") == 0;
        }

        public function set connectionClient(client:Object):void {
            log.debug("received connection client " + client);
            _connectionClient = client;
        }

        public function get connectionClient():Object {
            if (! _connectionClient) {
                _connectionClient = new NetConnectionClient();
            }
            log.debug("using connection client " + _connectionClient);
            return _connectionClient;
        }

        public function set onFailure(listener:Function):void {
            _failureListener = listener;
        }

        public function handeNetStatusEvent(event:NetStatusEvent):Boolean {
            return true;
        }

        public function get connection():NetConnection {
            return _connection;
        }
    }
}
