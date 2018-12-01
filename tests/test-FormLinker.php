<?php

use \ActiveDemand as AD;

class FormLinkerTest extends WP_UnitTestCase{
  public $FormLinker;

  public static $user_id;
  public static function setUpBeforeClass(){
    self::$user_id=wp_create_user('Testing User', "Blippity!", "test@email.com");
  }

  function setUp(){
    update_option('activedemand_options_field', ['activedemand_appkey'=>'2096248f-f7a888bf-fbcf1852-4bfe6319-bccf97']);
    $_SERVER['HTTP_REFERER']='http://cwpdev.com';
    $_SERVER['HTTP_USER_AGENT']='Mozilla/4.5 [en] (X11; U; Linux 2.2.9 i586)';

    $this->FormLinker=AD\FormLinker::build_full_linker();
    $this->assertFalse(empty($this->FormLinker->forms));
  }

  function test_init_profile_hooks(){
    $action=array_values(AD\FormLinker::$customer_actions)[0];
    $form_id=array_keys($this->FormLinker->forms)[0];
    update_option(AD\PREFIX.'_wc_actions_forms',[$action=>$form_id]);
    $map=array();
    foreach($this->FormLinker->forms[$form_id] as $name=>$content){
      $map[$name]='username';
    }
    update_option(AD\PREFIX."_form_$action",
      ['id'=>$form_id,
        'map'=>$map]);
    AD\FormLinker::initialize_hooks();
    global $wp_filter;

    $this->assertTrue(isset($wp_filter[$action]->callbacks[15]));

    do_action($action, self::$user_id);

    $this->assertTrue(is_array(AD\FormLinker::$last_response));

  }

}

 ?>
