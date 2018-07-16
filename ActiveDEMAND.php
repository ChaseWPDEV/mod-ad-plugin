<?php

/**
 * Plugin Name: ActiveDEMAND
 * Plugin URI: https://www2.activedemand.com/s/Gnf5n
 * Description: Adds the <a href="https://www2.activedemand.com/s/SW5nU">ActiveDEMAND</a> tracking script to your website. Add custom popups, use shortcodes to embed webforms and dynamic website content.
 * Version: 0.1.66
 * Author: JumpDEMAND Inc.
 * Author URI: https://www2.activedemand.com/s/SW5nU
 * License:GPL-2.0+
 * License URI:http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace ActiveDemand;


define(__NAMESPACE__.'\ACTIVEDEMAND_VER', '0.1.66');
define(__NAMESPACE__."\PLUGIN_VENDOR", "ActiveDEMAND");
define(__NAMESPACE__."\PLUGIN_VENDOR_LINK", "http://1jp.cc/s/SW5nU");
define(__NAMESPACE__."\PREFIX", 'activedemand');

include 'class-SCCollector.php';

//--------------- AD update path --------------------------------------------------------------------------
function activedemand_update()
{

    //get ensure a cookie is set. This call creates a cookie if one does not exist
    activedemand_get_cookie_value();

    $key = PREFIX.'_version';
    $version = get_option($key);

    if (ACTIVEDEMAND_VER === $version) return;
    activedemand_plugin_activation();
    update_option($key, ACTIVEDEMAND_VER);


}

add_action('init', __NAMESPACE__.'\activedemand_update');

//---------------Version Warning---------------------------//
/**function phpversion_warning_notice(){
    if(!((int)phpversion()<7)) return;
    $class='notice notice-warning is-dismissible';

    $message=(__(PLUGIN_VENDOR.' will deprecate PHP5 support soon -- we recommend updating to PHP7.'));
    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
}
add_action('admin_notices', __NAMESPACE__.'\phpversion_warning_notice');
*/
//--------------- AD Server calls -------------------------------------------------------------------------

function activedemand_getHTML($url, $timeout, $args = array())
{

    $fields_string = activedemand_field_string($args);

    if (in_array('curl', get_loaded_extensions())) {
        $ch = curl_init($url . "?" . $fields_string);  // initialize curl with given url
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]); // set  useragent
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // write the response to a variable
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // follow redirects if any
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); // max. seconds to execute
        curl_setopt($ch, CURLOPT_FAILONERROR, 1); // stop when it encounters an error
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);//force IP4
        $result = curl_exec($ch);
        curl_close($ch);
    } elseif (function_exists('file_get_contents')) {
        $result = file_get_contents($url);
    }

    return $result;
}

function activedemand_postHTML($url, $args, $timeout)
{
    $fields_string = activedemand_field_string($args);

    if (in_array('curl', get_loaded_extensions())) {
        $ch = curl_init($url); // initialize curl with given url
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]); // set  useragent
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // write the response to a variable
        // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // follow redirects if any
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); // max. seconds to execute
        curl_setopt($ch, CURLOPT_FAILONERROR, 1); // stop when it encounters an error
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);//force IP4
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        $result = curl_exec($ch);
        if ($result === false) {
            error_log(PLUGIN_VENDOR.' Web Form error: ' . curl_error($ch));
        }

        curl_close($ch);
    }


    return $result;
}

/**
 * Adds ActiveDEMAND popups if API Key isset and activedemand_server_showpopups is true
 *
 * @param string $content
 * @return string $content with popup prefix
 */


function activedemand_field_string($args, $api_key = '')
{

    $options = get_option(PREFIX.'_options_field');
    $fields_string = "";
    if (is_array($options) && array_key_exists(PREFIX.'_appkey', $options)) {
        $activedemand_appkey = $options[PREFIX."_appkey"];
    } else {
        $activedemand_appkey = "";
    }

    if ("" != $api_key) {
        $activedemand_appkey = $api_key;
    }

    if ("" != $activedemand_appkey) {

        $cookievalue = activedemand_get_cookie_value();
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        if (isset($_SERVER['HTTP_REFERER'])) {
            $referrer = $_SERVER['HTTP_REFERER'];
        } else {
            $referrer = "";
        }
        if ($cookievalue != "") {
            $fields = array(
                'api-key' => $activedemand_appkey,
                'activedemand_session_guid' => activedemand_get_cookie_value(),
                'url' => $url,
                'ip_address' => activedemand_get_ip_address(),
                'referer' => $referrer
            );
        } else {
            $fields = array(
                'api-key' => $activedemand_appkey,
                'url' => $url,
                'ip_address' => activedemand_get_ip_address(),
                'referer' => $referrer
            );

        }
        if (is_array($args)) {
            $fields = array_merge($fields, $args);
        }
        $fields_string = http_build_query($fields);
    }

    return $fields_string;
}

