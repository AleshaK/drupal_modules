<?php

namespace Drupal\test_task_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Exception;
use HubSpot\Factory;
use HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput;

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
    if(!$form_state->getValue('email') || !preg_match('/\w+@\w+\.\D{2,3}$/', $form_state->getValue('email'))){
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
    //get all values from form
    $first_name = $form_state->getValue('first_name');
    $last_name = '';
    $email = $form_state->getValue('email');
    $subject = new TranslatableMarkup($form_state->getValue('subject'));

    if($form_state->getValue('last_name'))
      $last_name = ' ' . $form_state->getValue('last_name');
    $body = $form_state->getValue('message')['value'] . 
         '<p> From ' . $first_name . $last_name .'.</p>';
    $body = [
      '#markup' => $body,
    ];

    $mail_handler = \Drupal::service('test_task_module.mail_handler');
    $result = $mail_handler->sendMail($email, $subject, $body);
    $messanger = \Drupal::messenger();
    if($result){
      //API key from HubSpot
      $api_key = '548eb958-5237-44e4-8d6b-de7694245972';

      // HubSpot instance
      $hubSpot = \HubSpot\Factory::createWithApiKey($api_key);

      // search contact by email
      // this is an example from https://github.com/HubSpot/hubspot-api-php/blob/master/README.md
      $filter = new \HubSpot\Client\Crm\Contacts\Model\Filter();
      $filter
          ->setOperator('EQ')
          ->setPropertyName('email') 
          ->setValue($email); 

      $filterGroup = new \HubSpot\Client\Crm\Contacts\Model\FilterGroup();
      $filterGroup->setFilters([$filter]);

      $searchRequest = new \HubSpot\Client\Crm\Contacts\Model\PublicObjectSearchRequest();
      $searchRequest->setFilterGroups([$filterGroup]);

      // @var CollectionResponseWithTotalSimplePublicObject $contactsPage
      $contactsPage = $hubSpot->crm()->contacts()->searchApi()->doSearch($searchRequest);

      // if there are no search results, than create a new contact
      
      if($contactsPage->getTotal() == 0){
        // @var HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput
        // object, which contains input variables
        // need to create new contact
        $contactInput = new \HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput();

        $contactInput->setProperties([
          'firstname' => $first_name,
          'lastname' => $last_name,
          'email' => $email,
        ]);

        // @var HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput
        $contact = $hubSpot->crm()->contacts()->basicApi()->create($contactInput);

      }
      \Drupal::logger('test_task_module')->notice('Send message to ' . $email);
      $messanger->addMessage('Message has been sent.');
    } else{
      $messanger->addMessage('Message something went wrong.', 'error');
    }
  }
}