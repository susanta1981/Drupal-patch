<?php
/**
 * @file
 * Admin page callbacks for the cssmenu module.
 */

/**
 * Menu callback provides the administration overview page.
 */
function cssmenu_overview_page() {

  $result = db_select('cssmenu', 'c')
    ->fields('c')
    ->orderBy('title', 'ASC')
    ->execute();

  $content = array();
  while ($tab = $result->fetchAssoc()) {
    if (!empty($tab)) {
      $tab['description'] = check_plain($tab['description']);
      $tab['href'] = 'admin/structure/cssmenu/edit/' . $tab['mid'];
      $tab['localized_options'] = array();
      $content[] = $tab;
    }
  }
  if (!empty($content)) {
    return theme('admin_block_content', array('content' => $content));
  }
  else {
    return t('There are no CSS menus yet.');
  }
}

/**
 * Loads CSS menu block details.
 */
function cssmenu_load_menu($mid) {
  $block_data = array();
  $menu_details = array();

  $data = db_select('cssmenu', 'c')
    ->fields('c')
    ->condition('mid', $mid)
    ->execute()
    ->fetchAssoc();

  $block_data['mid'] = $data['mid'];
  $block_data['title'] = $data['title'];
  $block_data['description'] = $data['description'];
  $block_data['menu'] = $data['menu'];
  $menu_details = unserialize($data['menu_details']);
  if (is_array($menu_details)) {
    foreach ($menu_details as $key => $value) {
      $block_data[$key] = $value;
    }
  }
  return $block_data;
}

/**
 * Administrative page callbacks for the CSS menu module.
 */
function cssmenu_overview_form($type, $mid = 0) {
  if ($mid) {
    $edit = cssmenu_load_menu($mid);
  }

  if ((isset($_POST['op']) && $_POST['op'] == t('Delete')) || !empty($_POST['confirm'])) {
    return drupal_get_form('cssmenu_confirm_delete', $edit['mid']);
  }

  switch ($type) {
    case 'add':
    case 'edit':
      $custom_menu = array();
      $result = db_select('menu_custom', 'm')
        ->fields('m', array('menu_name', 'title'))
        ->orderBy('title', 'ASC')
        ->execute();
      foreach ($result as $item) {
        $custom_menu[$item->menu_name] = check_plain($item->title);
      }
      $edit['menu_options'] = $custom_menu;
      return drupal_get_form('cssmenu_form', $edit);
  }
}

/**
 * Menu callback provides the CSS menu block creation form.
 *
 * @param array $form_state
 *   Not used.
 *
 * @param array $edit
 *   An associative array of block details used in block edit operation.
 *
 * @ingroup forms
 */