add_action('init', __NAMESPACE__.'\activedemand_get_cookie_value');

function activedemand_get_cookie_value()
{
    if (is_admin()) return "";
    
    static $cookieValue = ""; 
        
    if(!empty($cookieValue)) return $cookieValue;
        //not editing an options page etc.

        if (!empty($_COOKIE['activedemand_session_guid'])) {
            $cookieValue = $_COOKIE['activedemand_session_guid'];
            
        } else {
            $server_side = get_option(PREFIX.'_server_side', TRUE);;
            if($server_side){
                $urlParms = $_SERVER['HTTP_HOST'];
                if (NULL != $urlParms) {
                        $cookieValue = activedemand_get_GUID();
                        $basedomain = activedemand_get_basedomain();
                        setcookie('activedemand_session_guid', $cookieValue, time() + (60 * 60 * 24 * 365 * 10), "/", $basedomain);
                }
            }
        }
    
    return $cookieValue;
}


function activedemand_get_basedomain()
{
    $result = "";

    $urlParms = $_SERVER['HTTP_HOST'];
    if (NULL != $urlParms) {
        $result = str_replace('www.', "", $urlParms);
    }
    return $result;
}

// create a session if one doesn't exist
function activedemand_get_GUID()
{
    if (function_exists('com_create_guid')) {
        return com_create_guid();
    } else {
        mt_srand((double)microtime() * 10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12);
        return $uuid;
    }
}


// get the ip address
function activedemand_get_ip_address()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

//--------------- Admin Menu -------------------------------------------------------------------------
function activedemand_menu()
{
    global $activedemand_plugin_hook;
    $activedemand_plugin_hook = add_options_page(PLUGIN_VENDOR.' options', PLUGIN_VENDOR, 'manage_options', PREFIX.'_options', __NAMESPACE__.'\activedemand_plugin_options');
    add_action('admin_init', __NAMESPACE__.'\register_activedemand_settings');

}

function register_activedemand_settings()
{
    register_setting(PREFIX.'_options', PREFIX.'_options_field');
    register_setting(PREFIX.'_options', PREFIX.'_server_showpopups');
    register_setting(PREFIX.'_options', PREFIX.'_show_tinymce');
    register_setting(PREFIX.'_options', PREFIX.'_server_side');
}


function activedemand_enqueue_scripts()
{
    wp_enqueue_script('ActiveDEMAND-Track', 'https://static.activedemand.com/public/javascript/ad.collect.min.js.jgz');
}


function activedemand_admin_enqueue_scripts()
{
    global $pagenow;

    if ('post.php' == $pagenow || 'post-new.php' == $pagenow) {
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');

    }
}

