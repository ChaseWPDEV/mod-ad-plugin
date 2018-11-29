<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ActiveDemand;

class ShortCodeCollector{
    public $url;
    public $server_side;
  	public $posts_processed=array();
    private $blocks=array();
    private $forms=array();
    private $show_popups;
    private $has_fired;
    private $guid_value;
    private $reply;

    public static function get_instance(){
        static $instance=NULL;
        if(!isset($instance)){
            $instance=new ShortCodeCollector();
        }
        return $instance;
    }

    private function __construct() {
        $this->url='https://api.activedemand.com/v1/smart_blocks/show_all';
        $options = retrieve_activedemand_options();
        $show = get_option(PREFIX.'_server_showpopups');
        $this->show_popups=(is_array($options) && array_key_exists(PREFIX.'_appkey', $options) && $show);
        $this->server_side=get_option(PREFIX.'_server_side', TRUE);
        if (!isset($this->server_side)) {
            $this->server_side=TRUE;
        }
        $this->has_fired=FALSE;
    }
    public function reset(){
        $this->has_fired=FALSE;
        $this->blocks=array();
	      $this->forms=array();
    }

    private function add_shortcode($id, $slug){
        $div='activedemand_'.$slug.'_'.count($this->$slug);
        $this->$slug[$div]=$id;
        return $div;
    }

    public function has_content(){
        return (count($this->blocks) + count($this->forms)>0) || $this->show_popups;
    }

    public function add_block($id){
        $div='activedemand_blocks_'.count($this->blocks);
        $this->blocks[$div]=$id;
        return $div;
    }

    public function add_form($id){
        $div='activedemand_forms_'.count($this->forms);
        $this->forms[$div]=$id;
        return $div;
    }

    public function make_args(){
        $options = retrieve_activedemand_options();
        $activedemand_ignore_block_style = false;
        $activedemand_ignore_form_style = false;
        if (array_key_exists(PREFIX.'_ignore_block_style', $options)) {
            $activedemand_ignore_block_style = $options[PREFIX.'_ignore_block_style'];
        }
        if (array_key_exists(PREFIX.'_ignore_form_style', $options)) {
            $activedemand_ignore_form_style = $options[PREFIX.'_ignore_form_style'];
        }
        return array(
            'exclude_block_css'=>$activedemand_ignore_block_style,
            'exclude_form_css'=>$activedemand_ignore_form_style,
            'shortcodes'=> $this->get_codes(),
            PREFIX.'_session_guid' => activedemand_get_cookie_value()
        );
    }

    public function post_codes(){

        if(!$this->server_side){
            throw new \Exception('Method must be Server Side for ShortCodeCollector to POST');
        }

        $args= $this->make_args();
        $timeout=10;
        $response= activedemand_postHTML($this->url, $args, $timeout);
        $this->has_fired=TRUE;
        $this->reply=$response;
        return $response;
    }
    public function get_reply(){
        if(!$this->has_fired) $this->post_codes();
        return $this->reply;
    }

    public function get_codes(){
        return json_encode((object) array('forms'=> (object) $this->forms,
                                    'popups'=> $this->show_popups,
                                    'blocks'=> (object) $this->blocks
                                    ));
    }

}

add_shortcode(PREFIX.'_block', __NAMESPACE__.'\activedemand_process_block_shortcode');

function activedemand_process_block_shortcode($atts, $content = null){

    $id = "";
    //$id exists after this call.
    extract(shortcode_atts(array('id' => ''), $atts));
    $collector= ShortCodeCollector::get_instance();

    $div_id=$collector->add_block($id);
    $html= '';
    return "<div id='$div_id'>$html</div>";
}

add_shortcode(PREFIX.'_form', __NAMESPACE__.'\activedemand_process_form_shortcode');

function activedemand_process_form_shortcode($atts, $content = null){

    $id = "";
    //$id exists after this call.
    extract(shortcode_atts(array('id' => ''), $atts));
    $collector= ShortCodeCollector::get_instance();
    $div_id=$collector->add_form($id);
    $html= '';
    return "<div id='$div_id'></div>";
}

//enqueue jQuery for popup purposes
add_action('wp_enqueue_scripts', __NAMESPACE__.'\activedemand_scripts');

function activedemand_scripts(){
    wp_enqueue_script('jquery');
}


