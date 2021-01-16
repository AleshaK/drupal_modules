<?php

namespace Drupal\word_counter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WordCounterSettingsForm.
 */
class WordCounterSettingsForm extends ConfigFormBase {

   /**
    * Configuration settings id.
    *
    * @var string
    */    
    const SETTINGS = 'word_counter.settings';

   /**
    * Configuration setting turn on/off id.
    *
    * @var string
    */  
    const SETTING_TURN = 'word_counter.on_off';

   /**
    * Configuration setting node types id.
    *
    * @var string
    */ 
    const SETTING_NODE_TYPES = 'word_counter.settings_node_types';


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'word_counter_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames(){
    return [
      static::SETTINGS,
    ];
  }
  
  /**
   * Get an array of node types.
   *
   * @return array
   *   An array of node types keyed by machine name.
   */
  public static function getNodeTypes() {
    $options = [];
    $types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
    foreach ($types as $type) {
      $options[$type->id()] = t($type->label());
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    //when module installed: state = null
    if(!$config->get(static::SETTING_NODE_TYPES) == null)
      $values = $config->get(static::SETTING_NODE_TYPES);
    else $values = [];

    $form['on_off_word_counter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('On/off word counter'),
      '#default_value' => $config->get(static::SETTING_TURN),
      '#weight' => '0',
    ];
    $form['types'] = [
      '#type' => 'checkboxes',
      '#multiple' => TRUE,
      '#options' => $this->getNodeTypes(),
      '#title' => $this->t('Select content types'), 
      '#default_value' => $values,
      '#weight' => '0',
    ];

    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //saving configuration
    $config = $this->config(static::SETTINGS);  
    $config->set(static::SETTING_TURN, $form_state->getValue('on_off_word_counter'))->save();     
    $config->set(static::SETTING_NODE_TYPES, $form_state->getValue('types'))->save();
    
    //call parent method
    parent::submitForm($form, $form_state);
  }
}