function activedemand_plugin_action_links($links, $file)
{
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page='.PREFIX.'_options">Settings</a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}

function activedemand_stale_cart_form($form_xml = NULL)
{
    if (!isset($form_xml)) {
        $url = "https://api.activedemand.com/v1/forms.xml";
        $str = activedemand_getHTML($url, 10);
        $form_xml = simplexml_load_string($str);
    }
    $options = get_option(PREFIX.'_options_field');
    $activedemand_form_id = isset($options[PREFIX."_woocommerce_stalecart_form_id"]) ?
        $options[PREFIX."_woocommerce_stalecart_form_id"] : 0;
    $hours = isset($options['woocommerce_stalecart_hours']) ? $options['woocommerce_stalecart_hours'] : 2;

    ?>
    <tr valign="top">
    <th scope="row">WooCommerce Carts:</th>
    <td><?php
        echo "<select name=\"".PREFIX."_options_field[".PREFIX."_woocommerce_stalecart_form_id]\">";
        echo "<option value='0'";
        if (0 == $activedemand_form_id) echo "selected='selected'";
        echo ">Do Nothing</option>";
        foreach ($form_xml->children() as $child) {
            echo "<option value='";
            echo $child->id;
            echo "'";
            if ($child->id == $activedemand_form_id) echo "selected='selected'";
            echo ">Submit To Form: ";
            echo $child->name;
            echo "</option>";
        }
        echo "</select>";

        ?>
        <div style="font-size: small;"><strong>Note:</strong> The selected <?php echo PLUGIN_VENDOR?> Form must
            have <strong>[First
                Name]</strong>-<strong>[Last Name]</strong>-<strong>[Email
                Address*]</strong>-<strong>[Product Data]</strong>
            as the first 4 fields.
            Ensure that the [Product Data] field is a text area.
        </div>
        <br/>

        Send Stale carts to <?php echo PLUGIN_VENDOR?> after it has sat for:<br>
        <input type="number" min="1"
               name=PREFIX."_options_field[woocommerce_stalecart_hours]"
               value="<?php echo $hours; ?>"> hours
    </td>
    <?php

}

function activedemand_plugin_options()
{
    $woo_commerce_installed = false;

    $options = is_array(get_option(PREFIX.'_options_field'))? get_option(PREFIX.'_options_field') : array();
    $form_xml = "";


    if (!array_key_exists(PREFIX.'_appkey', $options)) {
        $options[PREFIX.'_appkey'] = "";
    }

    $activedemand_appkey = $options[PREFIX.'_appkey'];

    if (!array_key_exists(PREFIX.'_ignore_form_style', $options)) {
        $options[PREFIX.'_ignore_form_style'] = 0;
    }
    if (!array_key_exists(PREFIX.'_ignore_block_style', $options)) {
        $options[PREFIX.'_ignore_block_style'] = 0;
    }


    if (array_key_exists(PREFIX.'_woo_commerce_order_form_id', $options)) {
        $activedemand_woo_commerce_order_form_id = $options[PREFIX."_woo_commerce_order_form_id"];

    } else {
        $activedemand_woo_commerce_order_form_id = 0;
    }

    if (class_exists('woocommerce')) {

        $woo_commerce_installed = true;
    }
    if (array_key_exists(PREFIX.'_woo_commerce_use_status', $options)) {


        $activedemand_woo_commerce_use_status = $options[PREFIX."_woo_commerce_use_status"];
        if (!array_key_exists("pending", $activedemand_woo_commerce_use_status)) {
            $activedemand_woo_commerce_use_status["pending"] = FALSE;
        }
        if (!array_key_exists("processing", $activedemand_woo_commerce_use_status)) {
            $activedemand_woo_commerce_use_status["processing"] = FALSE;
        }
        if (!array_key_exists("on-hold", $activedemand_woo_commerce_use_status)) {
            $activedemand_woo_commerce_use_status["on-hold"] = FALSE;
        }
        if (!array_key_exists("completed", $activedemand_woo_commerce_use_status)) {
            $activedemand_woo_commerce_use_status["completed"] = FALSE;
        }
        if (!array_key_exists("refunded", $activedemand_woo_commerce_use_status)) {
            $activedemand_woo_commerce_use_status["refunded"] = FALSE;
        }
        if (!array_key_exists("cancelled", $activedemand_woo_commerce_use_status)) {
            $activedemand_woo_commerce_use_status["cancelled"] = FALSE;
        }
        if (!array_key_exists("failed", $activedemand_woo_commerce_use_status)) {
            $activedemand_woo_commerce_use_status["failed"] = FALSE;
        }


    } else {

        $activedemand_woo_commerce_use_status = array(
            "pending" => FALSE,
            "processing" => FALSE,
            "on-hold" => FALSE,
            "completed" => TRUE,
            "refunded" => FALSE,
            "cancelled" => FALSE,
            "failed" => FALSE

        );

        $options[PREFIX."_woo_commerce_use_status"] = $activedemand_woo_commerce_use_status;
    }
    update_option(PREFIX.'_options_field', $options);

    ?>


    <div class="wrap">
        <img src="<?php echo get_base_url() ?>/images/ActiveDEMAND-Transparent.png"/>

        <h1>Settings</h1>
        <?php if ("" == $activedemand_appkey || !isset($activedemand_appkey)) { ?>
            <h2>Your <?php echo PLUGIN_VENDOR?> Account</h2><br/>
            You will require an <a href="<?php echo PLUGIN_VENDOR_LINK?>"><?php echo PLUGIN_VENDOR?></a> account to use this plugin. With an
            <?php echo PLUGIN_VENDOR?> account you will be able
                                                                                 to:<br/>
            <ul style="list-style-type:circle;  margin-left: 50px;">
                <li>Build Webforms for your pages, posts, sidebars, etc</li>
                <li>Build Dynamic Content Blocks for your pages, posts, sidebars, etc</li>
                <ul style="list-style-type:square;  margin-left: 50px;">
                    <li>Dynamically swap content based on GEO-IP data</li>
                    <li>Automatically change banners based on campaign duration</li>
                    <li>Stop showing forms to people who have already subscribed</li>
                </ul>
                <li>Deploy Popups and Subscriber bars</li>
                <li>Automatically send emails to those who fill out your web forms</li>
                <li>Automatically send emails to you when a form is filled out</li>
                <li>Send email campaigns to your subscribers</li>
                <li>Build your individual blog posts and have them automatically be posted on a schedule</li>
                <li>Bulk import blog posts and have them post on a defined set of times and days</li>
            </ul>

            <div>
                <h3>To sign up for your <?php echo PLUGIN_VENDOR?> account, click <a
                            href="<?php echo PLUGIN_VENDOR_LINK?>"><strong>here</strong></a>
                </h3>

                <p>
                    You will need to enter your application key in order to enable the form shortcodes. Your can find
                    your
                    <?php echo PLUGIN_VENDOR?> API key in your account settings:

                </p>

                <p>
                    <img src="<?php echo get_base_url() ?>/images/Screenshot2.png"/>
                </p>
            </div>
        <?php } ?>
        <form method="post" action="options.php">
            <?php
            wp_nonce_field('update-options');
            settings_fields(PREFIX.'_options');
            ?>

            <h3><?php echo PLUGIN_VENDOR?> Plugin Options</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php echo PLUGIN_VENDOR?> API Key</th>
                    <td><input style="width:400px" type='text' name=<?php echo "\"".PREFIX."_options_field[".PREFIX."_appkey]\"";?>
                               value="<?php echo $activedemand_appkey; ?>"/></td>
                </tr>
                <?php if ("" != $activedemand_appkey) {
                    //get Forms
                    $url = "https://api.activedemand.com/v1/forms.xml";
                    $str = activedemand_getHTML($url, 10);
                    $form_xml = simplexml_load_string($str);



                    $show_popup = get_option(PREFIX.'_server_showpopups', FALSE);
                    $show_tinymce = get_option(PREFIX.'_show_tinymce', TRUE);
                    ?>
                    <tr valign="top">
                        <th scope="row">Enable Popup Pre-Loading?</th>
                        <td><input type="checkbox" name=<?php echo PREFIX."_server_showpopups"; ?> value="1"
                                <?php checked($show_popup, 1); ?> /></td>
                    </tr>
                    <?php  $server_side = get_option(PREFIX.'_server_side', TRUE); ?>
                    <tr valign="top">
                        <th scope="row">Enable Content Pre-Loading? (uncheck this if you use caching)</th>
                        <td><input type="checkbox" name=<?php echo PREFIX."_server_side"; ?> value="1"
                                <?php checked($server_side, 1); ?> /></td>
                    </tr>
                    <tr>
                        <th>
                            <scope
                            ="row">Show <?php echo PLUGIN_VENDOR?> Button on Post/Page editors?
                        </th>
                        <td><input type="checkbox" name=<?php echo PREFIX."_show_tinymce";?> value="1"
                                <?php checked($show_tinymce, 1) ?>/></td>
                    </tr>

                <?php } ?>

                <?php if ("" != $form_xml) { ?>
                    <tr valign="top">
                        <th scope="row">Use Theme CSS for <?php echo PLUGIN_VENDOR?> Forms</th>
                        <td>
                            <input type="checkbox" name=<?php echo PREFIX."_options_field[".PREFIX."_ignore_form_style]";?>
                                   value="1" <?php checked($options[PREFIX.'_ignore_form_style'], 1); ?> />
                        </td>
                    </tr>

                <?php } ?>

                <?php
                //get Blocks
                $url = "https://api.activedemand.com/v1/smart_blocks.xml";
                $str = activedemand_getHTML($url, 10);
                $block_xml = simplexml_load_string($str);

                if ("" != $block_xml) { ?>
                    <tr valign="top">
                        <th scope="row">Use Theme CSS for <?php echo PLUGIN_VENDOR?> Dynamic Blocks</th>
                        <td>
                            <input type="checkbox" name=<?php echo PREFIX."_options_field[".PREFIX."_ignore_block_style]"; ?>
                                   value="1" <?php checked($options[PREFIX.'_ignore_block_style'], 1); ?> />
                        </td>
                    </tr>

                <?php } ?>



                <?php
                if ($woo_commerce_installed && "" != $form_xml) {
                ?>
                <tr valign="top">
                    <th scope="row">On WooCommerce Order:</th>
                    <td><?php
                        echo "<select name=\"".PREFIX."_options_field[".PREFIX."_woo_commerce_order_form_id]\">";
                        echo "<option value='0'";
                        if ("0" == $activedemand_woo_commerce_order_form_id) echo "selected='selected'";
                        echo ">Do Nothing</option>";
                        foreach ($form_xml->children() as $child) {
                            echo "<option value='";
                            echo $child->id;
                            echo "'";
                            if ($child->id == $activedemand_woo_commerce_order_form_id) echo "selected='selected'";
                            echo ">Submit To Form: ";
                            echo $child->name;
                            echo "</option>";
                        }
                        echo "</select>";

                        if (0 != $activedemand_woo_commerce_order_form_id) {
                            ?>
                            <div style="font-size: small;"><strong>Note:</strong> The selected <?php echo PLUGIN_VENDOR?> Form must
                                have <strong>[First
                                    Name]</strong>-<strong>[Last Name]</strong>-<strong>[Email
                                    Address*]</strong>-<strong>[Order
                                    Value]</strong>-<strong>[Order State Change]</strong>-<strong>[Order ID]</strong> as
                                the
                                first 6 fields. Ensure that only the [Email Address*] field is required.
                            </div>
                            <br/>
                            Submit Forms to <?php echo PLUGIN_VENDOR?> when an WooCommerce order status changes to:
                            <style type="text/css">
                                table.wootbl th {

                                    padding: 5px;
                                }

                                table.wootbl td {

                                    padding: 5px;
                                }
                            </style>
                            <table class="wootbl" style="margin-left: 25px">
                                <tr>
                                    <th>Pending</th>
                                    <td><input type="checkbox"
                                               name=<?php echo PREFIX."_options_field[".PREFIX."_woo_commerce_use_status][pending]"; ?>
                                               value="1" <?php checked($activedemand_woo_commerce_use_status['pending'], 1); ?> />
                                    </td>
                                </tr>
                                <tr>
                                    <th>Processing</th>
                                    <td><input type="checkbox"
                                               name=<?php echo PREFIX."_options_field[".PREFIX."_woo_commerce_use_status][processing]"; ?>
                                               value="1" <?php checked($activedemand_woo_commerce_use_status['processing'], 1); ?> />
                                    </td>
                                </tr>
                                <tr>
                                    <th>On Hold</th>
                                    <td><input type="checkbox"
                                               name=<?php echo PREFIX."_options_field[".PREFIX."_woo_commerce_use_status][on-hold]";?>
                                               value="1" <?php checked($activedemand_woo_commerce_use_status['on-hold'], 1); ?> />
                                    </td>
                                </tr>
                                <tr>
                                    <th>Completed</th>
                                    <td><input type="checkbox"
                                               name=<?php echo PREFIX."_options_field[".PREFIX."_woo_commerce_use_status][completed]";?>
                                               value="1" <?php checked($activedemand_woo_commerce_use_status['completed'], 1); ?> />
                                    </td>
                                </tr>
                                <tr>
                                    <th>Refunded</th>
                                    <td><input type="checkbox"
                                               name=<?php echo PREFIX."_options_field[".PREFIX."_woo_commerce_use_status][refunded]";?>
                                               value="1" <?php checked($activedemand_woo_commerce_use_status['refunded'], 1); ?> />
                                    </td>
                                </tr>
                                <tr>
                                    <th>Cancelled</th>
                                    <td><input type="checkbox"
                                               name=<?php echo PREFIX."_options_field[".PREFIX."_woo_commerce_use_status][cancelled]";?>
                                               value="1" <?php checked($activedemand_woo_commerce_use_status['cancelled'], 1); ?> />
                                    </td>
                                </tr>
                                <tr>
                                    <th>Failed</th>
                                    <td><input type="checkbox"
                                               name=<?php echo PREFIX."_options_field[".PREFIX."_woo_commerce_use_status][failed]";?>
                                               value="1" <?php checked($activedemand_woo_commerce_use_status['failed'], 1); ?> />
                                    </td>
                                </tr>

                            </table>
                        <?php } ?>
                    </td>
                    <?php
                    activedemand_stale_cart_form($form_xml);
                    } ?>

                </tr>
                <tr>
                    <td></td>
                    <td>
                        <p class="submit">
                            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>"/>
                        </p>
                    </td>
                </tr>
            </table>
        </form>

        <?php if ("" != $activedemand_appkey) { ?>
            <div>

                <h2>Using <?php echo PLUGIN_VENDOR?> Web Forms and Dynamic Content Blocks</h2>

                <p> The <a href="<?php echo PLUGIN_VENDOR_LINK?>"><?php echo PLUGIN_VENDOR?></a> plugin adds a
                    tracking script to your
                    WordPress
                    pages. This plugin offers the ability to use web form and content block shortcodes on your pages,
                    posts, and
                    sidebars
                    that
                    will render an <?php echo PLUGIN_VENDOR?> Web Form/Dynamic Content block. This allows you to maintain your dynamic
                    content, form styling, and
                    configuration
                    within
                    <?php echo PLUGIN_VENDOR?>.
                </p>

                <p>
                    In your visual editor, look for the 'Insert <?php echo PLUGIN_VENDOR?> Shortcode' button:<br/>
                    <img
                            src="<?php echo get_base_url() ?>/images/Screenshot3.png"/>.
                </p>
                <?php if (!in_array('curl', get_loaded_extensions()))
                {
                    echo"<br/><h2>WARNING: cURL Was Not Detected</h2><p>To use ActiveDEMAND shortcodes on this site, cURL <strong>must</strong> be installed on the webserver. It looks like <strong>cURL is not installed on this webserver</strong>. If you have questions, please contact support@ActiveDEMAND.com.</p>";

                }?>

                <table>
                    <tr>
                        <td style="padding:15px;vertical-align: top;">
                            <?php if ("" != $form_xml) { ?>
                                <h3>Available Web Form Shortcodes</h3>

                                <style scoped="scoped" type="text/css">
                                    table#shrtcodetbl {
                                        border: 1px solid black;
                                    }

                                    table#shrtcodetbl tr {
                                        background-color: #ffffff;
                                    }

                                    table#shrtcodetbl tr:nth-child(even) {
                                        background-color: #eeeeee;
                                    }

                                    table#shrtcodetbl tr td {
                                        padding: 10px;

                                    }

                                    table#shrtcodetbl th {
                                        color: white;
                                        background-color: black;
                                        padding: 10px;
                                    }
                                </style>
                                <table id="shrtcodetbl" style="width:100%">
                                    <tr>
                                        <th>Form Name</th>
                                        <th>Shortcode</th>
                                    </tr>
                                    <?php
                                    foreach ($form_xml->children() as $child) {
                                        echo "<tr><td>";
                                        echo $child->name;
                                        echo "</td>";
                                        echo "<td>[".PREFIX."_form id='";
                                        echo $child->id;
                                        echo "']</td>";
                                    }
                                    ?>
                                </table>


                            <?php } else { ?>
                                <h2>No Web Forms Configured</h2>
                                <p>To use the <?php echo PLUGIN_VENDOR?> web form shortcodes, you will first have to add some Web
                                    Forms
                                    to
                                    your
                                    account in <?php echo PLUGIN_VENDOR?>. Once you do have Web Forms configured, the available
                                    shortcodes
                                    will
                                    be
                                    displayed here.</p>

                            <?php } ?>
                        </td>
                        <td style="padding:15px;vertical-align: top;">
                            <?php if ("" != $block_xml) { ?>
                                <h3>Available Dynamic Content Block Shortcodes</h3>

                                <style scoped="scoped" type="text/css">
                                    table#shrtcodetbl {
                                        border: 1px solid black;
                                    }

                                    table#shrtcodetbl tr {
                                        background-color: #ffffff;
                                    }

                                    table#shrtcodetbl tr:nth-child(even) {
                                        background-color: #eeeeee;
                                    }

                                    table#shrtcodetbl tr td {
                                        padding: 10px;

                                    }

                                    table#shrtcodetbl th {
                                        color: white;
                                        background-color: black;
                                        padding: 10px;
                                    }
                                </style>
                                <table id="shrtcodetbl" style="width:100%">
                                    <tr>
                                        <th>Block Name</th>
                                        <th>Shortcode</th>
                                    </tr>
                                    <?php
                                    foreach ($block_xml->children() as $child) {
                                        echo "<tr><td>";
                                        echo $child->name;
                                        echo "</td>";
                                        echo "<td>[".PREFIX."_block id='";
                                        echo $child->id;
                                        echo "']</td>";
                                    }
                                    ?>
                                </table>


                            <?php } else { ?>
                                <h2>No Dynamic Blocks Configured</h2>
                                <p>To use the <?php echo PLUGIN_VENDOR?> Dynamic Content Block shortcodes, you will first have to add
                                    some Dynamic Content Blocks
                                    to
                                    your
                                    account in <?php echo PLUGIN_VENDOR?>. Once you do have Dynamic Blocks configured, the available
                                    shortcodes
                                    will
                                    be
                                    displayed here.</p>

                            <?php } ?>
                        </td>
                    </tr>
                </table>
            </div>
        <?php } ?>
    </div>
    <?php
}


