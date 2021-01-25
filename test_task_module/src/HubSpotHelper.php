<?php 

namespace Drupal\test_task_module;

use Exception;
use HubSpot\Factory;

/**
 * Class HubSpotHelper. 
 * 
 * Provides operations with the HubSpot services.
 */
class HubSpotHelper{

    /**
     * @var string Contact API key.
     */
    protected $api_key;

    /**
     * @var HubSpot\Discovery HubSpot Connection Object.
     */
    protected $hubSpot;

    /**
     * Constructor
     * 
     * @param string Contact API key.
     */
    public function __construct(string $api_key){
        $this->createConnection($api_key);
    }

    /**
     * Method for checking the validity of a key.
     * 
     * @param string Contact API key.
     * @param object object for initializtion.
     * 
     * @return bool true If there is a connection | false if no connection
     */
    public static function checkApiKey(string $api_key, &$hubSpot = null): bool {
        try{
            $hubSpot = Factory::createWithApiKey($api_key);
        }catch(Exception $e){
            \Drupal::messenger()->addMessage("Wrong API Key");
            return false;
        }
        return true;
    }

    /**
     * Method creates new HubSpot connection.
     * 
     * @param string Contact API key.
     * 
     * @throws Exception if API key is Wrong.
     */
    public function createConnection(string $api_key){
        if(static::checkApiKey($api_key, $this->hubSpot))
            $this->api_key = $api_key;
        else throw new Exception("Wrong API Key!");
    }

   /**
     * Operation create
     *
     * @param  \HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput $simple_public_object_input simple_public_object_input (required)
     *
     * @throws Exception if no hubspot connection.
     * 
     * @return \HubSpot\Client\Crm\Contacts\Model\SimplePublicObject|\HubSpot\Client\Crm\Contacts\Model\Error
     */
    public function createContact($email, $first_name, $last_name ){
        if(!$this->hubSpot){
            throw new Exception("No HubSpot Connection!");
        }

        // @var HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput
        // object, which contains input variables
        // need to create new contact
        $contactInput = new \HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput();
        
        // set contact values
        $contactInput->setProperties([
            'firstname' => $first_name,
            'lastname' => $last_name,
            'email' => $email,
        ]);
        
        // @var HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput
        return $this->hubSpot->crm()->contacts()->basicApi()->create($contactInput);
    }

    /**
     * Search Contact by email.
     * This is the example from @see https://github.com/HubSpot/hubspot-api-php/blob/master/README.md
     *
     * @param string email
     *
     * @return \HubSpot\Client\Crm\Contacts\Model\CollectionResponseWithTotalSimplePublicObject|\HubSpot\Client\Crm\Contacts\Model\Error
     */
    public function searchByEmail(string $email){
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
        return $this->hubSpot->crm()->contacts()->searchApi()->doSearch($searchRequest);
    }

    /**
     * Method create message log to the hubspot contact.
     * 
     * For more information about creation engagements
     *   @see https://legacydocs.hubspot.com/docs/methods/engagements/create_engagement 
     * 
     * @param \HubSpot\Client\Crm\Contacts\Model\SimplePublicObject $contact - HubSpot contact.
     * @param string $subject - message subject.
     * @param string $body - message body.
     * 
     * @return bool true if the log was created or false if not.
     */
    public function sendMessageLogToContact($contact, $subject, $body): bool {
        //get contact properties
        $contact_properities = $contact->getProperties();

        //reqiure engagement array.
        //consists type of Log.
        $engagement = [
            "type" => "tyu",
            "active"=> true,
        ];

        //reqiure associations array.
        //consists contact id.
        $associations = [
            "contactIds" => [$contact->getId()],
        ];

        //reqiure metadata array.
        //consists all information about contact and message.
        $metadata = [
            "to"=>[
                [
                "email"=> $contact_properities['email'],
                "firstName"=> $contact_properities['firstname'],
                "lastName"=> $contact_properities['lastname'],
                ]
            ],
            "subject"=> $subject,
            "html"=> $body,
        ];
        
        //create JSON object for HTTP request
        $json_params = json_encode([
                'engagement' => $engagement,
                'associations' => $associations,
                'metadata' => $metadata,
                'attachments' => [],
        ]);
        
        //url for http request
        $endpoint = 'https://api.hubapi.com/engagements/v1/engagements?hapikey=' . $this->api_key;

        // Parameters of cURL
        $curl_params = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS=> $json_params,
            CURLOPT_URL=>$endpoint,
            CURLOPT_HTTPHEADER=> array('Content-Type: application/json'),
            CURLOPT_RETURNTRANSFER => true,
        ];
        
        //send request to HubSpot and decode answer
        $log = json_decode($this->sendRequire($curl_params));

        //if isset error type return false
        if(isset($log->status)) 
            return false;

        return true;
    }

    /**
     * This method send http request
     * 
     * @param array properties of curl request. Key => name of property, value => value of property.
     *              Array example @see ::sendMessageLogToContact() $curl_params.
     * 
     * @see https://www.php.net/manual/ru/function.curl-setopt-array.php
     * 
     * 
     * @return string result of http request
     */
    protected function sendRequire(array $props = []) {
        $ch = @curl_init();
        if($props){
            @curl_setopt_array($ch, $props);
        }
        return @curl_exec($ch);
    }
}