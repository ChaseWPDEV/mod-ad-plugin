<?php

namespace ActiveDemand;


class FormLinker{

  public $forms=array();

  public static $customer_actions=[
    'Customer Created'=>'woocommerce_created_customer',
    'Customer Updated'=>'profile_updated'
  ];

  public static $order_status_actions=[
    'Order Created'=>'new_shop_order',
    'Order Processing'=>'processing_shop_order',
    'Order Completed'=>'completed_shop_order',
    'Order Cancelled'=>'cancelled_shop_order',
    'Order Refunder'=>'refunded_shop_order'
  ];

  public static $form_xml=NULL;

  public static function load_form_xml(){
    if(!isset(self::$form_xml) || !is_a(self::$form_xml, 'SimpleXMLElement')){
      $url = "https://api.activedemand.com/v1/forms.xml";
      $str = activedemand_getHTML($url, 10);
      self::$form_xml = simplexml_load_string($str);
    }
    return self::$form_xml;
  }

  public static function build_full_linker(){
    self::load_form_xml();
    $ids=array_map(function($a){return (int) $a->id;}, self::$form_xml->children());

     return new FormLinker($ids);
  }

  public static function form_list_dropdown($name, $atts=array(), $selected=null){
    self::load_form_xml();

    $output='<select name="'.$name.'"';
    foreach($atts as $k=>$v){
      $output.=" $k=\"$v\"";
    }
    $output.='><option';
    if(!isset($selected) || $selected==0) $output.= 'selected>';
    $output.='>None</option>';
    foreach(self::$form_xml->children() as $child){
      $id=(int) $child->id;
      $output.="<option value=\"$id\"";
      if($id==$selected) $output.=' selected';
      $output.=">{$child->name}</option>";
    }
    $output.='</select>';
    return $output;
  }

  public static function form_link_table($arr, $atts=array()){
    $setting=get_option(PREFIX.'_wc_actions_forms');
    $output="<table";
    foreach($atts as $k=>$v){
      $output.=" $k=\"$v\"";
    }
    $output.=">";
    foreach($arr as $name=>$hook){
      $output.="<tr><td>$name</td>"
      ."<td>".self::form_list_dropdown(PREFIX."_wc_actions_forms[$hook]",
        ['class'=>'ad_form_link_dropdown'],
          isset($setting[$hook]) ? $setting[$hook] : NULL)."</td>"
          ."<td><div>Pencil Icon"
          .wp_nonce_field($hook.'-reset','form_mapper_reset_nonce', true, false)
          ."</td></tr>";
        }
      $ouput.="</table>";
      return $output;
  }

  public static function linked_forms_page(){

    if(!is_array($setting)) $setting=array();
    ?>
    <form method="post" action="options.php">
      <?php settings_fields(PREFIX.'_woocommerce_linked_actions'); ?>
      <h2>Customer Profile Actions</h2>
      <?php echo self::form_link_table(self::$$customer_actions, ['class'=>'customer_form_table']);?>
      <h2>WooCommerce Order Status Changes</h2>
      <?php echo self::form_link_table(self::$order_status_actions, ['class'=>'order_form_table']);?>
      <input type="submit" value="Save">
    </form>
    <?php
  }

  public static function initialize_hooks(){
    //Register all applicable customer actions
    foreach(self::$customer_actions as $hook){
      $setting=get_option(PREFIX."_form_$hook");
      if(!$setting || empty($setting)) continue;
      if(!isset($setting['id']) || !isset($setting['map'])) continue;
      add_action($hook, function($customer_id) use ($setting){
        $id=$setting['id'];
        $map=$setting['map'];
        $data=array();
        $user=get_userdata($customer_id);
        foreach($map as $name=>$arg){
          switch($arg){
            case 'username':
              $data[$name]=$user->username;
              break;
            case 'user_firstname':
              $data[$name]=$user->first_name;
              break;
            case 'user_lastname':
            $data[$name]=$user->last_name;
            break;
            case 'user_email':
            $data[$name]=$user->user_email;
            break;
          }
        }
        $url="https://submit.activedemand.com/submit/form/$id";
        $response=wp_remote_post($url, array(
          'body'=>$data
        ));

        if(is_wp_error($response)){
          $msg=$response->get_error_message();
          new WP_Error($msg);
        }
      }, 15, 1);
    }

    //Register all applicable Order Status Changes actions
    foreach(self::$order_status_actions as $hook){
      $setting=get_option(PREFIX."_form_$hook");
      if(!$setting || empty($setting)) continue;
      if(!isset($setting['id']) || !isset($setting['map'])) continue;
      add_action($hook, function($new_status, $old_status, $post) use ($setting){
        $id=$setting['id'];
        $map=$setting['map'];
        $data=array();
        $user=get_userdata(get_current_user_id());
        foreach($map as $name=>$arg){
          switch($arg){
            case 'username':
              $data[$name]=$user->username;
              break;
            case 'user_firstname':
              $data[$name]=$user->first_name;
              break;
            case 'user_lastname':
            $data[$name]=$user->last_name;
            break;
            case 'user_email':
            $data[$name]=$user->user_email;
            break;
          }
        }
        $url="https://submit.activedemand.com/submit/form/$id";
        $response=wp_remote_post($url, array(
          'body'=>$data
        ));

        if(is_wp_error($response)){
          $msg=$response->get_error_message();
          new WP_Error($msg);
        }
      }, 15, 3);
    }
  }

