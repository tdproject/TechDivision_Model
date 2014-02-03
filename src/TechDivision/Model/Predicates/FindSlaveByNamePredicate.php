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
require_once 'TechDivision/Lang/String.php';
require_once 'TechDivision/Collections/Interfaces/Predicate.php';

/**
 * This class is the predicate for checking if the target
 * of a rewrite equals the one passed to the constructor.
 *
 * @package TechDivision_Model
 * @author Tim Wagner <t.wagner@techdivision.com>
 * @copyright TechDivision GmbH
 * @link http://www.techdivision.com
 * @license GPL
 */
class TechDivision_Model_Predicates_FindSlaveByNamePredicate
    extends TechDivision_Lang_Object
    implements TechDivision_Collections_Interfaces_Predicate {

    /**
	 * Holds the name the found pattern has to equal
	 * @var String
	 */
    private $_name = null;

    /**
     * Constructor that initializes the internal member
     * with the value passed as parameter.
     *
     * @param string $name Holds the name the found pattern has to equal
     * @return void
     */
    public function __construct($name)
    {
		// set the member
        $this->_name = $name;
    }

    /**
     * This method equals the pattern of the passed Mapping
     * with the name passed to the constructor. If the Strings
     * are equal it returns TRUE, else FALSE.
     *
     * @param Rewrite $object Holds the Mapping to compare its pattern with
     * @return boolean Returns TRUE if the name equals the found pattern
     */
    public function evaluate($object)
    {
        // if the pattern matches the passed name
        if($this->_name == $object->getDataSourceName()) {
        	return true;
        }
        // if not, return FALSE
        return false;
    }
}