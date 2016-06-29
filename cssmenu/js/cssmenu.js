(function ($) {

Drupal.behaviors.initColorPicker = {
  attach: function (context) {
    generatePreview("load");
    var colorPickerFields = ['menu_bgcolor', 'menu_hover_bgcolor', 'menu_text_color', 'menu_hover_text_color', 'menu_item_bgcolor', 'menu_item_hover_bgcolor', 'menu_item_text_color', 'menu_item_hover_text_color'];
    $.each(colorPickerFields, function(index, value) {
    showColorPreview($('#' + value).val(), '#' + value);

    $('#' + value).ColorPicker({
      onSubmit: function(hsb, hex, rgb, el) {
      showColorPreview(hex, '#' + el.id);
      $(el).ColorPickerHide();
      },
      onChange: function(hsb, hex, rgb) {
      showColorPreview(hex, '#' + value);
      },
      onBeforeShow: function () {
      $('#' + value).ColorPickerSetColor(this.value);
      },
    })
    .bind('keyup', function(){
      $('#' + value).ColorPickerSetColor(this.value);
    });
    });
	
	$('#preview').click(function(){
		generatePreview("click");
	});
	
  }
};


function showColorPreview(colorCode, id) {
  $(id).val(colorCode);
  $(id).css('color', '#' + colorCode);
  $(id).css('background', '#' + colorCode);
  $(id + '_hex').css('font-weight', 'bold');
  if("" != colorCode) {
    $(id + '_hex').html('#' + colorCode);
  }
  else {
    $(id + '_hex').html('');
  }
}

function validatePreviewInput(event) {

  var is_menu_selected = false;
  $('input:radio[name=menu]').each(function(){
    if($(this).is(':checked')) {
      is_menu_selected = true;
    }
  });

  var is_menu_style_selected = false;
  $('input:radio[name=menu_style]').each(function(){
    if($(this).is(':checked')) {
      is_menu_style_selected = true;
    }
  });

  if('click' == event) {
    $("#menu_preview").html('');
    if(!is_menu_selected) {
      $("#menu_preview").append("Menu field is required.");
    }

    if(!is_menu_style_selected) {
      $("#menu_preview").append("<br/>Menu style field is required.");
    }
  }

  $("#menu_preview").css('color', '#000000');
  if((!is_menu_selected) || (!is_menu_style_selected)) {
    $("#menu_preview").css('color', '#FF0000');
    return false;
  }

  return true;

}

function generatePreview(event) {

  if(validatePreviewInput(event)) {

    var opacity_effect = 0;
    if($('#opacity_effect').is(':checked')) {
      opacity_effect = 1;
    }

    var key = ['menu_title',
      'menu',
      'menu_style',
      'menu_bgcolor',
      'menu_hover_bgcolor',
      'menu_text_color',
      'menu_hover_text_color',
      'menu_text_transform',
      'menu_item_bgcolor',
      'menu_item_hover_bgcolor',
      'menu_item_text_color',
      'menu_item_hover_text_color',
      'menu_item_text_transform',
      'opacity_effect'];

    var value = [$('#edit-title').val(),
      $('input:radio[name=menu]:checked').val(),
      $('input:radio[name=menu_style]:checked').val(),
      $('#menu_bgcolor').val(),
      $('#menu_hover_bgcolor').val(),
      $('#menu_text_color').val(),
      $('#menu_hover_text_color').val(),
      $('input:radio[name=menu_text_transform]:checked').val(),
      $('#menu_item_bgcolor').val(),
      $('#menu_item_hover_bgcolor').val(),
      $('#menu_item_text_color').val(),
      $('#menu_item_hover_text_color').val(),
      $('input:radio[name=menu_item_text_transform]:checked').val(),
      opacity_effect];

    $("#menu_preview").html("Please wait refreshing preview...");

    $.ajax({
      type: "POST",
      url: Drupal.settings.basePath + "xmlrpc.php",
      dataType:"xml",
      cache: false,
      data: "<methodCall><methodName>GetMenuPreview</methodName><params><value>" + key + "</value><value>" + value + "</value></params></methodCall>",
      success:function(data) {
        $("#menu_preview").html($(data).find('value').text());
      }
    });
  }
}

})(jQuery);
