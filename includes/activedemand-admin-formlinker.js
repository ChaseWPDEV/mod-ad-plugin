
/*
*/

jQuery(document).ready(function($){
  $('.ad-formlink-dropdown').change(function(e){
    var name=$(this).prop('name');
    var bracket=/\[\w+\]/.exec(name)[0];
    var hook=bracket.slice(1,-1);
    var form_id=$(this).val();
    var edit_btn=$('.ad-edit-linkedform.'+hook);
    edit_btn.hide();
    var pack={
      action:'reset_ad_form_linkage',
      form_mapper_reset_nonce: $('input[name="form_mapper_reset_'+hook+'"]').val(),
      form_id: form_id,
      action_hook:hook
    }
    $.ajax(ajaxurl, {
      method:"POST",
      data: pack,
      success: function(response){
        console.log(response)
        if(form_id==='0'){
          edit_btn.hide();
        } else{
          edit_btn.show();
        }
      },
      error: function(response){
        console.log(response);
      }
    });
  });

  $('.ad-settings-form input, .ad-settings-form select').change(function(){
    window.onbeforeunload = function() {
    return true;
    };
  });

  $('.ad-settings-form input[type="submit"]').click(function(){
    window.onbeforeunload = null;
  });
});

function adShowTab(evt, tabName){
  // Declare all variables
    var i, tabcontent, tablinks;
    if(evt) evt.preventDefault();
    // Get all elements with class="tabcontent" and hide them
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    // Get all elements with class="tablinks" and remove the class "active"
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // Show the current tab, and add an "active" class to the link that opened the tab
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}

function ad_form_linker_update(event, id, action){
  var map={};
  var $=jQuery;
  var form=$(event.target).parents('form');
  form.find('select').each(function(){
    var name=$(this).prop('name');
    var field=/form\[\w+\]/.exec(name)[0];
    var arg=$(this).val();
    map[field]=arg;
  });
  var pack={
    action: 'update_ad_form_linkage',
    action_hook: action,
    form_id: id,
    form_mapper_update_nonce: $('#form_mapper_update_nonce').val(),
    map: map
  }

  $.ajax(ajaxurl, {
    method: "POST",
    data: pack,
    success: function(response){
      console.log(response);
    }
  });
  $.featherlight.close();
}