function get_base_url()
{
    return plugins_url(null, __FILE__);
}

function activedemand_register_tinymce_javascript($plugin_array)
{
    $plugin_array['activedemand'] = plugins_url('/js/tinymce-plugin.js', __FILE__);
    return $plugin_array;
}


function activedemand_buttons()
{
    add_filter("mce_external_plugins", __NAMESPACE__.'\activedemand_add_buttons');
    add_filter('mce_buttons', __NAMESPACE__.'\activedemand_register_buttons');
}

function activedemand_add_buttons($plugin_array)
{
    $plugin_array['activedemand'] = get_base_url() . '/includes/activedemand-plugin.js';
    return $plugin_array;
}

function activedemand_register_buttons($buttons)
{
    array_push($buttons, 'insert_form_shortcode');
    return $buttons;
}


function activedemand_add_editor()
{
    global $pagenow;

    // Add html for shortcodes popup
    if ('post.php' == $pagenow || 'post-new.php' == $pagenow) {
        echo "Including Micey!";
        include 'partials/tinymce-editor.php';
    }

}

function activedemand_clean_url($url)
{


    if (TRUE == strpos($url, 'ad.collect.min.js.jgz'))
    {
        return "$url' async defer";
    }

    return $url;

}

//Constant used to track stale carts
define(__NAMESPACE__.'\AD_CARTTIMEKEY', 'ad_last_cart_update');

