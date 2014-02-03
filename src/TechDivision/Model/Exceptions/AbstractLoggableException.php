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

require_once 'TechDivision/Logger/Logger.php';
require_once 'TechDivision/Model/Interfaces/Exception.php';

/**
 * Abstract base class of all exceptions.
 *
 * @package TechDivision_Model
 * @author Tim Wagner <t.wagner@techdivision.com>
 * @copyright TechDivision GmbH
 * @link http://www.techdivision.com
 * @license GPL
 */
abstract class TechDivision_Model_Exceptions_AbstractLoggableException
    extends Exception
    implements TechDivision_Model_Interfaces_Exception {

    /**
     * This member holds an instance of the logger.
     * @var TechDivision_Logger_Interfaces_Logger $logger
     */
    private $_logger = null;

    /**
     * The constructor passes the message
     * to the exception base class.
     *
     * @param string $message The message of the exception
     */
    public function __construct($message)
    {
		// pass the message to the superclass
        Exception::__construct($message);
		// log the exception
        $this->_logger = TechDivision_Logger_Logger::forObject($this);
        $this->_logger->error($this->__toString());
    }
}