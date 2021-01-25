<?php

namespace Drupal\test_task_module\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\test_task_module\HubSpotHelper;

/**
 * Class ConfigMailForm.
 */
class ConfigMailForm extends ConfigFormBase {

  /**
   * @var string const value to access custom config settings.
   */
  public const CONFIG_HUBSPOT_FORM_SETINGS = 'test_task_module.settings';

  /**
   * @var string const value to access to the current custom config field.
   */
  public const CONFIG_HUBSPOT_FORM_API_KEY = 'test_task_module.setting_api_key';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_mail_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames(){
    return [
      static::CONFIG_HUBSPOT_FORM_SETINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIG_HUBSPOT_FORM_SETINGS);

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Input your API key recivier'), 
      '#default_value' => $config->get(static::CONFIG_HUBSPOT_FORM_API_KEY),
      '#weight' => '1',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // uses method \Drupal\test_task_module\HubSpotHelper::checkApiKey to check entered API key.
    if(!HubSpotHelper::checkApiKey($form_state->getValue('api_key'))){
      $form_state->setErrorByName('api_key', $this->t('Wrong API  key!'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::CONFIG_HUBSPOT_FORM_SETINGS)
          ->set(static::CONFIG_HUBSPOT_FORM_API_KEY, $form_state->getValue('api_key'))
          ->save();

    parent::submitForm($form, $form_state);
    $form_state->setRedirect('<front>');
  }
}
