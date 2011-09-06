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
    public $name;
    protected $parent;
    
    protected $handlers = array();
    
    protected $iter;
    
    protected $modifiers = array(T_ABSTRACT, T_FINAL, T_PRIVATE, T_PROTECTED, T_PUBLIC, T_STATIC, T_WHITESPACE);
    protected $skipModifiers = array(T_ABSTRACT => 'skip',
                                     T_FINAL => 'skip',
                                     T_PRIVATE => 'skip',
                                     T_PROTECTED => 'skip',
                                     T_PUBLIC => 'skip',
                                     T_STATIC => 'skip');
    
    //protected $quietTokens = array(T_ABSTRACT, T_FINAL, T_PRIVATE, T_PROTECTED, T_PUBLIC, T_STATIC);
    protected $quietTokens = array(T_DOC_COMMENT, T_COMMENT, T_WHITESPACE);
    
    public function __construct(TypedStatementList $pairs) {
        $this->iter = $pairs->getIterator();
        
        $this->parse($this->iter);
        
        // After parsing, remove the various parser vars
        $this->cleanup();
    }
    
    protected function cleanup() {
        unset($this->handlers);
        unset($this->iter);
        unset($this->modifiers);
        unset($this->skipModifiers);
        unset($this->quietTokens);
    }
    
    protected function getTokenType($token) {
        if($token instanceof TypedStatementList)
            return $token->type;
        return $token[0];
    }
    
    protected function getTokenString($token) {
        if($token instanceof TypedStatementList)
            return $token->getSource();
        else if(is_array($token))
            return $token[1];
        else
            return $token;
    }
    
    protected function getTokenName($token) {
        $tokenType = $this->getTokenType($token);
        return @token_name($tokenType) ?: $tokenType;
    }
    
    protected function parse(Iterator $iter) {
        while($next = $this->expects($iter, array_keys($this->handlers), true)) {
            $response =  call_user_method($this->handlers[$this->getTokenType($next)], $this, $iter);
            if($response)
                $this->list[] = $response;
            
            $iter->next();
        }
    }
    
    protected function nextToken(Iterator $iter, $store = true) {
        $iter->next();
        do
        {
            $token = $iter->current();
            $found = false;
            if($token instanceof TypedStatementList || $token[0] != T_WHITESPACE)
                $found = true;
            
            if(!$found || $store)
                $this->list[] = $token;
            
            if($found)
                return $token;
            
            $iter->next();
        } while($iter->valid());
        return null;
    }
    
    protected function lookBehind(SeekableIterator $iter, $firstTypeOrTypes) {
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
        
        // Trim leading whitespace, if applicable
        if(in_array(T_WHITESPACE, $tokenTypes)) {
            for($i = count($tokens); $i > 0; $i--) {
                if($this->getTokenType($tokens[$i-1]) == T_WHITESPACE)
                    unset($tokens[$i-1]);
                else
                    break;
            }
        }
        return array_reverse($tokens);
    }
    
    protected function expects(Iterator $iter, $firstTypeOrTypes) {
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
                $tempList[] = $token;
//                if(!in_array($tokenType, $this->quietTokens)) {
//                    echo "Missing " . (@token_name($tokenType) ?: $tokenType) . " (" . $tokenType . ") on " . get_class($this) . " (not in " . implode(",", $this->quietTokens) . ")\n";
//                    echo $this->getTokenString($token) . "\n";
//                }
                $this->list[] = $token;
            }
            
            $iter->next();
        }
        return null;
    }
    
    protected function until(Iterator $iter, $store, $firstTypeOrTypes) {
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
        if($store)
            $this->list = array_merge($this->list, $tokens);
        return $tokens;
    }
    
    protected function skip(Iterator $iter) {
        return null;
    }
    
    protected function unimpl(Iterator $iter) {
        //echo "WARNING: Unimplemented " . $this->getTokenName($iter->current()) .
        //     " on " . get_class($this) . "\n";
        return $iter->current();
    }
    
    public function getSource() {
        $source = "";
        foreach($this->list as $token) {
            if($token instanceof TypedStatementList || $token instanceof PairConsumer) {
                $source .= $token->getSource();
            } else {
                $source .= $token[1];
            }
        }
        return $source;
    }
    
    protected function readTypes(Iterator $iter, $firstTypeOrTypes) {
        $tokenTypes = $firstTypeOrTypes;
        if(!is_array($tokenTypes)) {
            $tokenTypes = array($firstTypeOrTypes);
            for($i = 2; $i < count(func_get_args()); $i++)
                $tokenTypes[] = func_get_arg($i);
        }
        
        
        $tokens = $this->until($iter, false, $tokenTypes);
        array_pop($tokens);
        $this->list = array_merge($this->list, $tokens);

        $toRet = array();
        $curType = "";
        foreach($tokens as $token) {
            if($curType == "" && $this->getTokenType($token) == T_WHITESPACE)
                continue;
            if($this->getTokenType($token) == ",") {
                $toRet[] = $curType;
                $curType = "";
            } else {
                $curType .= $this->getTokenString($token);
            }
        }
        $toRet[] = trim($curType);
        return $toRet;
    }
    
    public function getListSize() {
        return count($this->list);
    }
    
    public function findClass($className) {
        foreach($this->findObjects("class", true) as $class) {
            if($class->name == $className)
                return $class;
        }
        return null;
    }
    
    public function findObjects($type, $recurse = false) {
        $toRet = array();
        
        foreach($this->list as $item) {
            if($type == "class" && $item instanceof ClassInfo && !($item instanceof InterfaceInfo)) {
                $toRet[] = $item;
            } elseif($type == "interface" && $item instanceof InterfaceInfo) {
                $toRet[] = $item;
            } elseif($type == "function" && $item instanceof FunctionInfo) {
                $toRet[] = $item;
            } elseif($type == "variable" && $item instanceof VariableInfo) {
                $toRet[] = $item;
            } elseif($type == "namespace" && $item instanceof NamespaceInfo) {
                $toRet[] = $item;
            } elseif($type == "php" && $item instanceof PHPBlockInfo) {
                $toRet[] = $item;
            } elseif($item instanceof PairConsumer && $recurse) {
                $toRet = array_merge($toRet, $item->findObjects($type));
            }
        }
        
        return $toRet;
    }
    
    public function getParent() {
        return $this->parent;
    }
}

?>
