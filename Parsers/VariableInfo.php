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

/**
 * Description of VariableInfo
 *
 * @author predakanga
 */
class VariableInfo extends PairConsumer {
    protected $varModifiers;
    protected $varDefault;
    protected $varType;
    
    public function __construct(ArrayIterator $iter, PairConsumer $parent, $end) {
        $this->parent = $parent;
        $this->list[] = $iter->current();
        
        $this->name = $this->getTokenString($iter->current());
        
        if($parent instanceof FunctionInfo) {
            //echo("Parent is a funcinfo\n");
            $typeTokens = $this->lookBehind($iter, array(T_STRING, T_NS_SEPARATOR, T_WHITESPACE));
            //array_pop($typeTokens);
            $this->varType = "";
            foreach($typeTokens as $token) {
                $this->varType .= $this->getTokenString($token);
            }
            $this->list = array_merge($typeTokens, $this->list);
        }
        $this->varModifiers = $this->lookBehind($iter, $this->modifiers);
        // Prepend the modifiers to the list
        $this->list = array_merge($this->varModifiers, $this->list);
        // TODO: Should be able to lookbehind on T_STRING, T_NS_SEPARATOR to
        // enable type-hinting on function arguments
        $this->varDefault = null;
        $next = $this->nextToken($iter);
        if($next && (strpos($end, $next[0]) === false)) {
            // Skip the =
            $iter->next();
            $remainingTokens = $this->until($iter, true, $end);
            array_pop($remainingTokens);
            
            $this->varDefault = "";
            foreach($remainingTokens as $token) {
                $this->varDefault .= $this->getTokenString($token);
            }
        }
        // HACKHACK
        elseif($parent instanceof FunctionInfo) {
            array_pop($this->list);
            $iter->seek($iter->key()-1);
        }
        $this->cleanup();
    }
    
    public function getModifiers() {
        return $this->varModifiers;
    }
    
    public function getDefault() {
        return $this->varDefault;
    }
    
    public function getIsReference() {
        return ($this->name[0] == "&");
    }
    
    public function getType() {
        return $this->varType;
    }
}

?>