/**
 * Adds cart timestamp to usermeta
 */
function activedemand_woocommerce_cart_update()
{
    $user_id = get_current_user_id();
    update_user_meta($user_id, AD_CARTTIMEKEY, time());
}

add_action('woocommerce_cart_updated', __NAMESPACE__.'\activedemand_woocommerce_cart_update');

/**
 * Deletes timestamp from current user meta
 */
function activedemand_woocommerce_cart_emptied()
{
    $user_id = get_current_user_id();
    delete_user_meta($user_id, AD_CARTTIMEKEY);
}

add_action('woocommerce_cart_emptied', __NAMESPACE__.'\activedemand_woocommerce_cart_emptied');

/**Periodically scans, and sends stale carts to activedemand
 *
 * @global object $wpdb
 *
 * @uses activedemand_send_stale_carts function to process and send
 */

function activedemand_woocommerce_scan_stale_carts()
{
    if(!class_exists('WooCommerce')) return;
    
    global $wpdb;
    $options = get_option(PREFIX.'_options_field');
    $hours = $options['woocommerce_stalecart_hours'];

    $stale_secs = $hours * 60 * 60;

    $carts = $wpdb->get_results('SELECT * FROM ' . $wpdb->usermeta . ' WHERE meta_key=' . AD_CARTTIMEKEY);

    $stale_carts = array();
    $i = 0;
    foreach ($carts as $cart) {
        if ((time() - (int)$cart->meta_value) > $stale_secs) {
            $stale_carts[$i]['user_id'] = $cart->user_id;
            $stale_carts[$i]['cart'] = get_user_meta($cart->user_id, '_woocommerce_persistent_cart', TRUE);
        }
    }
    activedemand_send_stale_carts($stale_carts);
}

