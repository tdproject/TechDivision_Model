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
require_once 'TechDivision/Util/AbstractDataSource.php';
require_once 'TechDivision/Util/XMLDataSource.php';
require_once 'TechDivision/Util/Object/Factory.php';
require_once 'TechDivision/Logger/Logger.php';
require_once 'TechDivision/Logger/Interfaces/Logger.php';
require_once 'TechDivision/Collections/HashMap.php';
require_once 'TechDivision/Collections/CollectionUtils.php';
require_once 'TechDivision/Model/Interfaces/Container.php';
require_once 'TechDivision/Model/Container/Utils.php';
require_once 'TechDivision/Model/Container/Entity.php';
require_once 'TechDivision/Model/Container/Session.php';
require_once 'TechDivision/Model/Manager/MySQLi.php';
require_once 'TechDivision/Model/Exceptions/ContainerConfigurationException.php';
require_once 'TechDivision/Model/Exceptions/InvalidDataSourceTypeException.php';
require_once 'TechDivision/Model/Configuration/Interfaces/Container.php';
require_once 'TechDivision/Model/Configuration/CachedXML.php';
require_once 'TechDivision/Model/Configuration/XML.php';
require_once 'TechDivision/Model/Predicates/FindSlaveByNamePredicate.php';
require_once 'TechDivision/VFS/ClassLoader.php';

/**
 * This is the container that manages the epbs.
 *
 * The DataSource for the session management MUST have
 * the autocommit flag set to true, else the session
 * management won't work correctly.
 *
 * @package TechDivision_Model
 * @author Tim Wagner <t.wagner@techdivision.com>
 * @copyright TechDivision GmbH
 * @link http://www.techdivision.com
 * @license GPL
 */