function cssmenu_form($form, &$form_state, $edit = array()) {
  $edit += array(
    'mid' => 0,
    'title' => '',
    'description' => '',
    'menu' => NULL,
    'menu_style' => NULL,
    'menu_bgcolor' => "000000",
    'menu_hover_bgcolor' => "000000",
    'menu_text_color' => "FFFFFF",
    'menu_hover_text_color' => "FFFFFF",
    'menu_item_bgcolor' => "000000",
    'menu_item_hover_bgcolor' => "000000",
    'menu_item_text_color' => "FFFFFF",
    'menu_item_hover_text_color' => "FFFFFF",
    'menu_text_transform' => 'capitalize',
    'menu_item_text_transform' => 'capitalize',
    'opacity_effect' => TRUE,
  );

  drupal_set_title(check_plain($edit['title']));

  $path = libraries_get_path('colorpicker');
  drupal_add_css($path . '/css/colorpicker.css');
  drupal_add_js($path . '/js/colorpicker.js');

  $module_path = drupal_get_path('module', 'cssmenu');
  drupal_add_js($module_path . '/js/cssmenu.js');
  drupal_add_css($module_path . '/css/cssmenu.admin.css');

  $form['mid'] = array(
    '#type' => 'value',
    '#default_value' => $edit['mid'],
  );

  $form['title'] = array(
    '#title' => t('Title'),
    '#type' => 'textfield',
    '#default_value' => $edit['title'],
    '#maxlength' => 128,
    '#required' => TRUE,
  );

  $form['description'] = array(
    '#title' => t('Description'),
    '#type' => 'textarea',
    '#default_value' => $edit['description'],
    '#maxlength' => 2048,
  );

  $form['menudetails'] = array(
    '#title' => t('Menu details'),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
  );

  $form['menudetails']['menu'] = array(
    '#prefix' => '<div class="menu_wrapper clear">',
    '#title' => t('Menu'),
    '#id' => 'menu',
    '#type' => 'radios',
    '#options' => $edit['menu_options'],
    '#default_value' => $edit['menu'],
    '#required' => TRUE,
    '#description' => t('Select menu to render.'),
    '#suffix' => '</div><div class="clear"></div>',
  );

  $form['menudetails']['menu_style'] = array(
    '#prefix' => '<div class="menu_style clear">',
    '#title' => t('Menu style'),
    '#id' => 'menu_style',
    '#type' => 'radios',
    '#options' => array('vertical_right_fly' => t('Vertical right fly'), 'vertical_down_fly' => t('Vertical down fly')),
    '#default_value' => $edit['menu_style'],
    '#required' => TRUE,
    '#suffix' => '</div><div class="clear"></div>',
    '#description' => t('<b>Vertical right fly</b>: menu items are listed on top of each other and expand to the right<br />
    <b>Vertical down fly</b>: menu items are listed side by side and expand down.'),
  );

  $form['menucolor'] = array(
    '#title' => t('Parent menu details'),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
  );

  $form['menucolor']['menu_bgcolor'] = array(
    '#prefix' => '<div><div class="fleft colorfield">',
    '#title' => t('Background color'),
    '#description' => t('Background color for menu.'),
    '#type' => 'textfield',
    '#id' => 'menu_bgcolor',
    '#default_value' => $edit['menu_bgcolor'],
    '#required' => TRUE,
    '#maxlength' => 6,
    '#size' => 10,
    '#suffix' => '</div>',
    '#field_suffix' => '<div class="inline" id="menu_bgcolor_hex"></div>',
  );

  $form['menucolor']['menu_hover_bgcolor'] = array(
    '#prefix' => '<div class="fleft">',
    '#title' => t('Mouse over background color'),
    '#description' => t('Background color for menu when mouse cursor is over menu.'),
    '#type' => 'textfield',
    '#id' => 'menu_hover_bgcolor',
    '#default_value' => $edit['menu_hover_bgcolor'],
    '#required' => TRUE,
    '#maxlength' => 6,
    '#size' => 10,
    '#suffix' => '</div></div>',
    '#field_suffix' => '<div class="inline" id="menu_hover_bgcolor_hex"></div>',
  );

  $form['menucolor']['menu_text_color'] = array(
    '#prefix' => '<div style="clear:both;"><div class="fleft colorfield">',
    '#title' => t('Text color'),
    '#description' => t('Menu text color.'),
    '#type' => 'textfield',
    '#id' => 'menu_text_color',
    '#default_value' => $edit['menu_text_color'],
    '#required' => TRUE,
    '#maxlength' => 6,
    '#size' => 10,
    '#suffix' => '</div>',
    '#field_suffix' => '<div class="inline" id="menu_text_color_hex"></div>',
  );

  $form['menucolor']['menu_hover_text_color'] = array(
    '#prefix' => '<div class="fleft">',
    '#title' => t('Mouse over text color'),
    '#description' => t('Menu text color when mouse cursor is over menu.'),
    '#type' => 'textfield',
    '#id' => 'menu_hover_text_color',
    '#default_value' => $edit['menu_hover_text_color'],
    '#required' => TRUE,
    '#maxlength' => 6,
    '#size' => 10,
    '#suffix' => '</div></div>',
    '#field_suffix' => '<div class="inline" id="menu_hover_text_color_hex"></div>',
  );

  $form['menucolor']['menu_text_transform'] = array(
    '#prefix' => '<div class="menu_text_transform" style="clear: both;">',
    '#title' => t('Text transformation'),
    '#type' => 'radios',
    '#id' => 'menu_text_transform',
    '#options' => array(
      'none' => t('None'),
      'capitalize' => t('Capitalize'),
      'lowercase' => t('Lowercase'),
      'uppercase' => t('Uppercase'),
    ),
    '#default_value' => $edit['menu_text_transform'],
    '#required' => TRUE,
    '#suffix' => '</div>',
  );

  $form['menuitemcolor'] = array(
    '#title' => t('Menu item details'),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
  );

  $form['menuitemcolor']['menu_item_bgcolor'] = array(
    '#prefix' => '<div><div class="fleft colorfield">',
    '#title' => t('Background color'),
    '#description' => t('Background color for menu.'),
    '#type' => 'textfield',
    '#id' => 'menu_item_bgcolor',
    '#default_value' => $edit['menu_item_bgcolor'],
    '#required' => TRUE,
    '#maxlength' => 6,
    '#size' => 10,
    '#suffix' => '</div>',
    '#field_suffix' => '<div class="inline" id="menu_item_bgcolor_hex"></div>',
  );

  $form['menuitemcolor']['menu_item_hover_bgcolor'] = array(
    '#prefix' => '<div class="fleft">',
    '#title' => t('Mouse over background color'),
    '#description' => t('Background color for menu when mouse cursor is over menu.'),
    '#type' => 'textfield',
    '#id' => 'menu_item_hover_bgcolor',
    '#default_value' => $edit['menu_item_hover_bgcolor'],
    '#required' => TRUE,
    '#maxlength' => 6,
    '#size' => 10,
    '#suffix' => '</div></div>',
    '#field_suffix' => '<div class="inline" id="menu_item_hover_bgcolor_hex"></div>',
  );

  $form['menuitemcolor']['menu_item_text_color'] = array(
    '#prefix' => '<div class="clear"><div class="fleft colorfield">',
    '#title' => t('Text color'),
    '#description' => t('Menu text color.'),
    '#type' => 'textfield',
    '#id' => 'menu_item_text_color',
    '#default_value' => $edit['menu_item_text_color'],
    '#required' => TRUE,
    '#maxlength' => 6,
    '#size' => 10,
    '#suffix' => '</div>',
    '#field_suffix' => '<div class="inline" id="menu_item_text_color_hex"></div>',
  );

  $form['menuitemcolor']['menu_item_hover_text_color'] = array(
    '#prefix' => '<div class="fleft">',
    '#title' => t('Mouse over text color'),
    '#description' => t('Menu text color when mouse cursor is over menu.'),
    '#type' => 'textfield',
    '#id' => 'menu_item_hover_text_color',
    '#default_value' => $edit['menu_item_hover_text_color'],
    '#required' => TRUE,
    '#maxlength' => 6,
    '#size' => 10,
    '#suffix' => '</div></div>',
    '#field_suffix' => '<div class="inline" id="menu_item_hover_text_color_hex"></div>',
  );

  $form['menuitemcolor']['menu_item_text_transform'] = array(
    '#prefix' => '<div class="menu_item_text_transform clear">',
    '#title' => t('Menu item text transformation'),
    '#type' => 'radios',
    '#id' => 'menu_item_text_transform',
    '#options' => array(
      'none' => t('None'),
      'capitalize' => t('Capitalize'),
      'lowercase' => t('Lowercase'),
      'uppercase' => t('Uppercase'),
    ),
    '#default_value' => $edit['menu_item_text_transform'],
    '#required' => TRUE,
    '#suffix' => '</div>',
  );

  $form['menuitemcolor']['opacity_effect'] = array(
    '#prefix' => '<div class="clear">',
    '#title' => t('Opacity effect for background'),
    '#type' => 'checkbox',
    '#id' => 'opacity_effect',
    '#default_value' => $edit['opacity_effect'],
    '#required' => TRUE,
    '#suffix' => '</div>',
  );

  $link = '<a href="javascript:void(0);" id="preview" >Click to preview</a>';
  $form['preview_window'] = array(
    '#markup' => '<div id="menu_preview_wrapper"><div class="preview_link">' . $link . '</div><div id="menu_preview"></div></div>',
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  $form['delete'] = array(
    '#type' => 'submit',
    '#value' => t('Delete'),
  );

  return $form;
}

/**
 * Process cssmenu_form form submissions.
 *
 * @param array $form
 *   Not used.
 *
 * @param array $form_state
 *   Get form values form submissions.
 */
function cssmenu_form_submit($form, &$form_state) {

  $mid = trim($form_state['values']['mid']);
  $title = trim($form_state['values']['title']);
  $description = trim($form_state['values']['description']);
  $menu = $form_state['values']['menu'];

  $menu_details = array();
  $menu_details['menu_style'] = $form_state['values']['menu_style'];

  $menu_details['menu_bgcolor'] = trim($form_state['values']['menu_bgcolor']);
  $menu_details['menu_hover_bgcolor'] = trim($form_state['values']['menu_hover_bgcolor']);
  $menu_details['menu_text_color'] = trim($form_state['values']['menu_text_color']);
  $menu_details['menu_hover_text_color'] = trim($form_state['values']['menu_hover_text_color']);
  $menu_details['menu_text_transform'] = $form_state['values']['menu_text_transform'];

  $menu_details['menu_item_bgcolor'] = trim($form_state['values']['menu_item_bgcolor']);
  $menu_details['menu_item_hover_bgcolor'] = trim($form_state['values']['menu_item_hover_bgcolor']);
  $menu_details['menu_item_text_color'] = trim($form_state['values']['menu_item_text_color']);
  $menu_details['menu_item_hover_text_color'] = trim($form_state['values']['menu_item_hover_text_color']);
  $menu_details['menu_item_text_transform'] = $form_state['values']['menu_item_text_transform'];
  $menu_details['opacity_effect'] = $form_state['values']['opacity_effect'];

  $style_sheet = _cssmenu_get_style_sheet($menu_details, "{$title}-{$menu}");

  $record = new stdClass;
  $record->mid = $mid;
  $record->title = $title;
  $record->description = $description;
  $record->menu = $menu;
  $record->menu_details = serialize($menu_details);
  $record->style_sheet = $style_sheet;

  if (0 == $mid) {
    $status = drupal_write_record('cssmenu', $record);
  }
  else {
    $status = drupal_write_record('cssmenu', $record, 'mid');
  }

  switch($status) {
    case SAVED_NEW:
      drupal_set_message(t('CSS menu %title has been saved.', array('%title' => $title)));
      watchdog('cssmenu', 'CSS menu %title has been saved.', array('%title' => $title));
    break;

    case SAVED_UPDATED:
      drupal_set_message(t('CSS menu %title has been updated.', array('%title' => $title)));
      watchdog('cssmenu', 'CSS menu %title has been updated.', array('%title' => $title));
    break;

    default:
      if (0 == $mid) {
        drupal_set_message(t('Failed to save %title CSS menu.', array('%title' => $title)), 'error');
        watchdog('cssmenu', 'Failed to save %title CSS menu.', array('%title' => $title));
      }
      else {
        drupal_set_message(t('Failed to update %title CSS menu.', array('%title' => $title)), 'error');
        watchdog('cssmenu', 'Failed to update %title CSS menu.', array('%title' => $title));
      }
     break;
  }

  _cssmenu_create_css();

  $form_state['redirect'] = 'admin/structure/cssmenu';

}

/**
 * Returns a confirmation page for deleting a CSS menu.
 *
 * @param array $form_state
 *   Not used.
 *
 * @param int $mid
 *   ID of the menu to be deleted.
 */
function cssmenu_confirm_delete($form, &$form_state, $mid) {
  $title = db_select('cssmenu', 'c')
    ->fields('c', array('title'))
    ->condition('mid', $mid)
    ->execute()
  ->fetchField();

  $form['mid'] = array('#type' => 'value', '#value' => $mid);
  $form['title'] = array('#type' => 'value', '#value' => $title);

  return confirm_form($form,
    t('Are you sure you want to delete the CSS menu %title?', array('%title' => $title)),
    'admin/structure/cssmenu/edit/' . $mid,
    '',
    t('Delete'));
}

/**
 * Implementation of forms api _submit call to delete a CSS menu after confirmation.
 */
function cssmenu_confirm_delete_submit($form, &$form_state) {

  db_delete('cssmenu')->condition('mid', $form_state['values']['mid'])->execute();

  $title = $form_state['values']['title'];
  drupal_set_message(t('The CSS menu %title has been deleted.', array('%title' => $title)));
  watchdog('cssmenu', 'The CSS menu %title has been deleted.', array('%title' => $title));

  $form_state['redirect'] = 'admin/structure/cssmenu';
  return;
}

/**
 * Helper function get contents of CSS.
 *
 * @ingroup cssmenu_form_submit()
 * @ingroup _cssmenu_preview()
 */
function _cssmenu_get_style_sheet($menu_details, $menu_class) {
  $module_path = drupal_get_path('module', 'cssmenu');

  switch ($menu_details['menu_style']) {
    case 'vertical_right_fly';
      $file_path = $module_path . '/css/vertical_right_fly.css';
      break;

    case 'vertical_down_fly';
      $file_path = $module_path . '/css/vertical_down_fly.css';
      break;
  }

  $fp = fopen($file_path, 'r');
  $contents = fread($fp, filesize($file_path));
  fclose($fp);

  $ie_opacity = 100;
  $non_ie_opacity = 1.0;
  if ($menu_details['opacity_effect']) {
    $ie_opacity = 90;
    $non_ie_opacity = 0.9;
  }

  $find = array(
    '@menu_bgcolor',
    '@menu_hover_bgcolor',
    '@menu_text_color',
    '@menu_hover_text_color',
    '@menu_text_transform',
    '@menu_item_bgcolor',
    '@menu_item_hover_bgcolor',
    '@menu_item_text_color',
    '@menu_item_hover_text_color',
    '@menu_item_text_transform',
    '@ie_opacity',
    '@non_ie_opacity',
    '@menu_class',
  );

  $replace = array(
    '#' . $menu_details['menu_bgcolor'],
    '#' . $menu_details['menu_hover_bgcolor'],
    '#' . $menu_details['menu_text_color'],
    '#' . $menu_details['menu_hover_text_color'],
    $menu_details['menu_text_transform'],
    '#' . $menu_details['menu_item_bgcolor'],
    '#' . $menu_details['menu_item_hover_bgcolor'],
    '#' . $menu_details['menu_item_text_color'],
    '#' . $menu_details['menu_item_hover_text_color'],
    $menu_details['menu_item_text_transform'],
    $ie_opacity,
    $non_ie_opacity,
    _cssmenu_get_clean_string($menu_class),
  );

  return str_replace($find, $replace, $contents);
}

/**
 * Helper function to create CSS from saved style sheets.
 *
 * @ingroup cssmenu_form_submit()
 */
function _cssmenu_create_css() {
  $style_sheet = '';
  $result = db_select('cssmenu', 'c')->fields('c', array('title', 'style_sheet'))->execute();
  foreach ($result as $data) {
    $style_sheet .= '/* ' . $data->title . ' */' . PHP_EOL;
    $style_sheet .= $data->style_sheet;
    $style_sheet .= '/* ' . $data->title . ' */' . PHP_EOL . PHP_EOL;
  }

  $file_path = file_default_scheme() . '://' . '/cssmenu';
  file_prepare_directory($file_path, FILE_CREATE_DIRECTORY, 'cssmenu_form');
  $filename = $file_path . '/cssmenu.css';

  file_unmanaged_save_data($style_sheet, $filename, FILE_EXISTS_REPLACE);

  // Clear css cache, as we have configure style for menu.
  drupal_clear_css_cache();
}
