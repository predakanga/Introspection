<?php

/*
 * Copyright (c) 2011, predakanga
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the <organization> nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

require_once("TypedStatementList.php");
require_once("PairConsumer.php");
require_once("FunctionInfo.php");
require_once("VariableInfo.php");

/**
 * Description of ClassInfo
 *
 * @author predakanga
 */
class ClassInfo extends PairConsumer {
    protected $handlers = array(T_FUNCTION => "functionHandler",
                                T_VARIABLE => "propertyHandler",
                                T_CONST => "unimpl");
    
    protected $implements;
    protected $extends;
    protected $classModifiers;
    
    public function __construct(ArrayIterator $iter, PairConsumer $parent) {
        $this->parent = $parent;
        $this->quietTokens = array_merge($this->quietTokens, array('{', '}'));
        
        $this->classModifiers = $this->lookBehind($iter, $this->modifiers);
        // Prepend the modifiers to the list
        $this->list = array_merge($this->classModifiers, $this->list);
        
        $this->list[] = $iter->current();
        $className = $this->nextToken($iter);
        $this->name = $this->getTokenString($className[1]);
        $iter->next();
        
        while($start = $this->expects($iter, T_IMPLEMENTS, T_EXTENDS, '{')) {
            if($this->getTokenType($start) == T_IMPLEMENTS) {
                $this->list[] = $iter->current();
                $this->nextToken($iter, false);
                $this->implements = $this->readTypes($iter, T_EXTENDS, '{');
            } elseif($this->getTokenType($start) == T_EXTENDS) {
                $this->list[] = $iter->current();
                $this->nextToken($iter, false);
                $this->extends = $this->readTypes($iter, T_IMPLEMENTS, '{');
            } else {
                $classScope = $iter->current();

                $this->handlers += $this->skipModifiers; // Array union operator - who knew?

                parent::__construct($classScope);
                $iter->next();
                return;
            }
        }
    }
    
    protected function functionHandler(ArrayIterator $iter) {
        return new FunctionInfo($iter, $this);
    }
    
    protected function propertyHandler(ArrayIterator $iter) {
        return new VariableInfo($iter, $this, ';');
    }
    
    public function getImplements() {
        return $this->implements;
    }
    
    public function getExtends() {
        return $this->extends;
    }
    
    public function getModifiers() {
        return $this->classModifiers;
    }
    
    public function getProperties() {
        return $this->findObjects("property");
    }
    
    public function getProperty($name) {
        foreach($this->getProperties() as $prop) {
            if($prop->name == $name)
                return $prop;
        }
        return null;
    }
    
    public function getMethods() {
        return $this->findObjects("function");
    }
    
    public function getMethod($name) {
        foreach($this->getMethods() as $func) {
            $funcName = $func->name;
            if($funcName[0] == "&")
                $funcName = substr($funcName, 1);
            
            if($funcName == $name)
                return $func;
        }
        return null;
    }
}

?>
