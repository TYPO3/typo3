/*
 *    Copyright 2009 Flowplayer Oy
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
    import flash.events.TimerEvent;
    import flash.utils.Timer;

    import org.flowplayer.model.Clip;
    import org.flowplayer.util.Log;
    import org.flowplayer.flow_internal;

    use namespace flow_internal;
    
    public class InStreamTracker {
        private var _controller:PlayListController;
        private var _timer:Timer;
        private var log:Log = new Log(this);
        private var _prevStartTime:Number = 0;

        public function InStreamTracker(controller:PlayListController) {
            _controller = controller;
        }

        public function start(doReset:Boolean = false):void {
            log.debug("start()");
            if (! clip.hasChildren) {
                throw new Error("this clip does not have child clips");
            }

            if (doReset) {
                reset();
            }

            var children:Array = clip.playlist;
            for (var i:int = 0; i < children.length; i++) {
                var clip:Clip = children[i] as Clip;
                log.debug("start(): child clip at " + clip.position + ": " + clip);
            }

            if (! _timer) {
                _timer = new Timer(200);
                _timer.addEventListener(TimerEvent.TIMER, onTimer);
            }
            _timer.start();
        }

        public function stop():void {
            log.debug("stop()");
            if (_timer && _timer.running) {
                _timer.stop();
            }
        }

        private function onTimer(event:TimerEvent):void {
            var time:Number = _controller.status.time;
            log.debug("time " + Math.round(time));
            var child:Clip = clip.getMidroll(time);
            if (child && time - _prevStartTime > 2) {
                stop();
                log.info("found child clip with start time " + time + ": " + child);
                _controller.playInstream(child);
                _prevStartTime = child.position;
            }
        }

        private function get clip():Clip {
            return _controller.playlist.current;
        }

        public function reset():void {
            log.debug("reset()");
            _prevStartTime = 0;
        }
    }

}