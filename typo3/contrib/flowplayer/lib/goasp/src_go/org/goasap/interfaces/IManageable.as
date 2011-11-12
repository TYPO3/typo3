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
	 * Makes udpatable items usable by IManager instances.
	 * 
	 * <p>The Go system decouples manager classes so they remain compile-
	 * optional for the end user, who must explicitly register an instance 
	 * of each desired manager for use with GoEngine. <i>To uphold this system
	 * it is extremely important that item classes do not import or make 
	 * direct reference to specific manager classes. If you need to make a
	 * reference to a manager from any item class, datatype to manager interfaces
	 * like IManager, not manager classes like OverlapMonitor.</i></p>
	 * 
	 * @see IManager
	 * 
	 * @author Moses Gunesch
	 */
	public interface IManageable extends IUpdatable
	{
		/**
		 * IManageable requirement.
		 * 
		 * @return	All animation targets currently being handled.
		 */
		function getActiveTargets () : Array;
		
		
		/**
		 * IManageable requirement. 
		 * 
		 * <p>This list is often passed to the <code>isHandling</code> method of other active 
		 * IManageable items. <i>DO NOT return all properties the item handles in general, 
		 * only ones the instance is currently tweening or setting.</i> The list can include 
		 * any custom property names the item defines.</p>
		 * 
		 * @return All property-strings currently being handled.
		 */
		function getActiveProperties () : Array;
		
		
		/**
		 * IManageable requirement: 
		 * 
		 * Return true if any of the property strings passed in overlap with any 
		 * properties being actively handled.
		 * 
		 * <p><b>Direct matching:</b></p>
		 * 
		 * <p>First and foremost, test for a direct match with al properties the item
		 * is currently handling on all targets. For example, if the item is actively 
		 * setting a 'width' property on any of its animation targets:
		 * <br><br>
		 * <code>if (properties.indexOf("width")>-1) return true;</code></p>
		 * 
		 * <p><b>Indirect matching:</b></p>
		 * 
		 * <p>You must be sure to check for indirect, as well as direct  matches. 
		 * This is very important and can at times require some creative thought 
		 * on your part. Try to keep isHandling code effiecient to reduce processing
		 * and filesize across batches of items.</p>
		 * 
		 * <ol>
		 * <li><i>Overlap,</i> like 'width' and 'scaleX'. These would certainly conflict if 
		 * two different Go items were allowed to handle them at once on the same target. 
		 * Overlaps might not always be this obvious, so think creatively.<br><br></li>
		 * 
		 * <li><i>Multi-property groups.</i> If the item is setting multiple properties at 
		 * once for a single result, such as a bezier-curve tween that operates on both x 
		 * and y, and may also define a custom property like 'bezier', be sure to return 
		 * true if any of those properties are passed in.<br><br></li>
		 * 
		 * <li><i>Multi-property groups with overlap,</i> in which both of the above occurs. 
		 * Consider a class with custom 'scale' and 'size' properties that tween <i>scaleX/scaleY</i> 
		 * and <i>width/height</i>. Overlap occurs between entire groups of properties: 
		 * <i>scaleX/width/scale/size</i> and <i>scaleY/height/scale/size</i>. You must check 
		 * whether each property passed in conflicts with this item's active properties using 
		 * those groupings to check for any indirect match.</li>
		 * </ol>
		 * 
		 * @param properties	A list of properties to test for active overlap.
		 * @return				Whether any active overlap occurred with any property passed in.
		 */
		function isHandling (properties : Array) : Boolean;
		
		
		/**
		 * IManageable requirement: Normally this method should stop the instance.
		 * 
		 * @param params	Gives more complex managers leeway to send additional information
		 * 					like specific targets or properties to release, etc.
		 */
		function releaseHandling (...params) : void;
		
	}
}