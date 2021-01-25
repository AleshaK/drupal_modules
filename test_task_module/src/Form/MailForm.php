<?php

namespace Drupal\test_task_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\test_task_module\HubSpotHelper;
use Exception;

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
      '#default_value' =>'lalala'
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
    
    // validate form by email: check for x@x.y where x - any set of letters or numbers, y - a set of 2 to 3 letters
    if(!$form_state->getValue('email') || !preg_match('/\w+@\w+\.\D{2,3}$/', $form_state->getValue('email'))){
      $form_state->setErrorByName('email', $this->t('Wrong email address!'));
    }

    //  there are no numbers in the first name.
    if(preg_match('/\d/', $form_state->getValue('first_name'))){
      $form_state->setErrorByName('first_name', $this->t('Wrong first name!'));
    }

    //  there are no numbers in the last name.
    if(preg_match('/\d/', $form_state->getValue('last_name'))){
      $form_state->setErrorByName('last_name', $this->t('Wrong last name!'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */ 
  public function submitForm(array &$form, FormStateInterface $form_state) {

    //get all values from form
    $first_name = $form_state->getValue('first_name');
    $last_name = $form_state->getValue('last_name');
    $email = $form_state->getValue('email');
    $subject = new TranslatableMarkup($form_state->getValue('subject'));
    $body = [
      '#markup' => $form_state->getValue('message')['value'],
    ];

    $messanger = \Drupal::messenger();
    $logger = \Drupal::logger('test_task_module');

    // if message send add log message in hubspot.
    try{
    // Drupal\test_task_module\HubSpotHelper object
    $hubSpotHelper = new HubSpotHelper(\Drupal::config(ConfigMailForm::CONFIG_HUBSPOT_FORM_SETINGS)->get(ConfigMailForm::CONFIG_HUBSPOT_FORM_API_KEY));
    } catch(Exception $e){
      $this->errorSendMail($email, $messanger, $logger);
      return;
    }

    // search contact by email address
    // @var \HubSpot\Client\Crm\Contacts\Model\CollectionResponseWithTotalSimplePublicObject
    $search_result = $hubSpotHelper->searchByEmail($email);
    
    // if there are no search results, than create a new contact
    // or if exists result, get contact 
    // add a log message to an existing contact
    // else show error message
    if($search_result->getTotal() == 0){
      // create a new contact
      try{
        $contact = $hubSpotHelper->createContact($email, $first_name, $last_name);
      } catch(Exception $e){
        $logger->error('User ' . \Drupal::currentUser()->id() . ' entered wrong data. Email: ' . $email);
        $messanger->addMessage('Invalid entered parameters!!', 'error');
        return;
      }
    } else if ($search_result->getResults()){
      //get contact
      $contact = $search_result->getResults()[0];
    } else{
      $this->errorSendMail($email, $messanger, $logger);
      return;
    }
    // get result of log
    $log = $hubSpotHelper->sendMessageLogToContact($contact, $subject, $body['#markup']);

    //if ok, send mail
    //else show error message
    if($log){
      //get mail service
      $mail_handler = \Drupal::service('test_task_module.mail_handler');

      //send message
      $result = $mail_handler->sendMail($email, $subject, $body);

      // check, maybe something went wrong with mail delivery
      if($result){
        $messanger->addMessage('Message was sent.');
        \Drupal::logger('test_task_module')->notice('Send message to ' . $email . ' by user with uid ' . \Drupal::currentUser()->id());
      } else{
        $this->errorSendMail($email, $messanger, $logger);
      }
    } else{
      $this->errorSendMail($email, $messanger, $logger);
    }
  }

  /**
   * Method, that sends an error message and logs it.
   * 
   * @param string email address where there was an attempt to send a letter
   * @param $messanger \Drupal::messenger()
   * @param $logger \Drupal::logger()
   */
  protected function errorSendMail(string $email, $messanger, $logger){
    $logger->error('User ' . \Drupal::currentUser()->id() . ' cannot send mail to ' . $email);
    $messanger->addMessage('Something went wrong, try again later', 'error');
  }
}