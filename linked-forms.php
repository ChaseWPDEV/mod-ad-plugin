<?php

namespace ActiveDemand;


class FormLinker{

  public $forms=array();

  public static $customer_actions=array();

  public static $order_status_actions=array();
  public static $last_response;
  public static $form_xml=NULL;

  public static function initialize_class_vars(){
    self::$customer_actions=array(
      'Customer Created'=>'woocommerce_created_customer',
      'Customer Updated'=>'profile_updated'
    );

    self::$order_status_actions=array(
      'Order Created'=>'new_shop_order',
      'Order Processing'=>'processing_shop_order',
      'Order Completed'=>'completed_shop_order',
      'Order Cancelled'=>'cancelled_shop_order',
      'Order Refunded'=>'refunded_shop_order'
    );
  }

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
    $arr=(array) self::$form_xml->children();
    $ids=array();
    foreach($arr as $v){
      $ids[]=(int) $v->id;
    }
     return new FormLinker($ids);
  }

  public static function form_list_dropdown($name, $atts=array(), $selected=null){
    self::load_form_xml();

    $output='<select name="'.$name.'"';
    foreach($atts as $k=>$v){
      $output.=" $k=\"$v\"";
    }
    $output.='><option value="0"';
    if(!isset($selected) || $selected==0) $output.= ' selected>';
    $output.='None</option>';
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
      $url=add_query_arg('form_mapper_show_nonce', wp_create_nonce($hook.'-show'),
          add_query_arg('action', 'show_form_mapper',
          add_query_arg('action_hook', $hook,
          admin_url('admin-ajax.php'))));
      if(isset($setting[$hook]) && $setting[$hook]){
        $id=$setting[$hook];
        $style="display:block;";
      } else{
        $id=NULL;
        $style="display:none;";
      }
      $style.='text-decoration:none;color:black;';
      $output.="<tr><td>$name</td>"
      ."<td>"
      .self::form_list_dropdown(PREFIX."_wc_actions_forms[$hook]",
        array('class'=>'ad-formlink-dropdown'), $id)
      .wp_nonce_field($hook.'-reset',"form_mapper_reset_$hook", true, false)
      ."</td>"
      .'<td><a class="ad-edit-linkedform '.$hook.'" style="'.$style.'"'
      .'href="'.$url
      .' .ad_form_mapper" data-featherlight="ajax"><span class="dashicons dashicons-edit"></span></a>'
      ."</td></tr>";
        }
      $output.="</table>";
      return $output;
  }

  public static function linked_forms_page(){

    ?>
      <h2>Customer Profile Actions</h2>
      <?php echo self::form_link_table(self::$customer_actions, array('class'=>'customer_form_table'));?>
      <h2>WooCommerce Order Status Changes</h2>
      <?php echo self::form_link_table(self::$order_status_actions, array('class'=>'order_form_table'));?>
    <?php
  }

  public static function map_field_keys($map, $vars){
    $user=$vars['user'];
    $order=isset($vars['order']) ? $vars['order'] : NULL;
    $cart=isset($vars['cart']) ? $vars['cart'] : NULL;
    $data=array();
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
        case 'produce_ids':
          $ids=array();
          foreach($order->items as $product){
            if(!is_a($product, 'WC_Order_Item_Product')) continue;
            $ids[]=$product->get_product_id();
          }
          $data[$name]=\json_encode($ids);
          break;
        case 'product_names':
          $names=array_map(function($item){return $item->get_name();}, $order->items);
          $data[$name]=\json_encode($names);
          break;
        case 'product_prices':
            $price_map=array();
            foreach($order->items as $product){
              if(!is_a($product, 'WC_Order_Item_Product')) continue;
              $price_map[$product->get_name()]=$product->get_subtotal();
            }
            $data[$name]=\json_encode($price_map);
        case 'cart_product_data':
          $products = $cart['cart']['cart'];
          $data[$name]='';
          foreach ($products as $product) {
            $product_name = get_the_title($product['product_id']);
            $data[$name] .= "Product Name: $product_name \n"
                . "Product price: " . $product['price'] . '\n'
                . 'Product Qty: ' . $product['quantity'] . '\n'
                . 'Total: ' . $product['line_total'] . '\n\n';
          }
        default:
        $data[$name]=get_user_meta($customer_id, $arg);
      }
    }
    return $data;
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
        $user=get_userdata($customer_id);
        $data=FormLinker::map_field_keys($map, array('user'=>$user));
        $url="https://submit.activedemand.com/submit/form/$id";
        $response=wp_remote_post($url, array(
          'body'=>$data
        ));

        if(is_wp_error($response)){
          $msg=$response->get_error_message();
          new \WP_Error($msg);
        } else{
          self::$last_response=$response;
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
        $order=new WC_Order($post->ID);
        $user_id=$order->user_id;
        if(!($user_id)) return;
        $user=get_userdata($user_id);
        $data=FormLinker::map_field_keys($map, array('user'=>$user, 'order'=>$order));
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
      $collector->add_form($ids);
    }
    $reply=(array) \json_decode($collector->get_reply());
    foreach($reply as $form){
      $matches=array();
      if(\preg_match('/<form.*form>/s',(string) $form,$matches)){
      $dom= new \DOMDocument();
      $dom->loadHTML($matches[0]);
      $id=$this->get_form_id($dom);
      $labels=$this->get_form_labels($dom);
      $this->forms[$id]=$labels;
    } else {
      new \WP_Error('No Form Found in AD Reply');
    }
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
    $form_array=$form_dom->getElementsByTagName('form');
    $form=$form_array[0];
    $form_attributes=$form->attributes;
    if(isset($form_attributes)){
      $action=$form_attributes->getNamedItem('action')->nodeValue;
      \preg_match('/\d+$/', $action, $matches);
      return $matches[0];
    } else{
      new \WP_Error('Form DOM returned '. print_r($form_dom->getElementsByTagName, true));
  }
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

  function form_field_mapper($id,$options,$setting=array()){
    $labels=$this->forms[$id];
    $output="<table>";
    foreach($labels as $name=>$content){
      $selected=(!empty($setting) && isset($setting['map'][$name])) ?
        $setting['map'][$name] : NULL;
      $output.="<tr><td>$content</td><td>";
      $output.="<select name=\"map[$name]\"><option disabled";
      if(!isset($selected)) $output.=' selected';
      $output.="></option>";

      foreach($options as $option=>$description){
        $output.="<option value=\"$option\"";
        if($option===$selected) $output.=' selected';
        $output.=">$description</option>";
      }
      $output.='</select>';
      $output.="</td></tr>";
    }
    $output.="</table>";
    return $output;
  }

  function form_mapper($id, $action){
    $setting=get_option(PREFIX."_form_$action") ? get_option(PREFIX."_form_$action") : array();
    $options=array(
      'username'=>'User Name',
      'user_firstname'=>'First Name',
      'user_lastname'=>'Last Name',
      'user_email'=>'Email',
      'billing_company'=>'Company',
      'billing_postcode'=>'Zipcode',
      'billing_state'=>'State/Province',
      'billing_country'=>'Country',
      'billing_phone'=>'Phone'
    );
    if(in_array($action, self::$order_status_actions)){
      $options=\array_merge($options, array(
        'product_ids'=> 'Product IDs',
        'product_names'=>'Product Names',
        'product prices'=>'Product Prices'
      ));
    }
    if($action===PREFIX.'_stale_cart_map'){
      $options['cart_product_data']="Product Data";
    }
    return '<form class="ad_form_mapper '.$action.'_mapper">'
      . wp_nonce_field($action.'-'.$id.'-update', 'form_mapper_update_nonce', true, false)
      . $this->form_field_mapper($id, $options, $setting)
      .'<div style="float:right;margin-top:15px;">'
      .'<input type="button" value="Save Changes" class="button-primary" onclick="ad_form_linker_update(event, '.$id.', \''.$action.'\');">'
      .'<input type="button" value="Cancel" class="button-primary" '
        .'style="color: black;background-color:white;text-shadow:none;border-color: black;margin-left: 5px;box-shadow:none;" '
        .'onclick="jQuery.featherlight.close();" />'
      .'</div>'
      .'</form>';
  }

}

