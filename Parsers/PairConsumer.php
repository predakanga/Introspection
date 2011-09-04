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

/**
 * Description of PairConsumer
 *
 * @author predakanga
 */
abstract class PairConsumer {
    protected $list = array();
    
    protected $handlers = array();
    
    protected $iter;
    
    protected $modifiers = array(T_ABSTRACT, T_FINAL, T_PRIVATE, T_PROTECTED, T_PUBLIC, T_STATIC);
    protected $quietTokens = array(T_ABSTRACT, T_FINAL, T_PRIVATE, T_PROTECTED, T_PUBLIC, T_STATIC);
    
    public function __construct(TypedStatementList $pairs) {
        $this->iter = $pairs->getIterator();
        
        $this->parse($this->iter);
    }
    
    protected function getTokenType($token) {
        if($token instanceof TypedStatementList)
            return $token->type;
        return $token[0];
    }
    
    protected function getTokenString($token) {
        
    }
    
    protected function getTokenName($token) {
        $tokenType = $this->getTokenType($token);
        return @token_name($tokenType) ?: $tokenType;
    }
    
    public function parse(ArrayIterator $iter) {
        while($next = $this->expects($iter, array_keys($this->handlers), true)) {
            $response =  call_user_method($this->handlers[$this->getTokenType($next)], $this, $iter);
            $this->list[] = $response;
            
            $iter->next();
        }
    }
    
    public function nextToken($iter) {
        $iter->next();
        do
        {
            $token = $iter->current();
            if($token instanceof TypedStatementList || $token[0] != T_WHITESPACE) {
                return $token;
            }
            $iter->next();
        } while($iter->valid());
        return null;
    }
    
    public function lookBehind(SeekableIterator $iter, $firstTypeOrTypes) {
        $tokenTypes = $firstTypeOrTypes;
        if(!is_array($tokenTypes)) {
            $tokenTypes = array($firstTypeOrTypes);
            for($i = 2; $i < count(func_get_args()); $i++)
                $tokenTypes[] = func_get_arg($i);
        }
        
        $tokens = array();
        // Use array access to avoid rewinding
        for($i = $iter->key()-1; $i >= 0; $i--) {
            $token = $iter[$i];
            $tokenType = $this->getTokenType($token);
            if(!in_array($tokenType, $tokenTypes))
                break;
            $tokens[] = $token;
        }
        
        return array_reverse($tokens);
    }
    
    public function expects(Iterator $iter, $firstTypeOrTypes) {
        $tokenTypes = $firstTypeOrTypes;
        if(!is_array($tokenTypes)) {
            $tokenTypes = array($firstTypeOrTypes);
            for($i = 2; $i < count(func_get_args()); $i++)
                $tokenTypes[] = func_get_arg($i);
        }
        
        while($iter->valid()) {
            $token = $iter->current();
            $tokenType = $this->getTokenType($token);
            
            if(in_array($tokenType, $tokenTypes))
                return $token;
            else {
                if(!in_array($tokenType, $this->quietTokens))
                    echo "Missing " . (@token_name($tokenType) ?: $tokenType) . " on " . get_class($this) . "\n";
                $this->list[] = $token;
            }
            
            $iter->next();
        }
        return null;
    }
    
    public function until(Iterator $iter, $firstTypeOrTypes) {
        $tokenTypes = $firstTypeOrTypes;
        if(!is_array($tokenTypes)) {
            $tokenTypes = array($firstTypeOrTypes);
            for($i = 2; $i < count(func_get_args()); $i++)
                $tokenTypes[] = func_get_arg($i);
        }
        
        $tokens = array();
        while($iter->valid()) {
            $token = $iter->current();
            $tokenType = $this->getTokenType($token);
            
            $tokens[] = $token;
            
            if(in_array($tokenType, $tokenTypes))
                    break;
            
            $iter->next();
        }
        return $tokens;
    }
}

?>