add_action(PREFIX.'_hourly', __NAMESPACE__.'\activedemand_woocommerce_scan_stale_carts');

register_activation_hook(__FILE__, __NAMESPACE__.'\activedemand_plugin_activation');

function activedemand_plugin_activation()
{
    if (!wp_next_scheduled(PREFIX.'_hourly')) wp_schedule_event(time(), 'hourly', PREFIX.'_hourly');
}

register_deactivation_hook(__FILE__, __NAMESPACE__.'\activedemand_plugin_deactivation');

function activedemand_plugin_deactivation()
{
    wp_clear_scheduled_hook(__NAMESPACE__.'\\'.PREFIX.'_hourly');
    wp_clear_scheduled_hook(PREFIX.'_hourly');
}

/**Processes and send stale carts
 * Delete the timestamp so carts are only used once
 *
 * @param array $stale_carts
 *
 * @used-by activedemand_woocommerce_scan_stale_carts
 * @uses    function _activedemand_send_stale cart to send each cart individually
 */
function activedemand_send_stale_carts($stale_carts)
{
    foreach ($stale_carts as $cart) {
        $form_data = array();
        $user = new \WP_User($cart['user_id']);

        $form_data['first_name'] = $user->user_firstname;
        $form_data['last_name'] = $user->user_lastname;
        $form_data['email_address'] = $user->user_email;

        $products = $cart['cart']['cart'];
        $form_data['product_data'] = '';

        foreach ($products as $product) {
            $product_name = get_the_title($product['product_id']);
            $form_data['product_data'] .= "Product Name: $product_name \n"
                . "Product price: " . $product['price'] . '\n'
                . 'Product Qty: ' . $product['quantity'] . '\n'
                . 'Total: ' . $product['line_total'] . '\n\n';
        }
        _activedemand_send_stale_cart($form_data);
        delete_user_meta($user->ID, AD_CARTTIMEKEY);
    }
}

