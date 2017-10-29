<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

/**
 * Something all Models extend. Do not use this.
 * @internal
 */
class Base implements \JsonSerializable, \Serializable { //TODO: Nya
    /**
     * @internal
     */
    function jsonSerialize() {
        return get_object_vars($this);
    }
    
    /**
     * @internal
     */
    function serialize() {
        $vars = \get_object_vars($this);
        
        foreach($vars as $key => $val) {
            if($val instanceof \Closure) {
                unset($vars[$key]);
            }
        }
        
        return \serialize($vars);
    }
    
    /**
     * @internal
     */
    function unserialize($data) {
        $this->__construct(\unserialize($data));
    }
    
    /**
     * @internal
     */
    function _patch(array $data) {
        foreach($data as $key => $val) {
            if(\strpos($key, '_') !== false) {
                $key = \lcfirst(\str_replace('_', '', \ucwords($key, '_')));
            }
            
            if(\property_exists($this, $key)) {
                if($this->$key instanceof \CharlotteDunois\Yasmin\Utils\Collection) {
                    if(!\is_array($val)) {
                        $val = array($val);
                    }
                    
                    foreach($val as $element) {
                        $instance = $this->$key->get($element['id']);
                        if($instance) {
                            $instance->_patch($element);
                        }
                    }
                } else {
                    if(\is_object($this->$key)) {
                        if(\is_array($val)) {
                            $this->$key = clone $this->$key;
                            $this->$key->_patch($val);
                        } else {
                            if($val === null) {
                                $this->$key = null;
                            } else {
                                $class = '\\'.\get_class($this->$key);
                                
                                $exp = \ReflectionMethod::export($class, '__construct', true);
                                preg_match('/Parameters \[(\d+)\]/', $exp, $count);
                                $count = (int) $count[1];
                                
                                if($count === 1) {
                                    $this->$key = new $class($val);
                                } elseif($count === 2) {
                                    $this->$key = new $class($this->client, $val);
                                } elseif($count === 3) {
                                    $this->$key = new $class($this->client, ($this->guild ? $this->guild : ($this->channel ? $this->channel : null)), $val);
                                } else {
                                    $this->client->emit('debug', 'Manual update of '.$key.' in '.\get_class($this).' ('.$count.') required');
                                }
                            }
                        }
                    } else {
                        if($this->$key !== $val) {
                            $this->$key = $val;
                        }
                    }
                }
            }
        }
    }
    
    /**
     * @internal
     */
    function _shouldUpdate(array $data) {
        $oldData = \json_decode(\json_encode($this), true);
        
        foreach($data as $key => $val) {
            if(\strpos($key, '_') !== false) {
                $key = \lcfirst(\str_replace('_', '', \ucwords($key, '_')));
            }
            
            if(\array_key_exists($key, $oldData) && $oldData[$key] !== $val) {
                return true;
            }
        }
        
        return false;
    }
}