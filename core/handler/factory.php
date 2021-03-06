<?php

/**
 * Factory Handler
 *
 * Copyright 2016-2019 Jerry Shaw <jerry-shaw@live.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace core\handler;

use core\system;

class factory extends system
{
    //Factory storage
    private static $storage = [];

    /**
     * Get original object from called class using alias name
     * Defined by only class name created last time
     *
     * @param string $alias
     *
     * @return $this
     * @throws \Exception
     */
    public static function use(string $alias): object
    {
        if (!isset(self::$storage[$key = self::build_alias(get_called_class(), $alias)])) {
            throw new \Exception('Object "' . get_called_class() . ':' . $alias . '" NOT found!', E_USER_ERROR);
        }

        unset($alias);
        return self::$storage[$key];
    }

    /**
     * Get new cloned object from called class or alias name
     * Defined by both class name and arguments
     *
     * @return $this
     */
    public static function new(): object
    {
        return clone self::stock(__FUNCTION__, get_called_class(), func_get_args());
    }

    /**
     * Obtain original object from another class
     * Defined by both class name and arguments
     *
     * @param string $class
     * @param array  $param
     *
     * @return object
     */
    public static function obtain(string $class, array $param = []): object
    {
        return self::stock(__FUNCTION__, parent::build_name($class), $param);
    }

    /**
     * Reflect method
     *
     * @param string $class
     * @param string $method
     *
     * @return \ReflectionMethod
     * @throws \ReflectionException
     */
    protected static function reflect(string $class, string $method): \ReflectionMethod
    {
        //Reflection list
        static $list = [];

        //Return constructor
        if ('__construct' === $method && isset($list[$class])) {
            return $list[$class];
        }

        //Get method reflection
        $reflect = new \ReflectionMethod($class, $method);

        //Check method visibility
        if (!$reflect->isPublic()) {
            throw new \ReflectionException($class . '::' . $method . ': NOT for public!', E_USER_WARNING);
        }

        //Save constructor reflection
        if ('__construct' === $method) {
            $list[$class] = &$reflect;
        }

        unset($class, $method);
        return $reflect;
    }

    /**
     * Stock controller
     *
     * @param string $type
     * @param string $class
     * @param array  $param
     *
     * @return object
     */
    private static function stock(string $type, string $class, array $param): object
    {
        //Generate object key
        $key = hash('md5', $type . ':' . $class . ':' . json_encode($param));

        //Create object and save to storage
        if (!isset(self::$storage[$key])) {
            self::$storage[$key] = !empty($param) ? new $class(...$param) : new $class();
        }

        unset($type, $class, $param);
        return self::$storage[$key];
    }

    /**
     * Build alias name for a class
     *
     * @param string $class
     * @param string $alias
     *
     * @return string
     */
    private static function build_alias(string $class, string $alias): string
    {
        $hash = hash('md5', $class . ':' . $alias);

        unset($class, $alias);
        return $hash;
    }

    /**
     * Config class settings
     *
     * @param array $setting
     *
     * @return $this
     */
    public function config(array $setting): object
    {
        $setting = array_intersect_key($setting, get_object_vars($this));

        foreach ($setting as $key => $val) {
            $this->$key = $val;
        }

        unset($setting, $key, $val);
        return $this;
    }

    /**
     * Copy object as alias and remove source
     * Alias is merged with called class and alias name
     * Different names should be using conditionally to avoid conflicts
     *
     * @param string $alias
     *
     * @return $this
     */
    public function as(string $alias): object
    {
        self::$storage[$key = self::build_alias(get_class($this), $alias)] = &$this;

        unset($alias);
        return self::$storage[$key];
    }

    /**
     * Free from factory storage
     */
    public function free(): void
    {
        if (!empty($list = array_keys(self::$storage, $this, true))) {
            foreach ($list as $key) {
                self::$storage[$key] = null;
                unset(self::$storage[$key]);
            }
        }

        unset($list, $key);
    }
}