/**Sends individual carts to activedemand form
 *
 * @param array $form_data
 */
function _activedemand_send_stale_cart($form_data)
{
    $options = get_option(PREFIX.'_options_field');
    $activedemand_form_id = $options[PREFIX."_woocommerce_stalecart_form_id"];
    if(!($activedemand_form_id)) return;
    
    $form_str = activedemand_getHTML("https://api.activedemand.com/v1/forms/fields.xml", 10, array('form_id' => $activedemand_form_id));
    $form_xml = simplexml_load_string($form_str);


    if ($form_xml->children()->count() >= 4) {
        $fields = array();
        $i = 0;
        foreach ($form_xml->children() as $child) {

            if (!array_key_exists(urlencode($child->key), $fields)) {
                $fields[urlencode($child->key)] = array();
            }

            switch ($i) {
                case 0:
                    array_push($fields[urlencode($child->key)], $form_data['first_name']);
                    break;
                case 1:
                    array_push($fields[urlencode($child->key)], $form_data['last_name']);
                    break;
                case 2:
                    array_push($fields[urlencode($child->key)], $form_data['email_address']);
                    break;
                case 3:
                    array_push($fields[urlencode($child->key)], $form_data['product_data']);
                    break;
            }

            $i++;
        }
        activedemand_postHTML("https://api.activedemand.com/v1/forms/" . $activedemand_form_id, $fields, 5);
    }
}