function match_replacement($matches){
    switch($matches[1]){
        case PREFIX.'_block':
            $function=__NAMESPACE__.'\activedemand_process_block_shortcode';
            break;
        case PREFIX.'_form':
            $function=__NAMESPACE__.'\activedemand_process_form_shortcode';
            break;
        default:
            return "";
    }
    $args="array('id'=>$matches[3])";
    return "<!-- mfunc " . W3TC_DYNAMIC_SECURITY . "echo ".$function."($args) -->"
                . '<!--/mfunc ' . W3TC_DYNAMIC_SECURITY . ' -->';
}

function prefilter_content($content){
    if(!defined('W3TC_DYNAMIC_SECURITY') || !function_exists('w3tc_fragmentcache_start')){
        return $content;
    }
    else{

        $shortcodes = array(PREFIX.'_form', PREFIX.'_block');

        foreach ($shortcodes as $sc) {
            $pattern="/\[($sc).*?id=('|\")(\d+)('|\").*\]/";
            $content= preg_replace_callback($pattern, __NAMESPACE__.'\match_replacement', $content);
        }

        return $content;
    }
}

add_filter('the_content', __NAMESPACE__.'\prefilter_content',1);
//remove_filter('the_content', 'wpautop');

add_filter('widget_text', __NAMESPACE__.'\prefilter_content');



function footer_script(){
    if(!defined('W3TC_DYNAMIC_SECURITY') || !function_exists('w3tc_fragmentcache_start')){
        $process_code=process_shortcodes_script();
    } else{

        $process_code='<!--mfunc '. W3TC_DYNAMIC_SECURITY . ' echo '.__NAMESPACE__.'\process_shortcodes_script() -->'
            . '<!--/mfunc '.W3TC_DYNAMIC_SECURITY. ' -->';
    }
    echo $process_code;
}

add_action('wp_footer', __NAMESPACE__.'\footer_script', 900);


function process_shortcodes_script(){
    $collector= ShortCodeCollector::get_instance();
    $server_side=$collector->server_side;

    if(!$collector->has_content()) return;

    $script=<<<SCRIPTTOP
            <script type="text/javascript">
            function cycleAndReplace(obj){
                for(var property in obj){
                    if(!obj.hasOwnProperty(property) || property=="popup" || property=="contact_id") continue;
                    var id="#"+property;
                    jQuery(id).html(obj[property]);
                }
            }
            function prefixThePopup(popup){
                jQuery(document).ready(function(){
                    jQuery("body").prepend(popup);
                });
            }
SCRIPTTOP;

    if($server_side){
        $arr=json_decode($collector->get_reply(), TRUE);
        $json= json_encode($arr, JSON_HEX_TAG || JSON_HEX_QUOT);
        if(empty($arr)) $json='{}';
        $name=PREFIX.'_shortcodes';
        $script.=<<<SCRIPTBODY
                var $name=$json;
                cycleAndReplace($name);
                if($name.popup) prefixThePopup($name.popup);
SCRIPTBODY;
    } else{
        $script.= add_client_rider();
    }
     $script.=<<<SCRIPTEND
            </script>
SCRIPTEND;

    return $script;
}

function get_collector_content($div_id){
    $collector= ShortCodeCollector::get_instance();
    return $collector->get_content($div_id);
}

function add_client_rider(){
    $client_token = activedemand_getHTML("https://api.activedemand.com/v1/client_token", 10);

    $collector= ShortCodeCollector::get_instance();
    $args=$collector->make_args();
    $data= activedemand_field_string($args, $client_token);
    $url= $collector->url;
    $script=<<<SCRIPT
            jQuery(document).ready(function(){
                var data='$data';
                try {
                    if (typeof AD != 'undefined') {
						AD.session();
                        data = data + '&activedemand_session_guid=' + AD.jQuery.cookie('activedemand_session_guid');
                    }
                } catch(e) {}
                jQuery.post('$url', data, function(response){
                    var obj=response;
                    if(!obj) return;
                    cycleAndReplace(obj);
                    if(obj.popup) prefixThePopup(obj.popup);
                    if(obj.contact_id && typeof AD != 'undefined') AD.contact_id = obj.contact_id;
                    if(typeof AD != 'undefined' && AD.setup_forms) AD.setup_forms();
                    if(typeof AD != 'undefined' && AD.setup_forms) AD.setup_ad_paging();
                });
            });
SCRIPT;
    return $script;
}
