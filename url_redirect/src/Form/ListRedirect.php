<?php

namespace Drupal\url_redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Utility\Html;

class ListRedirect extends ConfigFormBase {
  public function getFormId() {
    return 'url_redirect_list_form';
  }
  public function getEditableConfigNames() {
    return [
      'url_redirect.settings',
    ];

  }
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {

    global $base_url;
    $form = array();

    $url = Url::fromRoute('url_redirect.add_redirect');
    $internal_link = Link::fromTextAndUrl($this->t('Add Url Redirect'), $url)->toString();
    $form['goto_list'] = array(
      '#markup' => $internal_link,
    );

    $form['path'] = array(
      '#title' => $this->t('Path'),
      '#type' => 'textfield',
      '#default_value' => isset($_GET['path']) ? $_GET['path'] : '',
    );
    $form['redirect_path'] = array(
      '#title' => $this->t('Redirect Path'),
      '#type' => 'textfield',
      '#default_value' => isset($_GET['redirect_path']) ? $_GET['redirect_path'] : '',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Filter',
    );
    $form['reset'] = array(
      '#type' => 'submit',
      '#value' => 'Reset',
    );

    $query = db_select('url_redirect', 'u')
        ->fields('u');
    if (!empty($_GET['path'])) {
      $query->condition('path', '%' . db_like($_GET['path']) . '%', 'LIKE');
    }
    if (!empty($_GET['redirect_path'])) {
      $query->condition('redirect_path', '%' . db_like($_GET['redirect_path']) . '%', 'LIKE');
    }
    $result = $query->execute()->fetchAll(\PDO::FETCH_NUM);

    // Header for the list of Redirects.
    $header = array(
      array('data' => $this->t('Path')),
      array('data' => $this->t('Redirect Path')),
      array('data' => $this->t('Checked For')),
      array('data' => $this->t('Roles')),
      array('data' => $this->t('Users')),
      array('data' => $this->t('Status')),
      array('data' => $this->t('Display Message')),
      array('data' => $this->t('Edit link')),
      array('data' => $this->t('Delete link')),
    );
    $rows = array();
    $output = '';
    foreach ($result as $url) {

      // Edit link.
      $edit_link = $base_url . '/admin/config/url_redirect/edit?path=' . $url[0];
      $edit_url = Url::fromUri($edit_link, array('absolute' => TRUE));

      // Delete link.
      $delete_link = $base_url . '/admin/config/url_redirect/delete?path=' . $url[0];
      $delete_url = Url::fromUri($delete_link, array('absolute' => TRUE));

      // Get the list of all the Roles.
      if ($url[1]) {
        $roles_names = array_keys((array) json_decode($url[1]));
        $roles = '';
        foreach ($roles_names as $rid) {
          $roles .= $rid . ', ';
        }
        $list_of_roles = rtrim($roles, ', ');
      }
      else {
        $list_of_roles = 'N/A';
      }

      // Get the list of all the Users.
      if ($url[2]) {
        $user_names = array_keys((array) json_decode($url[2]));
        $names = '';
        foreach ($user_names as $uid) {
          $user_name = User::load($uid)->getEmail();

          if ($user_name) {
            $names .= $user_name . '(' . $uid . ')' . ', ';
          }
        }
        $list_of_users = rtrim($names, ', ');
      }
      else {
        $list_of_users = 'N/A';
      }
      // Get the status.
      if ($url[4] == 1) {
        $status = 'Enabled';
      }
      else {
        $status = 'Disabled';
      }
      // Get the message.
      if ($url[5] == 'Yes') {
        $message = 'Enabled';
      }
      else {
        $message = 'Disabled';
      }

      $rows[] = array(
        array('data' => $url[0]),
        array('data' => $url[3]),
        array('data' => $url[6]),
        array('data' => $list_of_roles),
        array('data' => $list_of_users),
        array('data' => $status),
        array('data' => $message),
        array('data' => Link::fromTextAndUrl($this->t('Edit'), $edit_url)),
        array('data' => Link::fromTextAndUrl($this->t('Delete'), $delete_url)),
      );
    }
    if (count($rows) > 0) {
      $table = array(
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => array(
          'id' => 'list-of-redirect',
        ),
      );
      $markup = drupal_render($table);

      $form['output'] = array(
        '#markup' => $markup,
      );
    }

    else {
      $form['output'] = array(
        '#markup' => $this->t('No Paths available.'),
      );
    }
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Goto current path if reset.
    if ($values['op'] == 'Reset') {
      url_redirect_redirect(Url::fromRoute('url_redirect.list_redirects')->toString());
    }
    // Pass values to url.
    if ($values['op'] == 'Filter') {
      $filter_path = $values['path'];
      $filter_redirect_path = $values['redirect_path'];
      $params['path'] = Html::escape($filter_path);
      $params['redirect_path'] = Html::escape($filter_redirect_path);
      url_redirect_redirect(Url::fromRoute('url_redirect.list_redirects', $params, array('absolute' => TRUE))->toString());
    }
  }
}
