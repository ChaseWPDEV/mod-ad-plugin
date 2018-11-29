<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class RetrieveTest extends WP_UnitTestCase {
    
    function setUp(){
        update_option('activedemand_options_field', ['activedemand_appkey'=>'2096248f-f7a888bf-fbcf1852-4bfe6319-bccf97']);
        $_SERVER['HTTP_REFERER']='http://cwpdev.com';
        $_SERVER['HTTP_USER_AGENT']='Mozilla/4.5 [en] (X11; U; Linux 2.2.9 i586)';  
    }
    
    function test_array_structure(){
        $array= ActiveDemand\activedemand_get_landing_ids();
        $this->assertEquals(count($array),2);
    }
}