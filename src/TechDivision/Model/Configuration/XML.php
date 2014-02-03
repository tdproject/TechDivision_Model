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

require_once 'TechDivision/Lang/Object.php';
require_once 'TechDivision/Collections/HashMap.php';
require_once 'TechDivision/Logger/Logger.php';
require_once 'TechDivision/Model/Container/Plugin.php';
require_once 'TechDivision/Model/Container/Entity.php';
require_once
	'TechDivision/Model/Configuration/Interfaces/Container.php';

/**
 * Standard version of the XML configuration.
 *
 * By using this configuration version, the XML configuration
 * file is parsed in every requests. This can be very time and
 * processor intensiv. So use it carefully.
 *
 * @package TechDivision_Model
 * @author Tim Wagner <t.wagner@techdivision.com>
 * @copyright TechDivision GmbH
 * @link http://www.techdivision.com
 * @license GPL
 */
class TechDivision_Model_Configuration_XML
    extends TechDivision_Lang_Object
    implements TechDivision_Model_Configuration_Interfaces_Container {

	/**
	 * The HashMap with the entity and session bean information
	 * from the XML configuration file.
	 *
	 * @var TechDivision_Collections_HashMap
	 */
	protected $_entities = null;

	/**
	 * The HashMap with the plugin information from the
	 * XML configuration file.
	 *
	 * @var TechDivision_Collections_HashMap
	 */
	protected $_plugins = null;

	/**
	 * The initalized logger instance.
	 *
	 * @var TechDivision_Logger_Interfaces_Logger
	 */
	protected $_logger = null;

	/**
	 * Holds the path to the descriptor.
	 *
	 * @var string
	 */
	protected $_descriptor = null;

	/**
	 * Holds the actual instance.
	 *
	 * @var TechDivision_Model_Configuration_XML
	 */
	protected static $_instance = null;

	/**
	 * Protected constructor to initialize the configuration
	 * with the information of the passed descriptor.
	 *
	 * @param string $descriptor
	 * 		Holds the path and filename of the descriptor file with the
	 * 		entity and session bean information
	 */
	protected function __construct($descriptor)
	{
        // initialize the Logger instance
		$this->_logger = TechDivision_Logger_Logger::forObject($this);
        // intitialize the HashMap for the plugins and the entities
		$this->_entities = new TechDivision_Collections_HashMap();
		$this->_plugins = new TechDivision_Collections_HashMap();
		// set the path to the descriptor
		$this->_descriptor = $descriptor;
	}

	/**
	 * Singleton function for initalizing the
	 * configuration with the information from
	 * the descriptor passed as parameter.
	 *
	 * @param string $descriptor
	 * 		Holds the path nad filename of the descriptor file with the entity
	 * 		and session bean information
	 */
	public static function getConfiguration($descriptor)
	{
		if (TechDivision_Model_Configuration_XML::$_instance == null) {
			TechDivision_Model_Configuration_XML::$_instance = new TechDivision_Model_Configuration_XML($descriptor);
		}
		return TechDivision_Model_Configuration_XML::$_instance;
	}

	/**
	 * @see TechDivision_Model_Configuration_Interfaces_Container::initialize()
	 */
	public function initialize(
		TechDivision_Model_Interfaces_Container_Cache $cache)
	{
        // new xml document
	    $doc = new DomDocument();
	    // load the document
        $doc->loadXML(file_get_contents($this->_descriptor, true));
		$this->_logger->debug(
			'Initialize container with deployment descriptor file ' . $this->_descriptor,
			__LINE__
		);
        // intialize the xpath query
        $xpath = new domXPath($doc);
        // get the entity beans
        $nodelist = $xpath->query("/container/entities/entity");
		$this->_logger->debug(
			'Found ' . sizeof($nodelist) . ' entity beans in deployment ' .
		    'descriptor file',
		    __LINE__
		);
        // iterate over the found elements
        foreach ($nodelist as $node) {
            // create a new epb definition
            $cont = new TechDivision_Model_Container_Entity($cache);
            // get the necessary information for the epb definition
            $name = $node->getAttribute('name');
            $type = $node->getAttribute('type');
            // get the child nodes
            $childs = $node->childNodes;
			// set the additional values
            $cont->setName($name);
            $cont->setType($type);
            // adding the bean definition to the container
			$this->_logger->debug(
				'Adding bean of type ' . $type . ' with name ' . $name . ' in container',
			    __LINE__
			);
            $this->_entities->add($cont->getName(), $cont);
        }
        // get the plugins
        $nodelist = $xpath->query('/container/plugins/plugin');
		$this->_logger->debug(
			'Found ' . sizeof($nodelist) . ' plugins ' .
			'in deployment descriptor file',
		    __LINE__
		);
        // iterate over the found elements
        foreach ($nodelist as $node) {
        	$plugin = new TechDivision_Model_Container_Plugin();
            // get the necessary information for the epb definition
            $name = $node->getAttribute('name');
            $type = $node->getAttribute('type');
			// set the additional values
            $plugin->setName($name);
            $plugin->setType($type);
            // adding the plugin definition to the container
			$this->_logger->debug(
				'Adding plugin of type ' . $type .
				' with name ' . $name . ' in container',
			    __LINE__
			);
            $this->_plugins->add($plugin->getName(), $plugin);
        }
	}

	/**
	 * @see TechDivision_Model_Configuration_Interfaces_Container::getConfigurationDirectory()
	 */
	public function getConfigurationDirectory()
	{
		return dirname($this->_descriptor);
	}

	/**
	 * @see TechDivision_Model_Configuration_Interfaces_Container::getEntities()
	 */
	public function getEntities()
	{
		return $this->_entities;
	}

	/**
	 * @see TechDivision_Model_Configuration_Interfaces_Container::getPlugins()
	 */
	public function getPlugins()
	{
		return $this->_plugins;
	}
}