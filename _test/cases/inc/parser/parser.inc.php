<?php
/**
* @version $Id: parser.inc.php,v 1.2 2005/03/25 21:00:22 harryf Exp $
* @package Doku
* @subpackage Tests
*/

/**
* Includes
*/
require_once DOKU_INC . 'inc/init.php';
require_once DOKU_INC . 'inc/confutils.php';
require_once DOKU_INC . 'inc/parser/parser.php';
require_once DOKU_INC . 'inc/parser/handler.php';
require_once DOKU_INC . 'inc/events.php';
require_once DOKU_INC . 'inc/mail.php';

//require_once DOKU . 'parser/renderer.php';

//Mock::generate('Doku_Renderer');

/**
* @package Doku
* @subpackage Tests
*/
class TestOfDoku_Parser extends UnitTestCase {

    var $P;
    var $H;

    function TestOfDoku_Parser() {
        $this->UnitTestCase('TestOfDoku_Parser');
    }

    function setup() {
        $this->P = new Doku_Parser();
        $this->H = new Doku_Handler();
        $this->P->Handler = & $this->H;
    }

    function tearDown() {
        unset($this->P);
        unset($this->H);
    }
}

function stripByteIndex($call) {
    unset($call[2]);
    if ($call[0] == "nest") {
      $call[1][0] = array_map('stripByteIndex',$call[1][0]);
    }
    return $call;
}
