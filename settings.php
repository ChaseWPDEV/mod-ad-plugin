<?php



namespace ActiveDemand;


function activedemand_stale_cart_form($options)
{
    $options = retrieve_activedemand_options();
    $activedemand_form_id = isset($options[PREFIX."_woocommerce_stalecart_form_id"]) ?
    $options[PREFIX."_woocommerce_stalecart_form_id"] : 0;
    $hours = isset($options['woocommerce_stalecart_hours']) ? $options['woocommerce_stalecart_hours'] : 2;

    ?>
    <tr valign="top">
    <th scope="row">WooCommerce Carts:</th>
    <td><?php
        echo FormLinker::form_list_dropdown(
          PREFIX."_woocommerce_options_field[".PREFIX."_woocommerce_stalecart_form_id]",
          [],
          $activedemand_form_id
        );

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
               name="<?php echo PREFIX?>_woocommerce_options_field[woocommerce_stalecart_hours]"
               value="<?php echo $hours; ?>"> hours
    </td>
    <?php

}

function activedemand_link_event_form(){
  settings_fields(PREFIX.'_woocommerce_linked_actions');
  $linked_actions=\is_array(get_option(PREFIX.'_wc_actions_forms')) ?  get_option(PREFIX.'_wc_actions_forms') : array();

  //cycle through the actions already $linked_actions

  //onclick present a modal popup to edit the Options

  //provide a new action link buttons

}

function activedemand_carts($options){
  if (array_key_exists(PREFIX.'_woo_commerce_order_form_id', $options)) {
      $activedemand_woo_commerce_order_form_id = $options[PREFIX."_woo_commerce_order_form_id"];

  } else {
      $activedemand_woo_commerce_order_form_id = 0;
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

  settings_fields(PREFIX.'_woocommerce_options');
  ?>
  <table>
  <tr valign="top">
      <th scope="row">On WooCommerce Order:</th>
      <td><?php
          echo FormLinker::form_list_dropdown(PREFIX."_woocommerce_options_field[".PREFIX."_woo_commerce_order_form_id]",[], $activedemand_woo_commerce_order_form_id);

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
              <table class="wootbl" style="margin-left: 25px">
                  <tr>
                      <th>Pending</th>
                      <td><input type="checkbox"
                                 name="<?php echo PREFIX."_woocommerce_options_field[".PREFIX."_woo_commerce_use_status][pending]"; ?>
                                 value="1" <?php checked($activedemand_woo_commerce_use_status['pending'], 1); ?> />
                      </td>
                  </tr>
                  <tr>
                      <th>Processing</th>
                      <td><input type="checkbox"
                                 name="<?php echo PREFIX."_woocommerce_options_field[".PREFIX."_woo_commerce_use_status][processing]"; ?>
                                 value="1" <?php checked($activedemand_woo_commerce_use_status['processing'], 1); ?> />
                      </td>
                  </tr>
                  <tr>
                      <th>On Hold</th>
                      <td><input type="checkbox"
                                 name="<?php echo PREFIX."_woocommerce_options_field[".PREFIX."_woo_commerce_use_status][on-hold]";?>
                                 value="1" <?php checked($activedemand_woo_commerce_use_status['on-hold'], 1); ?> />
                      </td>
                  </tr>
                  <tr>
                      <th>Completed</th>
                      <td><input type="checkbox"
                                 name="<?php echo PREFIX."_woocommerce_options_field[".PREFIX."_woo_commerce_use_status][completed]";?>
                                 value="1" <?php checked($activedemand_woo_commerce_use_status['completed'], 1); ?> />
                      </td>
                  </tr>
                  <tr>
                      <th>Refunded</th>
                      <td><input type="checkbox"
                                 name="<?php echo PREFIX."_woocommerce_options_field[".PREFIX."_woo_commerce_use_status][refunded]";?>
                                 value="1" <?php checked($activedemand_woo_commerce_use_status['refunded'], 1); ?> />
                      </td>
                  </tr>
                  <tr>
                      <th>Cancelled</th>
                      <td><input type="checkbox"
                                 name="<?php echo PREFIX."_woocommerce_options_field[".PREFIX."_woo_commerce_use_status][cancelled]";?>
                                 value="1" <?php checked($activedemand_woo_commerce_use_status['cancelled'], 1); ?> />
                      </td>
                  </tr>
                  <tr>
                      <th>Failed</th>
                      <td><input type="checkbox"
                                 name="<?php echo PREFIX."_woocommerce_options_field[".PREFIX."_woo_commerce_use_status][failed]";?>
                                 value="1" <?php checked($activedemand_woo_commerce_use_status['failed'], 1); ?> />
                      </td>
                  </tr>

              </table>
          <?php } ?>
      </td>

      <?php activedemand_stale_cart_form($options);
}

