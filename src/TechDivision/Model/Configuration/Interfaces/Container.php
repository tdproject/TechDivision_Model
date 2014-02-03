<?php

/**
 * License: GNU General Public License
 *
 * Copyright (c) 2009 TechDivision GmbH.  All rights reserved.
 * Note: Original work copyright to respective authors
 *
 * This file is part of TechDivision GmbH - Connect.
 *
 * TechDivision_Model is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * TechDivision_Model is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 * USA.
 *
 * @package TechDivision_Model
 */

/**
 * This is the interface for all container configurations.
 *
 * @package TechDivision_Model
 * @author Tim Wagner <t.wagner@techdivision.com>
 * @copyright TechDivision GmbH
 * @link http://www.techdivision.com
 * @license GPL
 */
interface TechDivision_Model_Configuration_Interfaces_Container
{

	/**
	 * This method initializes the configuration with
	 * the values from the XML configuration file.
	 *
	 * @param TechDivision_Model_Interfaces_Container_Cache $cache
	 * 		The cache instance to use
	 * @return void
	 */
	public function initialize(
		TechDivision_Model_Interfaces_Container_Cache $cache);

	/**
	 * This method returns the HashMap with the
	 * entity and session bean information from
	 * the XML configuration file.
	 *
	 * @return TechDivision_Collections_HashMap
	 * 		Holds the information about the entity and session beans
	 */
	public function getEntities();

	/**
	 * This method returns the HashMap with the plugins
	 * information from the XML configuration file.
	 *
	 * @return TechDivision_Collections_HashMap
	 * 		Holds the information about the plugins
	 */
	public function getPlugins();

	/**
	 * Returns the path to the configuration
	 * directory.
	 *
	 * @return string Path to the configuration directory
	 */
	public function getConfigurationDirectory();
}