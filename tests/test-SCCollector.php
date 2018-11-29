<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class scCollectorTest extends WP_UnitTestCase{
    public $collector;

    function setUp(){
        $this->collector=ActiveDemand\ShortCodeCollector::get_instance();
        $this->collector->reset();
        $this->collector->server_side=TRUE;
    }

    function test_instance(){
        $this->assertTrue(is_a($this->collector, 'ActiveDemand\\ShortCodeCollector'));
        $col= ActiveDemand\ShortCodeCollector::get_instance();
        $this->assertEquals($this->collector,$col);
    }

    function test_code(){
        $block1=$this->collector->add_block('2869');
        $block2=$this->collector->add_block('2870');
        $json= $this->collector->get_codes();
        $this->assertTrue($block1!=$block2);
        $this->assertTrue(strpos($json, ':"2869')>0);
        $this->assertTrue(strpos($json, ':"2870')>0);


        $_SERVER['HTTP_REFERER']='http://cwpdev.com';
        $_SERVER['HTTP_USER_AGENT']='Mozilla/4.5 [en] (X11; U; Linux 2.2.9 i586)';
        update_option(ActiveDemand\PREFIX.'_options_field', [ActiveDemand\PREFIX.'_appkey'=>'2096248f-f7a888bf-fbcf1852-4bfe6319-bccf97']);

        $args=$this->collector->make_args();
        $this->assertTrue(array_key_exists(ActiveDemand\PREFIX.'_session_guid', $args));
        $this->assertFalse(empty($args[ActiveDemand\PREFIX.'_session_guid']));

        $fstring= \ActiveDemand\activedemand_field_string($args);

        $this->assertTrue(strpos($fstring, ActiveDemand\PREFIX.'_session_guid')>0);
        $this->assertTrue(strpos($fstring, 'pi-key')>0);

        $response=$this->collector->post_codes();
        $this->assertTrue(strpos($response, 'activedemand_blocks_0":')>0);
        $this->assertTrue(strpos($response, 'activedemand_blocks_1":')>0);
    }

    function test_process_serverside_script(){
        $this->collector->add_block('2869');

        $script=ActiveDemand\process_shortcodes_script();


        $this->assertTrue(strpos($script, 'script>')>0);
        $this->assertTrue(strpos($script, 'function cycleAndReplace(obj){')>0);
        $this->assertTrue(strpos($script, 'function prefixThePopup(popup){')>0);
        $this->assertTrue(strpos($script, ActiveDemand\PREFIX.'_shortcodes={')>0);
    }

    function test_process_clientside_script(){
        $this->collector->server_side=FALSE;

        $this->collector->add_block('2869');

        $script=ActiveDemand\process_shortcodes_script();
        do_action('init');
        $this->assertTrue(strpos($script, 'script>')>0);
        $this->assertTrue(strpos($script, 'function cycleAndReplace(obj){')>0);
        $this->assertTrue(strpos($script, 'function prefixThePopup(popup){')>0);
        $this->assertTrue(strpos($script, "jQuery.post('". $this->collector->url)>0);
    }

    function test_shortcodes(){
        do_shortcode('[activedemand_form id="123"]');
        do_shortcode('[activedemand_block id="321"]');
        $codes= json_decode($this->collector->get_codes(), TRUE);

        $this->assertTrue(in_array('123', $codes['forms']));
        $this->assertTrue(in_array('321', $codes['blocks']));
    }
}
