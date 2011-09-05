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

/**
 * Description of TypedStatementList
 *
 * @author predakanga
 */
class TypedStatementList implements IteratorAggregate {
    public $type;
    protected $list;
    
    private $balancers = array( '(' => ')',
                                '{' => '}',
                                '[' => ']',
                                T_OPEN_TAG => T_CLOSE_TAG,
                                T_OPEN_TAG_WITH_ECHO => T_CLOSE_TAG,
                                T_START_HEREDOC => T_END_HEREDOC,
                                T_CLOSE_TAG => T_OPEN_TAG,
                                T_CURLY_OPEN => '}',
                                T_DOLLAR_OPEN_CURLY_BRACES => '}');
    
    public function __construct(ArrayIterator $iter, $start = null, $end = null) {
        $this->type = $start;
        
        $list = array();
        
        if($end) {
            $list[] = array($this->getToken($iter), $this->getString($iter));
            $iter->next();
        }
        while($iter->valid()) {
            $token = $this->getToken($iter);
            
            if($token == $end) {
                $list[] = array($token, $this->getString($iter));
                $iter->next();
                $this->list = $list;
                return;
            } elseif(isset($this->balancers[$token])) {
                $list[] = new TypedStatementList($iter, $token, $this->balancers[$token]);
            } else {
                $list[] = array($token, $this->getString($iter));
                $iter->next();
            }
        }
        
        $this->list = $list;
    }
    
    public function getSource() {
        $source = "";
//        return "-complex-";
        foreach($this->list as $token) {
            if($token instanceof TypedStatementList) {
                $source .= $token->getSource();
            } else {
                $source .= $token[1];
            }
        }
        return $source;
    }
    
    protected function getToken(ArrayIterator $iter) {
        $token = $iter->current();
        $token = $token[0]; // Valid tokens are 258-380, ASCII in PHP 5.3
        return $token;
    }
    
    protected function getString(ArrayIterator $iter) {
        $string = $iter->current();
        if(is_array($string))
            $string = $string[1];
        return $string;
    }
    
    public function getIterator() {
        return new ArrayIterator($this->list);
    }
}

?>