function activedemand_woocommerce_order_status_changed($order_id, $order_status_old, $order_status_new)
{
    //post that this person has reviewed their account page.

    $options = get_option(PREFIX.'_options_field');
    if (array_key_exists(PREFIX.'_appkey', $options)) {
        $activedemand_appkey = $options[PREFIX."_appkey"];
    }

    if (array_key_exists(PREFIX.'_woo_commerce_use_status', $options)) {
        $activedemand_woo_commerce_use_status = $options[PREFIX."_woo_commerce_use_status"];
    } else {
        $activedemand_woo_commerce_use_status = array('none' => 'none');
    }

    if (array_key_exists(PREFIX.'_woo_commerce_order_form_id', $options)) {
        $activedemand_woo_commerce_order_form_id = $options[PREFIX."_woo_commerce_order_form_id"];

    } else {
        $activedemand_woo_commerce_order_form_id = "0";
    }

    $execute_form_submit = ("" != $activedemand_appkey) && ("0" != $activedemand_woo_commerce_order_form_id) && ("" != $activedemand_woo_commerce_order_form_id) && array_key_exists($order_status_new, $activedemand_woo_commerce_use_status);
    if ($execute_form_submit) {
        $execute_form_submit = $activedemand_woo_commerce_use_status[$order_status_new];
    }


    //we need an email address and a form ID
    if ($execute_form_submit) {
        $order = new \WC_Order($order_id);
        $user_id = (int)$order->get_user_id();

        if (0 == $user_id) {
            $first_name = $order->billing_first_name;
            $last_name = $order->billing_last_name;
            $email_address = $order->billing_email;

        } else {
            $guest = FALSE;

            $current_user = get_userdata($user_id);
            $first_name = $current_user->user_firstname;
            $last_name = $current_user->user_lastname;
            $email_address = $current_user->user_email;

        }


        if (("" != $email_address) && ('0' != $activedemand_woo_commerce_order_form_id)) {

            $form_str = $form_str = activedemand_getHTML("https://api.activedemand.com/v1/forms/fields.xml", 10, array('form_id' => $activedemand_woo_commerce_order_form_id));
            $form_xml = simplexml_load_string($form_str);


            if ("" != $form_xml) {

                if ($form_xml->children()->count() >= 6) {
                    $fields = array();
                    $i = 0;
                    foreach ($form_xml->children() as $child) {

                        if (!array_key_exists(urlencode($child->key), $fields)) {
                            $fields[urlencode($child->key)] = array();
                        }
                        switch ($i) {
                            case 0:
                                array_push($fields[urlencode($child->key)], $first_name);
                                break;
                            case 1:
                                array_push($fields[urlencode($child->key)], $last_name);
                                break;
                            case 2:
                                array_push($fields[urlencode($child->key)], $email_address);
                                break;
                            case 3:
                                array_push($fields[urlencode($child->key)], $order->get_total());
                                break;
                            case 4:
                                array_push($fields[urlencode($child->key)], $order_status_new);
                                break;
                            case 5:
                                array_push($fields[urlencode($child->key)], $order_id);
                                break;
                        }

                        $i++;


                    }


                    activedemand_postHTML("https://api.activedemand.com/v1/forms/" . $activedemand_woo_commerce_order_form_id, $fields, 5);

                }
            } else {
//                error_log("no form fields");
            }


            //$order_status_new;


        }


    } else {
        //      error_log("Not Processing ADForm Submit");
    }//execute form submit


}


if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {


    $options = get_option(PREFIX.'_options_field');

    //check to see if we have an API key, if we do not, zero integration is possible

    $activedemand_appkey = "";

    if (is_array($options) && array_key_exists(PREFIX.'_appkey', $options)) {
        $activedemand_appkey = $options[PREFIX."_appkey"];

    }


    if ("" != $activedemand_appkey) {

        add_action('woocommerce_order_status_changed', __NAMESPACE__.'\activedemand_woocommerce_order_status_changed', 10, 3);
    }

}


//defer our script loading
add_filter('clean_url', __NAMESPACE__.'\activedemand_clean_url', 11, 1);
add_action('wp_enqueue_scripts', __NAMESPACE__.'\activedemand_enqueue_scripts');

add_action('admin_enqueue_scripts', __NAMESPACE__.'\activedemand_admin_enqueue_scripts');

add_action('admin_menu', __NAMESPACE__.'\activedemand_menu');
add_filter('plugin_action_links', __NAMESPACE__.'\activedemand_plugin_action_links', 10, 2);


//widgets
// add new buttons

if (get_option(PREFIX.'_show_tinymce', TRUE)) {
    add_action('init', __NAMESPACE__.'\activedemand_buttons');
    add_action('in_admin_footer', __NAMESPACE__.'\activedemand_add_editor');
}


/*
 * Include module for Landing Page delivery
 */

include('landing-pages.php');
                    