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
require_once("NamespaceInfo.php");
require_once("ClassInfo.php");
require_once("VariableInfo.php");
require_once("InterfaceInfo.php");

/**
 * Description of PHPBlockInfo
 *
 * @author predakanga
 */
class PHPBlockInfo extends PairConsumer {
    protected $withEcho = false;
    
    protected $handlers = array(T_CLASS => 'classHandler',
                                T_INTERFACE => 'interfaceHandler',
                                T_NAMESPACE => 'namespaceHandler',
                                T_DECLARE => 'unimpl',
                                T_FUNCTION => 'unimpl',
                                T_USE => 'useHandler',
                                T_VARIABLE => 'variableHandler');
    
    protected $uses = array();
    
    public function __construct(TypedStatementList $pairs, $withEcho = false) {
        $this->quietTokens = array_merge($this->quietTokens, array(T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_CLOSE_TAG));
        
        $this->withEcho = $withEcho;
        // If it's a withEcho php block, just store the pair source
        if($this->withEcho) {
            $this->list[] = $pairs;
            
            $this->cleanup();
        } else {
            // Otherwise, continue normally
            parent::__construct($pairs);
        }
    }
    
    protected function namespaceHandler(ArrayIterator $iter) {
        return new NamespaceInfo($iter);
    }
    
    protected function useHandler(ArrayIterator $iter) {
        $this->list[] = $iter->current();
        $iter->next();
        $this->uses = array_merge($this->uses, $this->readTypes($iter, ';'));
        $this->list[] = $iter->current();
        
        return null;
    }
    
    protected function classHandler(ArrayIterator $iter) {
        return new ClassInfo($iter);
    }
    
    protected function interfaceHandler(ArrayIterator $iter) {
        return new InterfaceInfo($iter);
    }
    
    protected function variableHandler(ArrayIterator $iter) {
        return new VariableInfo($iter, ';');
    }
}

?>
