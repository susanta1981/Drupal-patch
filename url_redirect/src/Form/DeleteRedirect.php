<?php

namespace Drupal\url_redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Html;

class DeleteRedirect extends ConfigFormBase {
  public function getFormId() {
    return 'url_redirect_delete_form';
  }
  public function getEditableConfigNames() {
    return [
      'url_redirect.settings',
    ];

  }
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {

    $delete_path = \Drupal::request()->query->get('path');
    $path_data = url_redirect_path_edit($delete_path);
    if ($path_data) {
      $form['output'] = array(
        '#markup' => "Are you sure you want to delete <strong> " . $delete_path . '</strong> redirect? <br><br>',
      );
      $form['delete'] = array(
        '#type' => 'submit',
        '#value' => t('Delete'),
      );
      $form['no'] = array(
        '#type' => 'submit',
        '#value' => t('No'),
      );
      return $form;
    }
    else {
      drupal_set_message(t('Path specified is not correct for deletion.'), 'error');
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if ($values['op']->render() == 'No') {
      url_redirect_redirect(\Drupal::url('url_redirect.list_redirects'));
    }
    if ($values['op']->render() == 'Delete') {
     $delete_path = \Drupal::request()->query->get('path');
     if($delete_path != "<front>") {
        $delete_path = Html::escape($delete_path);
     }
      db_delete('url_redirect')
        ->condition('path', $delete_path)
        ->execute();

      drupal_set_message(t("The path '@path' is deleted.", array('@path' => $delete_path)));
      url_redirect_redirect(\Drupal::url('url_redirect.list_redirects'));
    }
  }
}