  function __construct($ids){
    $collector=ShortCodeCollector::get_instance();
    $collector->reset();
    $collector->server_side=true;
    if(is_array($ids)){
      foreach($ids as $id){
        $collector->add_form($id);
      }
    } else{
      $collector->add_form($id);
    }

    $reply=(array) \json_decode($collector->get_reply());

    foreach($reply as $form){
      $matches=[];
      if(\preg_match('/<form.*form>/s',(string) $form,$matches));
      $dom= new \DOMDocument();
      $dom->loadHTML($matches[0]);
      $id=$this->get_form_id($dom);
      $labels=$this->get_form_labels($dom);
      $this->forms[$id]=$labels;
    }

}

  function get_form_labels($form_dom){
    $output=array();
    $labels=$form_dom->getElementsByTagName('label');
    foreach($labels as $label){
      $content=$label->textContent;
      $for=$label->attributes->getNamedItem('for')->nodeValue;
      $input=$form_dom->getElementById($for);
      $name=$input->attributes->getNamedItem('name')->nodeValue;
      $output[$name]=$content;
      }
      return $output;
    }

  function get_form_id($form_dom){
    $matches=array();
    $action=$form_dom->getElementsByTagName('form')[0]->attributes->getNamedItem('action')->nodeValue;
    \preg_match('/\d+$/', $action, $matches);
    return $matches[0];
  }

  function get_form_field_dropdown($id, $name, $selected=NULL){
    $labels=$this->forms[$id];
    $output="<select name=\"$name\">";
    foreach($labels as $name=>$content){
      $output.="<option value=\"$label\"";
      if($selected===$name) $output.=" selected";
      $option.=">$content</option>";
    }
    $output.="</select>";
    return $output;
  }

  function form_field_mapper($id,$options,$setting=[]){
    $labels=$this->forms[$id];
    $output="<table>";
    foreach($labels as $name=>$content){
      $selected=(!empty($setting) && isset($setting[$name])) ?
        $setting['map'][$name] : NULL;
      $output.="<tr><td>$content</td><td>";
      $output.="<select name=\"map[$name]\">";

      foreach($options as $option=>$description){
        $output.="<option value=\$option\"";
        if($option===$selected) $output.=' selected';
        $option.=">$description</option>";
      }
      $output.='</select>';
      $output.="</td></tr>";
    }
    $output.="</table>";
    return $output;
  }

  function form_mapper($id, $action){
    $setting=get_option(PREFIX."_form_$action") ? get_option(PREFIX."_form_$action") : array();
    $options=[
      'username'=>'User Name',
      'user_firstname'=>'First Name',
      'user_lastname'=>'Last Name',
      'user_email'=>'Email'
    ];
    return '<form class="ad_form_mapper '.$action.'_mapper">'
      . wp_nonce_field($action.'-'.$id.'-update', 'form_mapper_update_nonce', true, false)
      . $this->form_field_mapper($id, $options, $setting)
      .'<input type="button" value="Save" onclick="ad_form_linker_update('.$id.', '.$$action.');">'
      .'</form>';
  }

}

add_action('init', array(__NAMESPACE__.'\FormLinker', 'initialize_hooks'));

add_action('wp_ajax_reset_ad_form_linkage', __NAMESPACE__.'\ajax_reset_action_form');
add_action('wp_ajax_update_ad_form_linkage', __NAMESPACE__.'\ajax_update_action_form');


function ajax_reset_action_form(){
  //TODO:validate_ajax_nonce

  $action=\filter_var($_POST['action_hook'], FILTER_SANITIZE_STRING);
  $id=\filter_var($_POST['form_id'], FILTER_SANITIZE_NUMBER);

  check_ajax_referer($action.'-reset', 'form_mapper_reset_nonce');
  update_option(PREFIX."_form_action", ['id'=>$id]);
}

function ajax_update_action_form(){

  $action=\filter_var($_POST['action_hook'], FILTER_SANITIZE_STRING);
  $id=\filter_var($_POST['form_id'], FILTER_SANITIZE_NUMBER);

  check_ajax_referer($action.'-'.$id.'-update', 'form_mapper_update_nonce');
  $map=(array) \filter_var($_POST['map'], FILTER_SANITIZE_STRING);

  update_option(PREFIX."_form_action", [
    'id'=>$id,
    'map'=>$map]);
}

function ajax_show_form_mapper(){
  //TODO:validate_ajax_nonce

  $id=\filter_var($_POST['form_id'], FILTER_SANITIZE_NUMBER);
  $action=\filter_var($_POST['action_hook'], FILTER_SANITIZE_STRING);
  $linker=new FormLinker($id);
  echo $linker->form_mapper($id, $action);
  wp_die();
}


 ?>
