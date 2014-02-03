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
 * Interface of all entity home objects.
 *
 * @package TechDivision_Model
 * @author Tim Wagner <t.wagner@techdivision.com>
 * @copyright TechDivision GmbH
 * @link http://www.techdivision.com
 * @license GPL
 */
interface TechDivision_Model_Interfaces_LocalHome
{

    /**
     * This method returns the container that
     * handles the storable.
     *
     * @return TechDivision_Model_Interfaces_Container
     * 		Returns a reference to the container used to handle the storable
     */
    public function getContainer();

    /**
     * This method returns a initialized entity.
     *
     * @param TechDivision_Lang_Integer $pk The primary key of the entity
     * @return TechDivision_Model_Interfaces_Entity The instance
     */
    public function findByPrimaryKey(TechDivision_Lang_Integer $pk);

    /**
     * This method creates a new instance of the entity and returns it.
	 *
     * @return TechDivision_Model_Interfaces_Entity The instance
     */
    public function epbCreate();

    /**
     * Returns the entity's alias name (the class name usually).
     *
     * @return string The entity's alias name
     */
    public function getEntityAlias();

    /**
     * Returns the alias name of the entity's mapping class.
     *
     * @return string The entity's mapping alias
     */
    public function getMappingAlias();
}