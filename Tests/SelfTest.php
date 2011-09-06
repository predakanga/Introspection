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

class SourceDirectoryFilter extends \RecursiveFilterIterator {
	public static $DIR_FILTERS = array('.git',
                                       'libs',
                                       'plugins',
                                       'compiled',
                                       'templates_c',
                                       'scratch');
    // Use of require_once filters index.php, so this is all we need to worry about
    public static $FILE_FILTERS = array();
	
	public function accept() {
		if($this->current()->isDir())
            return !in_array($this->current()->getFilename(),
                             self::$DIR_FILTERS,
                             true);
        else
            return !in_array($this->current()->getFilename(),
                             self::$FILE_FILTERS,
                             true);
	}
}

if(!defined("D_S"))
    define("D_S", DIRECTORY_SEPARATOR);

/**
 * Description of SelfTest
 *
 * @author predakanga
 */
class SelfTest extends PHPUnit_Framework_TestCase {
    protected $files;
    protected $introspector;
    
    protected function assertIdenticalArray($expected, $actual) {
        // First, check that they're the same size
        $this->assertEquals(count($expected), count($actual), "Expected and actual arrays don't match in size");
        
        foreach($expected as $key => $value) {
            if($actual[$key] != $value) {
                $this->fail("Arrays differ at key $key");
            }
        }
    }
    
    private function stripWhitespaceTokens($input) {
        if(is_array($input) && $input[0] == T_WHITESPACE)
            return false;
        return true;
    } 
    
    private function stripLineNumbers($input) {
        if(is_array($input))
            unset($input[2]);
        return $input;
    }
    
    public function provider() {
        $dirIter = new \RecursiveDirectoryIterator(__DIR__ . D_S . "..");
        $filterIter = new SourceDirectoryFilter($dirIter);
        $iterIter = new \RecursiveIteratorIterator($filterIter);
        $regexIter = new \RegexIterator($iterIter, '/\\.php$/');
        
        return array_map(function($fileInfo) {
            return array(realpath($fileInfo->getPathname()));
        }, iterator_to_array($regexIter, false));
    }
    
    protected function setUp() {
        require_once(__DIR__ . D_S . ".." . D_S . "Introspector.php");
        $this->introspector = new Introspector();
    }
    
    /**
     * @dataProvider provider
     */
    public function testCompileOwnFiles($file) {
            $result = $this->introspector->readFile($file);
            $this->assertNotNull($result);
            $this->assertGreaterThan(0, $result->getListSize(), "Could not parse file, or file was empty.");
    }
    
    /**
     * @depends testCompileOwnFiles
     * @dataProvider provider
     */
    public function testAccurateCompilationNoWS($file) {
        $parsedSrc = $this->introspector->readFile($file)->getSource();
        $origSrc = file_get_contents($file);

        $origTokens = token_get_all($origSrc);
        $parsedTokens = token_get_all($parsedSrc);

        $strippedOrig = array_filter($origTokens, array($this, 'stripWhitespaceTokens'));
        $strippedParsed = array_filter($parsedTokens, array($this, 'stripWhitespaceTokens'));

        $strippedOrig = array_map(array($this, 'stripLineNumbers'), $strippedOrig);
        $strippedParsed = array_map(array($this, 'stripLineNumbers'), $strippedParsed);

        $strippedOrig = array_values($strippedOrig);
        $strippedParsed = array_values($strippedParsed);

        try {
            $this->assertIdenticalArray($strippedOrig, $strippedParsed);
        } catch(Exception $e) {
            echo "Was in file $file\n";
            var_dump($strippedOrig);
            var_dump($strippedParsed);
            throw $e;
        }
    }
    
    /**
     * @depends testAccurateCompilationNoWS
     * @dataProvider provider
     */
    public function testAccurateCompilationWithWS($file) {
        $parsedSrc = $this->introspector->readFile($file)->getSource();
        $origSrc = file_get_contents($file);

        $origTokens = token_get_all($origSrc);
        $parsedTokens = token_get_all($parsedSrc);

        $strippedOrig = array_map(array($this, 'stripLineNumbers'), $origTokens);
        $strippedParsed = array_map(array($this, 'stripLineNumbers'), $parsedTokens);

        $this->assertIdenticalArray($strippedOrig, $strippedParsed);
    }
    
    /**
     * @depends testAccurateCompilationWithWS
     * @dataProvider provider
     */
    public function testCompletelyAccurateCompilation($file) {
        $parsedSrc = $this->introspector->readFile($file)->getSource();
        $origSrc = file_get_contents($file);

        $origTokens = token_get_all($origSrc);
        $parsedTokens = token_get_all($parsedSrc);

        $this->assertIdenticalArray($origTokens, $parsedTokens);
    }
}

?>
