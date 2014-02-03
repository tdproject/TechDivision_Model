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
class TechDivision_Model_Container_Plugin extends TechDivision_Lang_Object
{

    /**
     * Holds the classname of the storable.
     * @var string
     */
    private $_name = null;

    /**
     * Holds the type of the entity, view or persistent.
     * @var string
     */
    private $_type = null;

    /**
     * This method sets the classname of the storable.
     *
     * @param string $string Holds the classname of the storable
     */
    public function setName($string)
    {
        $this->_name = $string;
    }

    /**
     * This method sets the type of the storable.
     *
     * @param string $string Holds the type of the storable
     */
    public function setType($string)
    {
        $this->_type = $string;
    }

    /**
     * This method returns the classname of the storable.
     *
     * @return string Holds the classname of the storable
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * This method returns the type of the storable.
     *
     * @return string Holds the type of the storable
     */
    public function getType()
    {
        return $this->_type;
    }
}