/**
 * Copyright (c) 2007 Moses Gunesch
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
package org.goasap.interfaces {

	/**
	 * Extends IPlayableBase to define a standard set of play controls.
	 * 
	 * <p>The most typical way to create a playable class is to extend PlayableBase,
	 * (which provides state constants and an id property), then manually implement 
	 * this interface to provide play controls.</p>
	 * 
	 * @see org.goasap.PlayableBase PlayableBase
	 * @author Moses Gunesch
	 */
	public interface IPlayable extends IPlayableBase 
	{
		/**
		 * Start playing.
		 */
		function start () : Boolean;

		/**
		 * Stop playing.
		 */
		function stop () : Boolean;
		
		/**
		 * Pause play.
		 */
		function pause () : Boolean;
		
		/**
		 * Resume paused play.
		 */
		function resume () : Boolean;
		
		/**
		 * @param position	Index indicating point in animation to skipTo.
		 * 					(Remember that you can rename paramters when
		 * 					implementing an interface in AS3, for example
		 * 					"seconds" or "index" instead of "position.")
		 */
		function skipTo (position : Number) : Boolean;
	}
}
