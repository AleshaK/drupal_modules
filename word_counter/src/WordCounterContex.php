<?php

namespace Drupal\word_counter;

use Drupal\word_counter\Form\WordCounterSettingsForm;

/**
 * Class WordCounterSettingsForm. 
 * 
 * Consists contexts and methods to store count words in body;
 */
class WordCounterContex {

  /**
   * Array with key = node id, value = count words body.
   *
   * @var array
   */
    private $count_words = [];
    
    /**
     * Constant html open tag variable.
     *
     * @var string
     */
    private const OPEN_TAG = '<';

    /**
     * Constant html close tag variable.
     *
     * @var string
     */    
    private const CLOSE_TAG = '>';

    /**
     * Configuration setting array id.
     *
     * @var string
     */
    public const SETTINGS_ARRAY = 'word_counter.settings_array';

    /**
     * Private constructor.
     */
    private function __construct(array $array = []) {
        $this->count_words = $array;
    }

    /**
     * Method to get WordCounter Object.
     *
     * @return Drupal\word_counter\WordCounter
     */
    public static function getInstance(){
        $config = \Drupal::service('config.factory')->getEditable(WordCounterSettingsForm::SETTINGS);
        $instance = $config->get(static::SETTINGS_ARRAY);
        if($instance == null){
            $instance = [];
            $config->set(static::SETTINGS_ARRAY, $instance)
                ->save();    
        }
        $obj = new WordCounterContex($instance);
        return $obj;
    }

    /**
     * Function that returns config state.
     * 
     * @return bool 
     *     Current config state.
     */
    public static function getState(){
        $config = \Drupal::config(WordCounterSettingsForm::SETTINGS);
        return $config->get(WordCounterSettingsForm::SETTING_TURN);
    }

    /**
     * Function that clear configuration in Drupal.
     */
    public static function clear() {
        $config = \Drupal::service('config.factory')->getEditable(WordCounterSettingsForm::SETTINGS);
        $config->set('word', null)
               ->save();    
    }

    /**
     * Method wich count words in string.
     * 
     * @param string $str 
     *    String in HTML format (like <p>Hello World!</p>).
     * 
     * @return int
     *    Int value - count words in string.
     */
    public static function getWordCount(string $str) {
        if($str == null || $str == '') return 0;
        // variables for find tags
        $open_tag_position = 0;
        $close_tag_position = 0;
        $next_open_tag_position = 0;

        // loop, in which all tags replaced on ' '.
        while(true){
            $open_tag_position = strpos($str, self::OPEN_TAG, $open_tag_position);

            if($open_tag_position === false) break;

            $close_tag_position = strpos($str, self::CLOSE_TAG, $open_tag_position);
            $next_open_tag_position = strpos($str, self::OPEN_TAG, $open_tag_position+1);
            
            if($next_open_tag_position !== false && $next_open_tag_position < $close_tag_position)
                $open_tag_position = $next_open_tag_position;
            for($i = $open_tag_position; $i<=$close_tag_position;$i++){
                $str[$i] = '\n';
            }
            $open_tag_position = $close_tag_position;
        }
        // Find count words. 
        $str_arr = explode (' ' , $str);
        $count_words = 0;
        for($i = 0; $i < count($str_arr); $i++){
            if($str_arr[$i]!=''){
                $count_words++;
            }
        }
        return $count_words;
    }

    /**
     * Function delete value from array and call deleteFromDatabase() method.
     * 
     * @param int $nid 
     *    Node id.
     */
    public static function delete(string $nid) {
        $counter = static::getInstance();
        if(!isset($counter->count_words[$nid])){
            unset($counter->count_words[$nid]);
            $counter->saveConfiguration();
        }
    }

   /**
     * Function that returns selected content types.
     * 
     * @return array 
     *      Selected content types.
     */
    public static function getTypes(){
        $config = \Drupal::config(WordCounterSettingsForm::SETTINGS);
        return $config->get(WordCounterSettingsForm::SETTING_NODE_TYPES);
    }
    
   /**
     * Function that returns config state.
     * 
     * @return bool 
     *     Current config state.
     */
    public static function checkTypes(string $type){
        $types = static::getTypes();
        
        return (bool) $types[$type];
    }

    /**
     * Function that returns count word in body in node with nid = $nid.
     * 
     * @param int $nid 
     *     Node id.
     * 
     * @param string $str
     *    Body value.
     * 
     * @return int 
     *     Count word in node body.
     */
    public function get(string $nid = null, string $body = null) {
        if($nid != null){
            $count = static::getWordCount($body);
            if(!isset($this->count_words[$nid]) || $this->count_words[$nid] != $count)
                $this->count_words[$nid] = $count;
            $this->saveConfiguration();
            return $this->count_words[$nid];
        }
    }

    /**
     * Function that saves configuration in Drupal.
     */
    public function saveConfiguration() {
        \Drupal::service('config.factory')->getEditable(WordCounterSettingsForm::SETTINGS)
                ->set(static::SETTINGS_ARRAY, $this->count_words)
                ->save();
    }
}
