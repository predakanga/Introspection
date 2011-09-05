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
    protected $varName;
    protected $varDefault;
    
    public function __construct($iter, $end) {
        $this->list[] = $iter->current();
        
        $this->varName = $iter->current();
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
        $this->cleanup();
    }
}

?>