add_action('init', array(__NAMESPACE__.'\FormLinker', 'initialize_hooks'));
add_action('plugins_loaded', array(__NAMESPACE__.'\FormLinker', 'initialize_class_vars'));

add_action('wp_ajax_reset_ad_form_linkage', __NAMESPACE__.'\ajax_reset_action_form');
add_action('wp_ajax_update_ad_form_linkage', __NAMESPACE__.'\ajax_update_action_form');
add_action ('wp_ajax_show_form_mapper', __NAMESPACE__.'\ajax_show_form_mapper');

function ajax_reset_action_form(){
  $action=\filter_var($_POST['action_hook'], \FILTER_SANITIZE_STRING);
  check_ajax_referer($action.'-reset', 'form_mapper_reset_nonce');

  $id=\filter_var($_POST['form_id'], \FILTER_SANITIZE_NUMBER_INT);

  check_ajax_referer($action.'-reset', 'form_mapper_reset_nonce');
  if($id===0){
    delete_option(PREFIX."_form_$action");
    echo "Form Deleted";
  } else if(update_option(PREFIX."_form_$action", array('id'=>$id))){
    echo \json_encode(array($action=>$id));
  } else{
    new \WP_Error("Could not update $action to $id");
  }
  wp_die();
}

function ajax_update_action_form(){

  $action=\filter_var($_POST['action_hook'], \FILTER_SANITIZE_STRING);
  $id=\filter_var($_POST['form_id'], \FILTER_SANITIZE_NUMBER_INT);

  check_ajax_referer($action.'-'.$id.'-update', 'form_mapper_update_nonce');

  $map=array();
  foreach($_POST['map'] as $k=>$v){
    $map[$k.']']=$v;
  }


  $option=array('id'=>$id,'map'=>$map);

  if(update_option(PREFIX."_form_$action", $option)){
      $option['action']=$action;
      echo \json_encode($option);
    } else{
      new \WP_Error("Could not update $action");
    }
    wp_die();
}

function ajax_show_form_mapper(){
  $action=\filter_var($_GET['action_hook'], \FILTER_SANITIZE_STRING);
  check_ajax_referer($action.'-show', 'form_mapper_show_nonce');

  $setting=get_option(PREFIX."_form_$action");
  $id=$setting['id'];
  $linker=new FormLinker($id);
  echo $linker->form_mapper($id, $action);
  wp_die();
}

add_action('admin_enqueue_scripts', function(){
  wp_enqueue_script('featherlight', plugins_url('/includes/featherlight/featherlight.min.js',__FILE__), array('jquery'));
  wp_enqueue_style('featherlight-style', plugins_url('/includes/featherlight/featherlight.min.css',__FILE__) );
  wp_enqueue_script('activedemand-formlinker', plugins_url('/includes/activedemand-admin-formlinker.js', __FILE__), array('jquery'), '0.1');

});

 ?>