class TechDivision_Model_Container_Implementation
    extends TechDivision_Lang_Object
    implements TechDivision_Model_Interfaces_Container {

	/**
	 * The prefix for the session cache key.
	 * @var string
	 */
	const CACHE_KEY = 'session_';

	/**
	 * Array with the actual instances.
	 * @var array
	 */
	protected static $_instance = null;

    /**
     * Holds a reference to the session database manager needed for
     * storing the session data in the database.
     * @var TechDivision_Model_Interfaces_Manager
     */
    protected $_sessionManager = null;

    /**
     * Holds a reference to the master database manager needed for
     * read/write access to the database.
     * @var TechDivision_Model_Interfaces_Manager
     */
    protected $_masterManager = null;

    /**
     * Holds an array with database managers that only have
     * read access to the database.
     * @var array
     */
    protected $_slaveManagers = array();

    /**
     * Holds an array with the dedicated database managers that only
     * have read access to the database.
     * @var array
     */
    protected $_dedicatedManagers = array();

    /**
     * Holds a hash map needed for the management of the entities.
     * @var TechDivision_Collections_HashMap
     */
    protected $_entities = null;

    /**
	 * Holds an array with plugins executed by the container.
	 * @var array
	 */
    protected $_plugins = array();

    /**
     * Holds the session bean container with the session beans.
     * @var TechDivision_Model_Container_Session
     */
    protected $_sessionBeans = null;

    /**
     * Holds the container configuration.
     * @var TechDivision_Model_Configuration_Interfaces_Container
     */
    protected $_containerConfiguration = null;

	/**
	 * Holds the logger instance.
	 * @var TechDivision_Logger_Interfaces_Logger
	 */
	protected $_logger = null;

	/**
	 * Holds the flag to use the master database manager instead of a slave
	 * @var boolean
	 */
	protected $_forceMaster = false;

	/**
	 * Holds the key of the slave to use.
	 * @var integer
	 */
	protected $_activeSlave = 0;

	/**
	 * Holds the actual session id.
	 * @var string
	 */
	protected $_sessionId = null;

    /**
     * Array with the defined pointcuts.
     * @var array
     */
    protected $_pointcuts = array();

    /**
     * The object factory instance.
     * @var TDProject_Factory_Object
     */
    protected $_objectFactory = null;

    /**
     * The ClassLoader instance.
     * @var TechDivision_VFS_ClassLoader
     */
    protected $_classLoader = null;

    /**
     * TRUE to activate AOP.
     * @var boolean
     */
    protected $_useAOP = true;

    /**
     * The cache instance.
     * @var Zend_Cache_Core
     */
    protected $_cache = null;

    /**
     * Flag to mark the container as already deinitialized.
     * @var boolean
     */
    protected $_deinitialized = false;

    /**
     * The constructor initializes the configuration, checks
     * if the necessary tables exists and creates it if not.
     *
     * @param string $sessionId Holds the id of the session to use
     * @param TechDivision_Model_Configuration_Interfaces_Container $containerConfiguration
     * 		Holds the container configuration
     * @return void
     */
    protected function __construct(
        $sessionId = null,
        TechDivision_Model_Configuration_Interfaces_Container $containerConfiguration = null) {
    	// set the container configuration
    	$this->_containerConfiguration = $containerConfiguration;
        // initialize a new VFS class loader instance
        $this->_classLoader = TechDivision_VFS_ClassLoader::register();
        // initialize the object factory
        $this->_objectFactory = TechDivision_Util_Object_Factory::get();
        // initialize the logger
        if (!empty($containerConfiguration)) {
    		$this->_logger = TechDivision_Logger_Logger::forObject(
    		    $this,
    		    $this->getConfigurationDirectory() . DIRECTORY_SEPARATOR . 'log4php.properties'
    		);
        } else {
    		$this->_logger = TechDivision_Logger_Logger::forObject($this);
        }
        // check if APC is available for caching
       	if (extension_loaded('apc')) {
        	// log that APC will be used for caching
        	$this->_getLogger()
        		->debug('Using APC backend for caching', __LINE__);
            // initialize the cache backend
            $backend = 'Apc';
            $backendOptions = array();
       	}
       	// else, check if Memcached is available
        elseif (extension_loaded('memcached')) {
        	// log that Memcached will be used for caching
        	$this->_getLogger()
        		->debug('Using Memcached backend for caching', __LINE__);
        	// initialize the Memcached backend
        	$backend = 'Libmemcached';
        	$backendOptions = array(
        		'servers' => array(
	        		'host' => 'localhost',
	        	    'port' => 11211,
	        	    'weight' => 1
	        	),
	        	'client'  => array(
		        	Memcached::OPT_DISTRIBUTION => Memcached::DISTRIBUTION_CONSISTENT,
		        	Memcached::OPT_HASH => Memcached::HASH_MD5,
		        	Memcached::OPT_LIBKETAMA_COMPATIBLE => true
	        	)
        	);
        }
        // at least, use file caching
        else {
            // initialize the cache backend
            $backend = 'File';
            $backendOptions = array(
            	'cache_dir' => $this->getCacheDirectory()
            );
        }
        // initialize the cache frontend
        $frontendOptions = array(
           	'lifetime' => TechDivision_Model_Container_Utils::CACHE_TIMEOUT,
           	'automatic_serialization' => true
        );
        // instanciate the cache instance
        $this->_cache = Zend_Cache::factory(
        	'TechDivision_Model_Container_Cache',
        	$backend,
            $frontendOptions,
            $backendOptions,
            true,
            false
        );
    	// initialize the session id
    	if (!empty($sessionId)) {
    		// set the passed session id
			$this->_sessionId = $sessionId;
    		// log that the session id of the web application is used
    		$this->_getLogger()->debug(
    			'Use passed session id ' . $this->_sessionId,
    		    __LINE__
    		);
    	} else {
    		// use the session id of the web application
    		$this->_sessionId = session_id();
    		// log a warning that the session id of the web application is used
    		$this->_getLogger()->warning(
    			'No session id passed, instead try to use session id ' .
    		    $this->_sessionId .
    		    ' of the web application',
    		    __LINE__
    		);
    	}
		// initialize the container with the data from the
		// container configuration
    	$this->_initialize();
		// initialize the data sources
		$this->_initializeDataSources();
		// initialize the plugins
    	$this->_initializePlugins();
		// initialize the pointcuts
    	$this->_initializePointcuts();
		// load the session data
    	$this->_loadSession();
    }

    /**
     * The destructor creates or updates the session information
     * in the database and deletes session entries that are older
     * than the specified timeout (3600 seconds by default).
     *
     * @return void
     */
	public function __destruct()
	{
	    // save the session data
	    $this->_saveSession();
	}

	/**
	 * Returns the container configuration.
	 *
	 * @return TechDivision_Model_Configuration_Interfaces_Container
	 * 		The container configuration
	 */
	public function getContainerConfiguration()
	{
		return $this->_containerConfiguration;
	}

	/**
	 * Returns the path to the container's configuration directory
	 * folder META-INF.
	 *
	 * @return string The path to the container configuration directory
	 */
	public function getConfigurationDirectory()
	{
		return $this->getContainerConfiguration()->getConfigurationDirectory();
	}

	/**
	 * returns the container cache directory.
	 *
	 * @return string The cache directory
	 */
	public function getCacheDirectory()
	{
		return $this->getConfigurationDirectory() . DIRECTORY_SEPARATOR . 'cache';
	}

	/**
	 * This method returns the instance of the container as singleton.
	 *
	 * @param string $sessionId Holds the id of the actual session
     * @param TechDivision_Model_Configuration_Interfaces_Container $containerConfiguration
     * 		Holds the container configuration
	 * @return TechDivision_Model_Container_Implementation
	 * 		Returns the instance of the container as singleton
	 * @see TechDivision_Model_Interfaces_Container::getContainer(
     *	   		$applicationName,
     *	   		$sessionId = null,
     *	    	TechDivision_Model_Configuration_Interfaces_Container $containerConfiguration = null)
	 */
	public static function getContainer(
	    $sessionId = null,
	    TechDivision_Model_Configuration_Interfaces_Container $containerConfiguration = null) {
		// check the internal member is already instanciated
		if (self::$_instance == null) {
			// initialize the instance for the passed application name
			self::$_instance =
			    new TechDivision_Model_Container_Implementation(
			        $sessionId,
			        $containerConfiguration
			    );
		}
		// return the instance for the passed application name
		return self::$_instance;
	}

    /**
     * This method initializes the internal structure
     * with the information found in the deployment
     * descriptor.
     *
     * @return void
     */
    protected function _initialize()
    {
    	// initialize the container configuration
    	$this->_containerConfiguration->initialize($this->getCache());
    	// load the beans and the plugins from the configuration
    	$this->_setEntities($this->_containerConfiguration->getEntities());
    	$this->_setPlugins($this->_containerConfiguration->getPlugins());
    }

    /**
     * This method looks in the internal structure if an entity
     * with the passed name is registered. If yes, it checks if
     * the entity with the passed key is already initialized.
     * If yes it returns the initialized entity, if not, a new
     * entity is initialized, the data is loaded and the new
     * entity is returned.
     *
     * @param string $name The name of the epb that should be returned
     * @param $integer $key The key of the dataset the epb should be initialized
     * @param boolean $refresh
     * 		TRUE if the entity should be refreshed, else FALSE (default)
     * @return TechDivision_Model_Interfaces_Entity Holds the initialized entity
     * @throws TechDivision_Model_Exceptions_FindException
     * 		Is thrown if the entity could not be loaded
     * @throws TechDivision_Model_Exceptions_ContainerConfigurationException
     * 		Is thrown if the entity is not registered in the container
     * @see TechDivision_Model_Interfaces_Container::lookup($name, $key = null, $refresh = false)
     */
    public function lookup($name, $key = null, $refresh = false)
    {
		// initialize a new bean
		$bean = null;
        // search for the entity
        if (!$this->_entities->exists($name)) {
			throw new TechDivision_Model_Exceptions_ContainerConfigurationException(
				'Entity ' . $name . ' is not registered with deployment descriptor'
			);
		}
        $found = $this->_entities->get($name);
		$this->_getLogger()
			->debug("Taking lookup for bean with name $name", __LINE__);
        // check if a cached instance of the entity exists
        if ($key != null && $refresh == false) {
        	// make a lookup if maybe a cached version exists
			$bean = $found->lookup($key);
        }
        // check if an instance of the bean exists
        if ($bean == null) {
			// check if it is a entity
			if (strcmp($found->getType(), 'entity') == 0) {
                // instanciate a new entity
                $className = $found->getName();
				// create a new instance and connect it to the container
                $bean = $this->newInstance($className);
				// load the bean data
            	$bean->connect($this)->load($key);
				// add the bean to the container
				$this->_entities->get($name)->add($bean);
				// log the successfully bean creation
				$this->_getLogger()
					->debug("Successfully created new instance of $className and key $key", __LINE__);
			}
			// check if this is a session
			if (strcmp($found->getType(), 'session') == 0) {
				// get the session and register the session
				if (!$this->_sessionBeans->exists($name)) {
	                // instanciate a new session
					$className = $found->getName();
					// create a new instance
					$bean = $this->newInstance($className, array($this));
					// add the session bean to the container
	                $this->_sessionBeans->add($bean);
	                // log that the session bean has successfully been attached
					$this->_getLogger()
						->debug("Successfully created new instance of $className", __LINE__);
				}
				// load the existing session bean
				else {
					$bean = $this->_sessionBeans->lookup($name);
				}
			}
        }
        // if yes, return the cached instance
        else {
        	// if a entity bean has been found, connect it to the container
        	if (strcmp($found->getType(), 'entity') == 0) {
        		$bean->connect($this);
        	}
        	// log that a cached version of the been has been available
        	$this->_getLogger()
        		->debug("Found cached entity bean $name with key $key", __LINE__);
        }
        // return the entity/session bean instance
        return $bean;
    }

    /**
     * Removes the passed bean (entity/session) from the container.
     *
     * @param string $name Name of the bean
     * @param mixed $key Key of the bean to remove (only entity)
     * @throws TechDivision_Model_Exceptions_ContainerConfigurationException
     * 		Is thrown if the requested bean is not registered
     */
    public function remove($name, $key = null)
    {
		// initialize a new bean
		$bean = null;
        // search for the entity
        if (!$this->_entities->exists($name)) {
			throw new TechDivision_Model_Exceptions_ContainerConfigurationException(
				'Entity ' . $name . ' is not registered with deployment descriptor'
			);
		}
		// load the entity to be removed
        $found = $this->_entities->get($name);
        // check if a cached instance of the entity exists
        if ($key != null) {
        	// make a lookup if maybe a cached version exists
			$bean = $found->lookup($key);
        }
        // check if an instance of the bean exists
        if ($bean != null) {
			// check if it is a entity
			if (strcmp($found->getType(), 'entity') == 0) {
				// remove the entity from the container
				$this->_entities->get($name)->remove($bean);
                // log a message
				$this->_getLogger()
				    ->debug("Successfully remove $name with key {$bean->getPrimaryKey()}", __LINE__);
			}
			// if a session bean has been requested, remove it from the container
			elseif (strcmp($found->getType(), 'session') == 0) {
				$this->_sessionBeans->remove($name);
			}
			// throw an exception
			else {
				throw new TechDivision_Model_Exceptions_ContainerConfigurationException(
					"Entity $name not available"
				);
			}
        }
    }

    /**
     * This method initializes a new entity identified
     * by the passed name registers the entity in the
     * internal structure and returns a reference to it.
     *
     * @param string $name The name of the entity the should be returned
     * @return TechDivision_Model_Interfaces_Entity
     * 		Holds a reference of the entity
     * @see TechDivision_Model_Interfaces_Container::register($name)
     */
    public function register($name)
    {
        // search for the epb
        $found = $this->_entities->get($name);
        // instanciate a new entity
        $className = $found->getName();
		// create a new instance of the requested bean
        $bean = $this->newInstance($className)->connect($this);
        // log that the bean was successfully initialized
		$this->_getLogger()
			->debug("Successfully registered bean $name", __LINE__);
        // return the bean instance
		return $bean;
    }

    /**
     * This method returns the database manager used
     * by the entities with read/write access.
     *
     * @return TechDivision_Model_Interfaces_Manager Returns the database manager
     * @see TechDivision_Model_Interfaces_Container::getMasterManager()
     */
    public function getMasterManager()
    {
    	return $this->_masterManager;
    }

    /**
     * This method returns one of the database managers used
     * by the entities with read only access.
     *
     * @param string $name Holds the name of the slave manager to use
     * @return TechDivision_Model_Configuration_Interfaces_Manager
     * 		Returns one the database managers with read only access
     * @return TechDivision_Model_Interfaces_Container::getSlaveManager($name = '')
     */
    public function getSlaveManager($name = '')
    {
    	// if no slave is defined, make the master to a slave
		if (sizeof($this->_slaveManagers) == 0) {
    		$this->_slaveManagers[] = $this->getMasterManager();
    	}
    	// if the flag forced to use the master manager is not set, return a slave
    	if (!$this->_forceMaster) {
			// define a random number for the active slave to use
			if ($this->_activeSlave === null) {
				if ((empty($name))) {
					// get the key of the slave manager to use
					$this->_activeSlave = rand(0, sizeof($this->_slaveManagers) - 1);
	        		// log the key of the slave manager to use
					$this->_getLogger()->debug(
						'Now randomizing slave manager ' . $this->_activeSlave .
						' because of empty name' . $name,
					    __LINE__
					);
				}
				else {
					// check if the slave with the passed name exists
	        		if (($this->_activeSlave = TechDivision_Collections_CollectionUtils::findKey(
	        		    new TechDivision_Collections_ArrayList($this->_slaveManagers),
	        		    new TechDivision_Model_Predicates_FindSlaveByNamePredicate($name))) == null) {
						// get the key of the slave manager to use
						$this->_activeSlave = rand(
						    0,
						    sizeof($this->_slaveManagers) - 1
						);
	        			// log the key of the slave manager to use
						$this->_getLogger()->debug(
							'Now randomizing slave manager ' . $this->_activeSlave .
							' because of missing name' . $name,
						    __LINE__
						);
	        		}
	        		else {
	        			// log the name of the slave manager to use
						$this->_getLogger()->debug(
							'Now using named slave manager ' . $name,
						    __LINE__
						);
	        		}
				}
			}
			// load the used data source name
			$dataSourceName = $this->_slaveManagers[$this->_activeSlave]
				->getDataSourceName();
			// log the name of the slave manager to use
			$this->_getLogger()
				->debug("Application is using slave $dataSourceName", __LINE__);
			// else return one of the slaves by random
			return $this->_slaveManagers[$this->_activeSlave];
    	}
		// else return the master
   		return $this->getMasterManager();
    }

    /**
	 * This method returns all slave managers.
	 *
	 * @return array Holds all slave database managers
	 * @see TechDivision_Model_Interfaces_Container::getSlaveManagers()
	 */
    public function getSlaveManagers()
    {
    	return $this->_slaveManagers;
    }

    /**
     * This method returns the dedicated database manager used
     * by the entities with the passed name and with read only access.
     *
     * @return TechDivision_Model_Interfaces_Manager
     * 		Returns the dedicated database manager with the requested name
     * @see TechDivision_Model_Interfaces_Container::getDedicatedManager($name)
     */
    public function getDedicatedManager($name)
    {
    	// if no dedicated database managers are defined throw an exception
		if (sizeof($this->_dedicatedManagers) == 0) {
    		throw new Exception(
    			'No dedicated database managers are defined for container'
    		);
    	}
    	// if the requested dedicated database managers is
    	// not defined throw an exception
    	if (!array_key_exists($name, $this->_dedicatedManagers)) {
			throw new Exception(
				'Dedicated database manager with name ' .
			    $name . 'is not defined in container'
			);
    	}
    	// log that a dedicated database manager was returned
    	$this->_getLogger()->debug(
    		'Now returning dedicated database manager ' . $name,
    	    __LINE__
    	);
		// else return the requested dedicated database manager
		return $this->_dedicatedManagers[$name];
    }

    /**
     * This method returns the internal HashMap
     * with the entities.
     *
     * @return TechDivision_Collections_HashMap The HashMap with the entities
     */
    protected function _getEntities()
    {
        return $this->_entities;
    }

    /**
     * This method sets the passed HashMap with
     * epbs.
     *
     * @param TechDivision_Collections_HashMap $entities The HashMap with the entities
     */
    protected function _setEntities(TechDivision_Collections_HashMap $entities)
    {
        $this->_entities = $entities;
    }

    /**
     * This method returns the internal hash map
     * with container plugins.
     *
     * @return TechDivision_Collections_HashMap Holds the HashMap with plugins
     */
    protected function _getPlugins()
    {
        return $this->_plugins;
    }

    /**
     * This method sets the passed HashMap with
     * plugins.
     *
     * @param TechDivision_Collections_HashMap $plugins Holds the HashMap with plugins
     */
    protected function _setPlugins(TechDivision_Collections_HashMap $plugins)
    {
        // iterate over the plugin configuration
    	foreach($plugins as $plugin) {
        	// instanciate the plugin
    		$instance = $this->newInstance($plugin->getType());
        	// add and initialize the plugin to the internal list
    		$this->_plugins[] = $instance;
        }
    }

    /**
     * This methods parses the passed xml element,
     * creates a new data source from the found
     * information and adds it to the available
     * data sources.
     *
     * @param SimpleXMLElement $sxe
     * 		Holds the xml with the data source information
     * @return void
     * @throws Exception Is thrown if the specified data source type is invalid
     */
    protected function _addDataSource(SimpleXMLElement $sxe)
    {
		// create a data source from the xml content
		$ds = TechDivision_Util_XMLDataSource::create($sxe);
		// add it to the internal datasources
		switch ($ds->getType()) {
			case TechDivision_Util_AbstractDataSource::MASTER:
				$this->_addMasterManager(
					$this->newInstance('TechDivision_Model_Manager_MySQLi', array($this, $ds))
				);
				break;
			case TechDivision_Util_AbstractDataSource::SLAVE:
				$this->_addSlaveManager(
					$this->newInstance('TechDivision_Model_Manager_MySQLi', array($this, $ds))
				);
				break;
			case TechDivision_Util_AbstractDataSource::DEDICATED:
				$this->_addDedicatedManager(
					$this->newInstance('TechDivision_Model_Manager_MySQLi', array($this, $ds))
				);
				break;
			default:
				throw new TechDivision_Model_Exceptions_InvalidDataSourceTypeException(
					'Invalid data source type ' . $ds->getType() . ' specified'
				);
				break;
		}
    }

    /**
     * This method adds the master database manager
     * for read/write access.
     *
     * @param TechDivision_Model_Interfaces_Manager $master
     * 		Holds the master database manager for read/write access
     */
    protected function _addMasterManager(
        TechDivision_Model_Interfaces_Manager $masterManager) {
		// set the master manager
		$this->_masterManager = $masterManager;
		// add the callback function to the master manager
		$this->_masterManager
			->addCallbackFunction('afterExecute', $this, 'useMasterOnly');
    }

    /**
     * This method adds a slave database manager
     * for read only access.
     *
     * @param TechDivision_Model_Interfaces_Manager $slaveManager
     * 		Holds a slave database manager for read only access
     */
    protected function _addSlaveManager(
        TechDivision_Model_Interfaces_Manager $slaveManager) {
		// add the slave manager to the array with all slave managers
		$this->_slaveManagers[] = $slaveManager;
    }

    /**
     * This method adds a dedicated database manager
     * for read only access.
     *
     * @param TechDivision_Model_Interfaces_Manager $dedicatedManager
     * 		Holds a dedicated database manager for read only access
     */
    protected function _addDedicatedManager(
        TechDivision_Model_Interfaces_Manager $dedicatedManager) {
		// add the dedicated manager to the array with all dedicated managers
		$this->_dedicatedManagers[
		    $dedicatedManager->getDataSourceName()
	    ] = $dedicatedManager;
    }

    /**
     * This method return the actual logger instance.
     *
     * @return TechDivision_Logger_Interfaces_Logger
     * 		Holds the actual logger instance
     */
    protected function _getLogger()
    {
    	return $this->_logger;
    }

    /**
     * This method initializes the plugins described
     * in the deployment descriptor.
     *
     * @return void
     */
    protected function _initializePlugins()
    {
    	foreach($this->_plugins as $plugin) {
    		$plugin->initialize($this);
    	}
    }

    /**
     * @throws TechDivision_Model_Exceptions_ContainerConfigurationException
     * 		Is thrown if no master data source was defined
     */
    protected function _initializeDataSources()
    {
		// create a new xml element from the datasource
		$sxe = new SimpleXMLElement(
		    file_get_contents(
		        $this->_containerConfiguration->getConfigurationDirectory() .
		            DIRECTORY_SEPARATOR . "ds.xml",
		        true
		    )
		);
		// iterate over the data sources and add them
		foreach ($sxe->xpath("//datasources/datasource") as $sxe) {
			// initialize the data source
			$this->_addDataSource($sxe);
		}
		// check that at least a master datasource is defined
		if($this->_masterManager == null) {
			throw new TechDivision_Model_Exceptions_ContainerConfigurationException(
				'No master datasource was defined in the ' .
				' datasource configuration file'
			);
		}
    }

    /**
     * Returns the session cache key.
     *
     * @return string The session cache key
     */
    public function getCacheKey()
    {
    	return 'session_' . $this->getSessionId();
    }

    /**
     * This method checks if a session for the actual session id
     * exists in the database. If yes, it loads the session data
     * from the database else a new session container is created.
     *
     * @return void
     * @throws TechDivision_Model_Exceptions_ContainerConfigurationException
     * 		Is thrown if the session data found in the database is corrupt
     */
    protected function _loadSession()
    {
    	// initialize the path to the serialized file with the session beans
    	if ($this->getCache()->test($this->getCacheKey())) {
    		$this->_sessionBeans = $this->getCache()->load($this->getCacheKey());
    	}
    	// or create a new container for the session if the session is new
    	else {
    		$this->_sessionBeans = new TechDivision_Model_Container_Session();
    	}
    }

    /**
     * This method saves the session data in the database and
     * runs the garbage collector.
     *
     * @return void
     * @see TechDivision_Model_Interfaces_Container::saveSession()
     */
    protected function _saveSession()
    {
    	// save the session beans in the cache
    	$this->getCache()->save(
    		$this->_sessionBeans,
    		$this->getCacheKey(),
    		array(TechDivision_Model_Container_Session::CACHE_TAG),
    		TechDivision_Model_Container_Utils::SESSION_TIMEOUT
    	);
    }

    /**
     * This method forces the container to use the master
     * database manager only when requesting a slave.
     *
     * @return void
     * @see TechDivision_Model_Interfaces_Container::useMasterOnly()
     */
    public function useMasterOnly()
    {
		// check if the flag, force using the master database
		// manager, is already set
		if (!$this->_forceMaster) {
			// if not, set it
        	$this->_forceMaster = true;
        	// log setting the flag
        	$this->_getLogger()->debug(
        		'Successfully set flag to force container using the master ' .
        	    ' database manager only',
        	    __LINE__
        	);
		}
    }

    /**
	 * This method returns the session id of the
	 * actual request.
	 *
	 * @return string Holds the requested session id
	 * @see TechDivision_Model_Interfaces_Container::getSessionId()
	 */
    public function getSessionId()
    {
    	return $this->_sessionId;
    }

    /**
     * Returns the cache instance.
     *
     * @return Zend_Cache_Core The cache instance
     */
    public function getCache()
    {
        return $this->_cache;
    }

    /**
     * Attaches the defined Pointcuts to the
     * passed Proxy.
     *
     * @param TechDivision_AOP_Interfaces_Proxy $proxy
     * 		The Proxy to initialize
     * @return TechDivision_AOP_Interfaces_Proxy
     * 		The initialized Proxy
     */
    public function attachPointcuts(
        TechDivision_AOP_Interfaces_Proxy $proxy) {
        // add the Proxy's Pointcuts
        foreach ($this->getPointcuts() as $pointcut) {
            // add the pointcut to the Proxy
            $proxy->addPointcut($pointcut->getInstance($this));
        }
        // return the initialized Proxy
        return $proxy;
    }

    /**
     * Sets the pointcuts.
     *
     * @param array $pointcuts The pointcuts
     * @return TDProject_Application The instance itself
     */
    public function setPointcuts(array $pointcuts)
    {
		$this->_pointcuts = $pointcuts;
		return $this;
    }

    /**
     * Returns the pointcuts.
     *
     * @return array The pointcuts
     */
    public function getPointcuts()
    {
        return $this->_pointcuts;
    }

    /**
     * Initializes the pointcuts found in the projects
     * configuration files.
     *
	 * @return TDProject_Application The instance itself
     */
    protected function _initializePointcuts()
    {
    	// initialize the array for the pointcut configuration
    	$pointcuts = array();
	    // check if configuration has already been cached
	    if (($cachedPointcuts = $this->getCache()->load('model_pointcuts'))) {
	        // log that pointcut configuration was found cached
	        $this->_getLogger()->info('Found cached pointcut configuration');
	        // set the cached pointcut configuration and return the instance itself
	        return $this->setPointcuts($cachedPointcuts);
	    }
        // log that no cached configuration was found
        $this->_getLogger()->info('Load poincut configuration');
        // create the directory iterator
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(getcwd())
        );
        // iterate over the directory recursively and look for configurations
        while ($it->valid()) {
            if (!$it->isDot()) {
                // if a configuration file in folder META-INF was found
                if (basename($it->getSubPathName()) == 'pointcuts.xml' &&
                	strrchr($it->getSubPath(), 'META-INF') !== false) {
                    // initialize the SimpleXMLElement with the content of pointcut XML file
                    $sxe = new SimpleXMLElement(
                        file_get_contents($it->getSubPathName(), true)
                    );
                    // iterate over the found nodes
                    foreach ($sxe->xpath('/pointcuts/pointcut') as $child) {
                        // initialize the pointcut
                        $pointcut = TechDivision_Model_Configuration_Pointcut::create()
                            ->setClassName((string) $child->className)
                            ->setIncludeFile((string) $child->includeFile)
                            ->setInterceptWithMethod((string) $child->interceptWithMethod)
	                        ->setMethodToIntercept((string) $child->methodToIntercept)
	                        ->setAdvice((string) $child->advice);
                        // add it the array
                        $pointcuts[] = $pointcut;
                    }
                }
            }
            // proceed with the next folder
            $it->next();
        }
        // cache the pointcut configuration
        $this->getCache()->save($pointcuts, 'model_pointcuts', array('configuration'));
        // set the poincut configuration and return the instance itself
        return $this->setPointcuts($pointcuts);
    }

    /**
     * Factory method for a new instance of the
     * class with the passed name.
     *
     * @param string Name of the class to create and return the oject for
     * @param array The arguments passed to the classes constructor
	 * @return TechDivision_AOP_Interfaces_AspectContainer
	 * 		The AspectContainer instance
     */
    public function newInstance($className, array $arguments = array())
    {
    	if ($this->_useAOP) {
	    	// log that instance using AOP will be created
	    	$this->_getLogger()->debug(
	            	"Create instance of $className using AOP", __LINE__
	            );
	    	// create the Proxy
	    	$proxy = $this->getObjectFactory()
	       		->newInstance('TechDivision_Model_Proxy_Generator')
	            	->newProxy($className, $arguments)
	    	        ->setProxyCache($this->getCache());
	       	// attach pointcuts and return the AspectContainer
	        return $this->attachPointcuts($proxy);
    	}
    	// log that instance using AOP will be created
    	$this->_getLogger()->debug(
    		"Create instance of $className NOT using AOP"
    	);
    	// if AOP is NOT activated create an instance without Proxy
    	return $this->getObjectFactory()
    		->newInstance($className, $arguments);
    }

	/**
	 * Returns the object factory.
	 *
	 * @return TDProject_Factory_Object The object factory
	 */
	public function getObjectFactory()
	{
	    return $this->_objectFactory;
	}
}