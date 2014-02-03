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
require_once 'TechDivision/Model/Interfaces/Entity.php';

/**
 * This is the container to handle the information of
 * a storable in the container.
 *
 * @package TechDivision_Model
 * @author Tim Wagner <t.wagner@techdivision.com>
 * @copyright TechDivision GmbH
 * @link http://www.techdivision.com
 * @license GPL
 */
class TechDivision_Model_Container_Entity extends TechDivision_Lang_Object
{

	/**
	 * The cache tag.
	 * @var string
	 */
	const CACHE_TAG = 'container_entity';

	/**
	 * The cache key prefix.
	 * @var string
	 */
	const CACHE_KEY_PREFIX = 'entity_';

    /**
     * Holds the class name of the entity.
     * @var string
     */
    protected $_name = null;

    /**
     * Holds the type of the storable, <b>view</b> or <b>persistent</b>.
     * @var string
     */
    protected $_type = null;

    /**
     * The cache instance to use.
     * @var TechDivision_Model_Interfaces_Container_Cache
     */
    protected $_cache = null;

	/**
	 * Temporary entity storage.
	 * @var array
	 */
    protected $_entities = array();

    /**
     * Initializes the container for the entity instances and passes
     * the cache instance to use.
     *
     * @param TechDivision_Model_Interfaces_Container_Cache $cache
     * 		The cache instance to use
     */
    public function __construct(
    	TechDivision_Model_Interfaces_Container_Cache $cache)
    {
    	$this->setCache($cache);
    }

    /**
     * Persists the entities to the cache.
     *
     * @return void
     */
    public function __destruct()
    {
    	// iterate over the entities and persist them in cache
    	foreach ($this->_entities as $entity) {
    		$cacheKey = $this->getCacheKey($entity->getPrimaryKey());
    		$this->getCache()->save($entity->disconnect(), $cacheKey, array(self::CACHE_TAG));
    	}
    }

    /**
     * Sets the cache instance to use.
     *
     * @param TechDivision_Model_Interfaces_Container_Cache $cache
     * @return TechDivision_Model_Container_Entity
     * 		The instance itself
     */
    public function setCache(
    	TechDivision_Model_Interfaces_Container_Cache $cache)
    {
    	$this->_cache = $cache;
    	return $this;
    }

    /**
     * Returns the cache instance to use.
     *
     * @return TechDivision_Model_Interfaces_Container_Cache
     * 		The cache instance
     */
    public function getCache()
    {
    	return $this->_cache;
    }

    /**
     * Returns the unique cache key for the entity.
     *
     * @param TechDivision_Model_Interfaces_Entity $entity
     * 		The entity to create the cache key for
     */
    public function getCacheKey($key)
    {
		return self::CACHE_KEY_PREFIX .
			strtolower($this->getName()) . '_' . $key;
    }

    /**
     * This method adds a entity to the internal array.
     *
     * @param TechDivision_Model_Interfaces_Entity $entity
     * 		The entity that should be registered in the container
     */
    public function add($entity)
    {
    	$cacheKey = $this->getCacheKey($entity->getPrimaryKey());
    	$this->_entities[$cacheKey] = $entity;
    }

    /**
     * Removes the passed entity from the container.
     *
     * @param TechDivision_Model_Interfaces_Entity $entity
     * 		The entity that should be remove from the container
     */
    public function remove($entity)
    {
    	$cacheKey = $this->getCacheKey($entity->getPrimaryKey());
    	unset($this->_entities[$cacheKey]);
    	$this->getCache()->remove($cacheKey);
    }

    /**
     * This method looks in the internal hash map
     * for the entity with the passed key. If it
     * is found the entity is returned.
     *
     * @param integer $key Holds the key of the entity that should be returned
     * @return TechDivision_Model_Interfaces_Entity
     * 		Returns the entity with the passed key or null
     */
    public function lookup($key)
    {
		// create the unique cache key of the entity
    	$cacheKey = $this->getCacheKey($key);
    	// check if a entity with the passed primary key is available
        if (array_key_exists($cacheKey, $this->_entities)) {
        	return $this->_entities[$cacheKey];
        }
        // check if the requested entity exists in cache
        elseif($this->getCache()->test($cacheKey)) {
        	return $this->getCache()->load($cacheKey);
        }
        // if not return null
        else {
        	return null;
        }
    }

    /**
     * This method sets the classname of the entity.
     *
     * @param string $string Holds the classname of the entity
     */
    public function setName($string)
    {
        $this->_name = $string;
    }

    /**
     * This method sets the type of the entity.
     *
     * @param string $string Holds the type of the entity
     */
    public function setType($string)
    {
        $this->_type = $string;
    }

    /**
     * This method returns the classname of the entity.
     *
     * @return string Holds the classname of the entity
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * This method returns the type of the entity.
     *
     * @return string Holds the type of the entity
     */
    public function getType()
    {
        return $this->_type;
    }
}