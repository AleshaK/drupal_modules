<?php

namespace Drupal\test_task_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Class MailForm.
 */
class MailForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mail_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First name'),
      '#required' =>true,
      '#maxlength' => 20,
      '#weight' => '0',
    ];
    $form['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last name'),
      '#maxlength' => 20,
      '#weight' => '5',
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' =>true,
      '#weight' => '10',
    ];
    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#required' =>true,
      '#maxlength' => 128,
      '#weight' => '15',
    ];
    $form['message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Message'),
      '#required' =>true,
      '#weight' => '20',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => '100',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if(!$form_state->getValue('email') || !preg_match('/\w+@\w+\.\w+/', $form_state->getValue('email'))){
      $form_state->setErrorByName('email', $this->t('Wrong email address!'));
    }
    if(preg_match('/\d/', $form_state->getValue('first_name'))){
      $form_state->setErrorByName('first_name', $this->t('Wrong first name!'));
    }
    if(preg_match('/\d/', $form_state->getValue('last_name'))){
      $form_state->setErrorByName('last_name', $this->t('Wrong last name!'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $first_name = $form_state->getValue('first_name');
    $last_name = '';
    if($form_state->getValue('last_name'))
      $last_name = ' ' . $form_state->getValue('last_name');
    $body = $form_state->getValue('message')['value'] . 
         '<p> From ' . $first_name . $last_name .'.</p>';
    $body = [
      '#markup' => $body,
    ];
    $subject = new TranslatableMarkup($form_state->getValue('subject'));
    $from = $form_state->getValue('email');

    // to make a wonderful thing, uncomment
    // while(true){
      $mail_handler = \Drupal::service('test_task_module.mail_handler');
      $result = $mail_handler->sendMail($from, $subject, $body);
      $messanger = \Drupal::messenger();
      if($result){
        \Drupal::logger('test_task_module')->notice('Send message to ' . $form_state->getValue('email'));
        $messanger->addMessage('Message has been sent.');
      } else{
        $messanger->addMessage('Message something went wrong.', 'error');
      }
    //}
  }
}
