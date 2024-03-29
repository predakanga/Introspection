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

require_once("PairConsumer.php");
require_once("VariableInfo.php");

/**
 * Description of FunctionInfo
 *
 * @author predakanga
 */
class FunctionInfo extends PairConsumer {
    protected $handlers = array(T_VARIABLE => 'argHandler');
    protected $funcModifiers;
    protected $funcBlock;
    
    public function __construct(ArrayIterator $iter, PairConsumer $parent) {
        $this->parent = $parent;
        
        $this->funcModifiers = $this->lookBehind($iter, $this->modifiers);
        // Prepend the modifiers to the list
        $this->list = array_merge($this->funcModifiers, $this->list);
        
        $this->list[] = $iter->current();
        $iter->next();
        
        while($this->getTokenType($iter->current()) == T_WHITESPACE) {
            $this->list[] = $iter->current();
            $iter->next();
        }
        $funcNameTokens = $this->until($iter, false, '(');
        $funcArgs = array_pop($funcNameTokens);
        
        $this->list = array_merge($this->list, $funcNameTokens);
        
        $this->name = "";
        foreach($funcNameTokens as $token) {
            $this->name .= $this->getTokenString($token);
        }
        
        $this->quietTokens = array_merge($this->quietTokens, array('(', ')'));
        $this->handlers += $this->skipType;
        
        parent::__construct($funcArgs);
        
        $this->funcBlock = $this->nextToken($iter);
    }
    
    protected function argHandler(ArrayIterator $iter) {
        return new VariableInfo($iter, $this, ",)");
    }
    
    public function getArguments() {
        return $this->findObjects("variable");
    }
    
    public function getArgument($name) {
        foreach($this->getArguments() as $arg) {
            $argName = $arg->name;
            if($argName[0] == "&")
                $argName = substr($argName, 1);
            
            if($argName == $name)
                return $arg;
        }
        return null;
    }
    
    public function getModifiers() {
        return $this->funcModifiers;
    }
    
    public function getReturnsReference() {
        return ($this->name[0] == "&");
    }
    
    public function getBlock() {
        return $this->funcBlock;
    }
}

?>
