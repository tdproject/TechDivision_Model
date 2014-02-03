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

require_once 'TechDivision/Model/Configuration/XML.php';

/**
 * Cached version of the XML configuration.
 *
 * After first time parsing the XML configuration file,
 * the configuration is written to the file system as a
 * class. This class is then included in every request.
 *
 * The standard value for the generated configuration
 * file is '/tmp/epb.php' and can be changed by setting
 * the filename as parameter for the singleton.
 *
 * @package TechDivision_Model
 * @author Tim Wagner <t.wagner@techdivision.com>
 * @copyright TechDivision GmbH
 * @link http://www.techdivision.com
 * @license GPL
 */
class TechDivision_Model_Configuration_CachedXML
    extends TechDivision_Model_Configuration_XML {

    /**
     * The path and the filename to use for the generated configuration file.
     * @var string
     */
    private $_generatedConfigFile = null;

    /**
     * Holds the config file generation date as a UNIX timestamp.
     * @var integer
     */
    private $_configDate = null;

	/**
	 * Protected constructor to initialize the configuration
	 * with the information of the passed descriptor and the
	 * name/path to the generated configuration class.
	 *
	 * @param string $descriptor
	 * 		Holds the path and filename of the descriptor file with the entity
	 * 		and session bean information
	 * @param string $generatedConfigFile
	 * 		Holds the path and filename of the generated configuration class
	 */
	protected function __construct($descriptor, $generatedConfigFile)
	{
		TechDivision_Model_Configuration_XMLConfiguration::__construct(
		    $descriptor
		);
		$this->_generatedConfigFile = $generatedConfigFile;
    	$this->_configDate = filemtime(
    	    TechDivision_Model_Configuration_CachedXML::getFileWithIncludePath(
    	        $descriptor
    	    )
    	);
    	$this->_logger->error(
    		'Found config date ' . $this->_configDate .
    	    ' for descriptor ' . $descriptor
    	);
	}

	/**
	 * Singleton function for initalizing the configuration
	 * with the information from the descriptor passed as parameter.
	 *
	 * This method also checks if the configuration is already
	 * generated. If yes the generated class is included and the
	 * values are loaded from this class instead of reparsing the
	 * XML configuration file.
	 *
	 * @param string $descriptor
	 * 		Holds the path nad filename of the descriptor file with the
	 * 		entity and session bean information
	 * @param string $generatedConfigFile
	 * 		Holds the path and filename of the generated configuration class
	 */
	public static function getConfiguration(
	    $descriptor,
	    $generatedConfigFile = '/tmp/epb.php') {
    	// check if already one object is instanciated, if yes, return it
    	if(TechDivision_Model_Configuration_XML::$INSTANCE == null) {
        	// check if the generated configuration exists, if not initialize
        	// this class for configuration
        	if(!file_exists($generatedConfigFile)) {
        		TechDivision_Model_Configuration_XML::$_instance =
        		    new TechDivision_Model_Configuration_CachedXML(
        		        $descriptor, $generatedConfigFile
        		    );
        	} else {
	        	// if the generated configuration exists, include it and
	        	// check the timestamp
	        	require_once $generatedConfigFile;
	        	$instance = new TechDivision_Model_Configuration_Generated();
	        	$t = filemtime(
	        	    TechDivision_Model_Configuration_CachedXML::getFileWithIncludePath(
	        	        $descriptor
	        	    )
	        	);
	        	if ($instance->getConfigDate() == $t) {
	        		// if the timestamps are equal use the generated one
	        		TechDivision_Model_Configuration_XML::$_instance = $instance;
	        	} else {
	        		// if the timestamps are different, generate it new
	        		TechDivision_Model_Configuration_XML::$_instance =
	        		    new TechDivision_Model_Configuration_CachedXML(
	        		        $descriptor,
	        		        $generatedConfigFile
	        		    );
	        	}
        	}
    	}
    	// return the configuration instance
    	return TechDivision_Model_Configuration_XML::$_instance;
	}

	/**
	 * This method generates a configuratio class with all
	 * informations given in the XML configuration and
	 * saves it to the file system as a native PHP class.
	 *
	 * @return void
	 */
	public function save()
	{
		$configurationClass  = '<?php' . PHP_EOL;
		$configurationClass .= 'require_once "TechDivision/Lang/Object.php";' . PHP_EOL;
		$configurationClass .= 'require_once "TechDivision/Collections/HashMap.php";' . PHP_EOL;
		$configurationClass .= 'require_once "TechDivision/Model/Container/Plugin.php";' . PHP_EOL;
		$configurationClass .= 'require_once "TechDivision/Model/Container/Entity.php";' . PHP_EOL;
		$configurationClass .= 'require_once "TechDivision/Model/Configuration/Interfaces/Container.php";' . PHP_EOL;
		$configurationClass .= 'class TechDivision_Model_Configuration_Generated' . PHP_EOL;
		$configurationClass .= '	extends TechDivision_Lang_Object' . PHP_EOL;
		$configurationClass .= '	implements TechDivision_Model_Configuration_Interfaces_Container {' . PHP_EOL;
		$configurationClass .= '	protected $_entities = null;' . PHP_EOL;
		$configurationClass .= '	protected $_plugins = null;' . PHP_EOL;
		$configurationClass .= '	protected $_configDate = ' . $this->_configDate . ';' . PHP_EOL;
		$configurationClass .= '	public function __construct() {}' . PHP_EOL;
		$configurationClass .= '	public function getConfigDate() {' . PHP_EOL;
		$configurationClass .= '		return $this->_configDate;' . PHP_EOL;
		$configurationClass .= '	}' . PHP_EOL;
		$configurationClass .= '	public function getPlugins() {' . PHP_EOL;
		$configurationClass .= '		return $this->_plugins;' . PHP_EOL;
		$configurationClass .= '	}' . PHP_EOL;
		$configurationClass .= '	public function getEntities() {' . PHP_EOL;
		$configurationClass .= '		return $this->_entities;' . PHP_EOL;
		$configurationClass .= '	}' . PHP_EOL;
		$configurationClass .= '	public function initialize($applicationName = null) {' . PHP_EOL;
		$configurationClass .= '		$this->_plugins = new TechDivision_Collections_HashMap();' . PHP_EOL;
		// generated the code for all plugins
		foreach ($this->_plugins as $plugin) {
			$configurationClass .= '    	$plugin = new TechDivision_Model_Container_Plugin();' . PHP_EOL;
			$configurationClass .= '    	$plugin->setName("' . $plugin->getName() . '");' . PHP_EOL;
			$configurationClass .= '    	$plugin->setType("' . $plugin->getType() . '");' . PHP_EOL;
			$configurationClass .= '		$this->_plugins->add($plugin->getName(), $plugin);' . PHP_EOL;
		}
		$configurationClass .= '		$this->_entities = new TechDivision_Collections_HashMap();' . PHP_EOL;
		// generated the code for all entitiy and session beans
		foreach ($this->_entities as $entity) {
			$configurationClass .= '    	$cont = new TechDivision_Model_Container_Entity();' . PHP_EOL;
			$configurationClass .= '    	$cont->setName("' . $entity->getName() . '");' . PHP_EOL;
			$configurationClass .= '    	$cont->setType("' . $entity->getType() . '");' . PHP_EOL;
			foreach ($epb->getApplications() as $application) {
				$configurationClass .= '    	$cont->addApplication("' . $application . '");' . PHP_EOL;
			}
			$configurationClass .= '		$this->_entities->add($cont->getName(), $cont);' . PHP_EOL;
		}
		// close the brackets and add the php delimiter
		$configurationClass .= '	}' . PHP_EOL;
		$configurationClass .= '}' . PHP_EOL;
		$configurationClass .= '?>';
		// save the file
		return file_put_contents(
		    $this->_generatedConfigFile,
		    $configurationClass
		);
	}

	/**
	 * TechDivision_Model_Configuration_Interfaces_Container::initialize()
	 */
	public function initialize(
		TechDivision_Model_Interfaces_Container_Cache $cache)
	{
		TechDivision_Model_Configuration_XMLConfiguration::initialize();
    	return $this->save();
	}

	/**
	 * This method tries to find the complete pysical filename of
	 * the file with the passed name. Therefore it checks all
	 * include pathes and tries to check if the file exists there.
	 *
	 * If the file was found it returns the complete path with
	 * the filename.
	 *
	 * @return string $filename Holds the filename to get the pysical path for
	 * @return string The complete physical path and filename
	 */
	public static function getFileWithIncludePath($filename)
	{
		// get an array with the include paths and check if the file exists there
		foreach (explode(PATH_SEPARATOR, ini_get("include_path")) as $includePath) {
			if (file_exists($includePath . DIRECTORY_SEPARATOR . $filename)) {
				return $includePath . DIRECTORY_SEPARATOR . $filename;
			}
		}
	}
}