<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

if(!function_exists('w3tc_fragmentcache_start')){
function w3tc_fragmentcache_start(){
    return NULL;
}
}
if(!defined('W3TC_DYNAMIC_SECURITY')){
    define('W3TC_DYNAMIC_SECURITY', '123abc');
}
class w3tcTest extends WP_UnitTestCase{

    function test_content_filter(){
        $content='[activedemand_block id="123"]';
        $filtered= apply_filters('the_content', $content);

        $this->assertTrue(strpos($filtered, 'mfunc')>0);

    }
}
