<?php

namespace Drupal\url_redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Html;

class EditRedirect extends ConfigFormBase {
  public function getFormId() {
    return 'url_redirect_edit_form';
  }
  public function getEditableConfigNames() {
    return [
      'url_redirect.settings',
    ];

  }
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {

    $url = Url::fromRoute('url_redirect.list_redirects');
    $internal_link = \Drupal::l(t('Url Redirect List'), $url);
    $form['goto_list'] = array(
      '#markup' => $internal_link,
    );

    $path = \Drupal::request()->query->get('path');
    if($path != "<front>") {
        $path = Html::escape($path);
    }
    $edit_path = $path;
    $path_data = url_redirect_path_edit($edit_path);

    if ($path_data) {
      $check_form = $path_data['check_for'];
      $form['path'] = array(
        '#type' => 'textfield',
        '#title' => 'Path',
        '#attributes' => array('placeholder' => 'Enter Path'),
        '#required' => TRUE,
        '#default_value' => $path_data['path'],
        '#disabled' => TRUE,
      );
      $form['redirect_path'] = array(
        '#type' => 'textfield',
        '#title' => 'Redirect Path',
        '#attributes' => array('placeholder' => 'Enter Redirect Path'),
        '#required' => TRUE,
        '#default_value' => $path_data['redirect_path'],
        '#description' => t('This can be an internal Drupal path such as node/add, node/* .'),
      );
      $status = array(0 => 'Disabled', 1 => 'Enabled');
      $user_roles = user_role_names();
      $users = url_redirect_user_fetch();
      if ($check_form == 'Role') {
        $roles = (array) json_decode($path_data['roles']);
        $form['checked_for'] = array(
          '#type' => 'radios',
          '#options' => array(
            'Role' => t('Role')
          ),
          '#title' => t('Select Redirect path for'),
          '#required' => TRUE,
          '#default_value' => 'Role',
        );
        $form['roles'] = array(
          '#type' => 'select',
          '#options' => $user_roles,
          '#title' => t('Select Roles.'),
          '#multiple' => TRUE,
          '#default_value' => $roles,
        );
      }
      if ($check_form == 'User') {
        $default_users = (array) json_decode($path_data['users']);
        $form['checked_for'] = array(
          '#type' => 'radios',
          '#options' => array(
            'User' => t('User')
          ),
          '#title' => t('Select Redirect path for'),
          '#required' => TRUE,
          '#default_value' => 'User',
        );
        $form['user'] = array(
          '#type' => 'select',
          '#title' => t('Select Users.'),
          '#options' => $users,
          '#multiple' => TRUE,
          '#default_value' => $default_users,
        );
      }
      $form['message'] = array(
        '#type' => 'radios',
        '#options' => array(
          'Yes' => t('Yes'),
          'No' => t('No')
        ),
        '#title' => t('Display Message for Redirect'),
        '#required' => TRUE,
        '#description' => t('Show a message for redirect path.'),
        '#default_value' => $path_data['message'],
      );
      $form['status'] = array(
        '#type' => 'radios',
        '#options' => $status,
        '#title' => t('Status'),
        '#required' => TRUE,
        '#default_value' => $path_data['status'],
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
    else {
      drupal_set_message(t('Path Specified is not correct to update'), 'error');
    }
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // For settings page.
    if ($values['op']->render() == 'Delete') {
      url_redirect_redirect(\Drupal::url('url_redirect.list_redirects'));
    }
    if ($values['op']->render() == 'Save') {
      $path = $values['path'];
      $redirect_path = $values['redirect_path'];
      if (!\Drupal::service('path.validator')->isValid($redirect_path)) {
        $form_state->setErrorByName('redirect_path', $this->t("The redirect path '@link_path' is either invalid or you do not have access to it.", array('@link_path' => $redirect_path)));
      }
      $checked_for = $values['checked_for'];
      if ($checked_for == 'User') {
        $user_values = $values['user'];
        if (!$user_values) {
          $form_state->setErrorByName('user', $this->t("Select Atleast one user."));
        }
      }
      if ($checked_for == 'Role') {
        $roles_values = $values['roles'];
        if (!$roles_values) {
          $form_state->setErrorByName('roles', $this->t("Select Atleast one Role."));
        }
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $path = $values['path'];
    $redirect_path = $values['redirect_path'];
    $checked_for = $values['checked_for'];
    if ($checked_for == 'User') {
      $user_values = $values['user'];
      if ($user_values) {
        $users_values = json_encode($user_values);
        $role_values = '';
      }
    }
    if ($checked_for == 'Role') {
      $roles_values = $values['roles'];
      if ($roles_values) {
        $role_values = json_encode($roles_values);
        $users_values = '';
      }
    }
    $status = $values['status'];
    $message = $values['message'];

    db_update('url_redirect')
        ->fields(array(
          'roles' => $role_values,
          'users' => $users_values,
          'redirect_path' => Html::escape($redirect_path),
          'status' => $status,
          'message' => $message,
          'check_for' => $checked_for,
        ))
        ->condition('path', $path)
        ->execute();
    drupal_set_message(t("The path '@path' is Updated.", array('@path' => $path)));
    url_redirect_redirect(\Drupal::url('url_redirect.list_redirects'));
  }
}
