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

require_once 'TechDivision/AOP/Proxy/Generator.php';

/**
 * This class is a wrapper for all classes an Aspect relies on.
 *
 * The class intercepts the original method call using PHP's
 * magic __call method.
 *
 * The actual version only works for non static methods.
 *
 * @package TechDivision_Model
 * @author Tim Wagner <t.wagner@techdivision.com>
 * @copyright TechDivision GmbH
 * @link http://www.techdivision.com
 * @license GPL
 */
class TechDivision_Model_Proxy_Generator extends TechDivision_AOP_Proxy_Generator
{

    /**
     * (non-PHPdoc)
     * @see TechDivision_AOP_Proxy_Generator::newProxy()
     */
    public function newProxy($className, array $arguments = array())
    {
        // create and set the ReflectionClass to create the Proxy for
        $this->setReflectionClass(new ReflectionClass($className));
        // create a new instance of the Proxy
        return TechDivision_Util_Object_Factory::get()
            ->newInstance($this->load(), $arguments)->initProxy();
    }

    /**
     * (non-PHPdoc)
     * @see TechDivision_AOP_Proxy_Generator::load()
     */
    public function load()
    {
        // load the filename of the proxy to generate
        $proxyFileName = $this->getProxyFileName();
        // check if the file has already been generated
        if (!file_exists($proxyFileName)) {
            // initialize the directory for the proxy to be stored
            $dir = './' . dirname($proxyFileName);
            // check if the directory exists
            if (!is_dir($dir)) {
                // if not, create it
                mkdir($dir, 0755, true);
            }
            // store the source code of the proxy
            file_put_contents($proxyFileName, $this->create());
        }
        // return the proxy's class name
        return $this->getProxyClass();
    }
}