function activedemand_plugin_options()
{
    $woo_commerce_installed = \class_exists('WooCommerce');

    $options = retrieve_activedemand_options();

    $form_xml = "";

    if (!array_key_exists(PREFIX.'_appkey', $options)) {
        $options[PREFIX.'_appkey'] = "";
    }

    $activedemand_appkey = $options[PREFIX.'_appkey'];

    if ("" != $activedemand_appkey) {
        //get Forms
        $form_xml=FormLinker::load_form_xml();

        $block_xml = simplexml_load_string($str);
      }

    if (!array_key_exists(PREFIX.'_ignore_form_style', $options)) {
        $options[PREFIX.'_ignore_form_style'] = 0;
    }
    if (!array_key_exists(PREFIX.'_ignore_block_style', $options)) {
        $options[PREFIX.'_ignore_block_style'] = 0;
    }

    ?>


    <div class="wrap">
        <img src="<?php echo get_base_url() ?>/images/ActiveDEMAND-Transparent.png"/>

       <h2>ActiveDemand Settings Options</h2>
       <?php settings_errors(); ?>
       <?php $form_view=isset($_GET['view']) ? $_GET['view'] : null; ?>
        <h2 class="nav-tab-wrapper">
            <a href="?page=activedemand_options" class="nav-tab <?php echo $form_view===null ? 'nav-tab-active' : '' ?>">Options</a>
            <?php if ("" != $activedemand_appkey):?>
              <a href="?page=activedemand_options&view=content" class="nav-tab <?php echo $form_view==='content' ? 'nav-tab-active' : '' ?>">Content</a>
            <?php endif;?>
            <?php if($woo_commerce_installed):?>
              <a href="?page=activedemand_options&view=woo" class="nav-tab <?php echo $form_view==='woo' ? 'nav-tab-active' : '' ?>">WooCommerce</a>
            <?php endif;?>
        </h2>

        <form method="post" action="options.php">

        <?php
        wp_nonce_field('update-options');
        switch($form_view):
        case 'woo':
          activedemand_carts($options);
           break;

          case 'content':?>
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

          <?php break;
          default:
          settings_fields(PREFIX.'_options');?>

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

            <h3><?php echo PLUGIN_VENDOR?> Plugin Options</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php echo PLUGIN_VENDOR?> API Key</th>
                    <td><input style="width:400px" type='text' name=<?php echo "\"".PREFIX."_options_field[".PREFIX."_appkey]\"";?>
                               value="<?php echo $activedemand_appkey; ?>"/></td>
                </tr>
                <?php if ("" != $activedemand_appkey) {
                    //get Forms
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

                if ("" != $block_xml) { ?>
                    <tr valign="top">
                        <th scope="row">Use Theme CSS for <?php echo PLUGIN_VENDOR?> Dynamic Blocks</th>
                        <td>
                            <input type="checkbox" name=<?php echo PREFIX."_options_field[".PREFIX."_ignore_block_style]"; ?>
                                   value="1" <?php checked($options[PREFIX.'_ignore_block_style'], 1); ?> />
                        </td>
                    </tr>

                <?php } ?>

                <?php endswitch; ?>

                </tr>
                <tr>
                    <td></td>
                    <?php if($form_view !=='content'):?>
                    <td>
                        <p class="submit">
                            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>"/>
                        </p>
                    </td>
                    <?php endif;?>
                </tr>
            </table>
        </form>
                        </td>
                    </tr>
                </table>
            </div>
    </div>
    <?php
}
 ?>

 <style type="text/css">
     table.wootbl th {

         padding: 5px;
     }

     table.wootbl td {

         padding: 5px;
     }
 </style>
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
 <?php ?>
