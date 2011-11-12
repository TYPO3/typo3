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
package org.flowplayer.view {
    import flash.display.Sprite;
    import flash.events.TimerEvent;
import flash.utils.Timer;

    public class RotatingAnimation extends AbstractSprite {
        private var _rotationImage:BufferAnimation;
        private var _rotation:Sprite;
        private var _rotationTimer:Timer;

        public function RotatingAnimation() {
            createRotation();
            _rotationTimer = new Timer(50);
            _rotationTimer.addEventListener(TimerEvent.TIMER, rotate);
            _rotationTimer.start();
        }

        public function start():void {
            _rotationTimer.start();
        }

        public function stop():void {
            _rotationTimer.stop();
        }

        protected override function onResize():void {
            arrangeRotation(width, height);
        }

        private function rotate(event:TimerEvent):void {
            _rotation.rotation += 10;
        }

        private function createRotation():void {
            _rotationImage = new BufferAnimation();
            _rotation = new Sprite();
            _rotation.addChild(_rotationImage);
            addChild(_rotation);
        }

        private function arrangeRotation(width:Number, height:Number):void {
            if (_rotationImage) {
                _rotationImage.height = height;
                _rotationImage.scaleX = _rotationImage.scaleY;

                _rotationImage.x =  - _rotationImage.width / 2;
                _rotationImage.y = - _rotationImage.height / 2;
                _rotation.x = _rotationImage.width / 2 + (width - _rotationImage.width)/2;
                _rotation.y = _rotationImage.height / 2 + (height - _rotationImage.height)/2;
            }
        }
    }
}