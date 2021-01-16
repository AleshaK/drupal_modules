<?php

namespace Drupal\word_counter;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\word_counter\Form\WordCounterSettingsForm;

/**
 * Class WordCounterSettingsForm. 
 * 
 * Consists contexts and methods to store count words in body;
 */
class WordCounterContex {

    private $count_words;

    private const OPEN_TAG = '<';

    private const CLOSE_TAG = '>';

    private const NODE_BODY_TABLE = 'node__body';

    private const WORD_COUNTER_TABLE = 'word_counter__words';

    private const FIELD_NID = 'nid';

    private const FIELD_WORD = 'words';

    private const FIELD_NID__NODE_BODY_TABLE = 'entity_id';

    private const FIELD_BODY_VALUE__NODE_BODY_TABLE = 'body_value';

    private static $types = ['article']; 

    public function __construct(array &$arr = []) {
        $this->count_words = $arr;
    }

    /**
     * Одиночки не должны быть клонируемыми.
     */
    protected function __clone() { }

    /**
     * Одиночки не должны быть восстанавливаемыми из строк.
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    public static function getInstance(){
        $config = \Drupal::service('config.factory')->getEditable(WordCounterSettingsForm::SETTINGS);
        $instance = $config->get('word');
        if($instance == null){
            \Drupal::messenger()->addMessage('create arr');
            $instance = [];
            static::arrayInitialization($instance);
            $config->set('word', $instance)
                   ->save();    
        }
        $obj =  new WordCounterContex($instance);
        return $obj;
      }
    
    /**
     * 
     * 
     */
    public static function getWordCount(string $str) {
        $open_tag_position = 0;
        $close_tag_position = 0;
        $next_open_tag_position = 0;
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
        $str_arr = explode (' ' , $str);
        $count_words = 0;
        for($i = 0; $i < count($str_arr); $i++){
            if($str_arr[$i]!=''){
                $count_words++;
            }
        }
        return $count_words;
    }

    public static function clear() {
        $config = \Drupal::service('config.factory')->getEditable(WordCounterSettingsForm::SETTINGS);
        $config->set('word', null)
               ->save();    
    }

    
   /**
    * @return array
    *   like [
    *       count=> Object[
    *          'id' => id,
    *          'nid' => nid,
    *          'words' => words,
    *          ]
    *       ]
    */
    public static function getFromWordsTable(){
        $database = \Drupal::database();
        //  dynamic query 
        $query = $database->select(self::WORD_COUNTER_TABLE, 'wct');
        $query->fields('wct', ['id', self::FIELD_NID, self::FIELD_WORD]);
        $result = $query->execute()->fetchAll();
        for($i = 0; $i < count($result); $i++){
            $result[$i] = (array) $result[$i];
        }
        return $result;
    }

   /**
    * @return array
    *   like [
    *       count=> Object[
    *          'entity_id' => entity_id,
    *          'bundle' => bundle,
    *          'body_value' => body_value,
    *          ]
    *       ]
    */
    public static function getFromNodeBodyTable(){
        $database = \Drupal::database();
        //  dynamic query 
        $query = $database->select(self::NODE_BODY_TABLE, 'node');
        $query->fields('node', [self::FIELD_NID__NODE_BODY_TABLE])
              ->fields('node', ['bundle'])
              ->fields('node', [self::FIELD_BODY_VALUE__NODE_BODY_TABLE]);
        return $query->execute()->fetchAll();
    }

    /**
     * 
     * 
     */
    public static function arrayInitialization(array &$array) {
        \Drupal::messenger()->addMessage('before');
        $words = static::getFromWordsTable();
        $nodes = static::getFromNodeBodyTable();
        if(empty($words) && empty($nodes)){
            return;
        }
        //ksm($nodes);
        $add = [];
        $update = [];
        $is = [];

        // for($i = 0; $i<count($nodes); $i++){

        // }
        foreach($nodes as $i => $obj){
            if(!isset($words[$obj->entity_id])){
                $add[] = [
                    'nid' => $obj->entity_id,
                    'words' => static::getWordCount($obj->body_value),
                ];
            } else {
                $nid = $obj->entity_id;
                $count = static::getWordCount($obj->body_value);
                if($words[$nid]!=$count){
                    $update[] = [
                        'nid' => $nid,
                        'words' => $count,
                    ];
                }
                $is[] = $nid;
            }
            $a = $i;
        }
        ksm($a);
        ksm(count($add));
        ksm($update);
        ksm($is);
        \Drupal::messenger()->addMessage('after');
        // ksm($result);
        // for($i = 0; $i < count($result); $i++){
        //     //if(in_array($result[$i]->bundle, static::$types)){
        //         $nid = $result[$i]->entity_id;
        //         $body = static::getWordCount($result[$i]->body_value);
        //         $array[$nid] = $body;
        //     //}
        //    // $database_val[$i] = [$nid => $body];
        // }

        // $connection = \Drupal::service('database');
        // $query = $connection->insert(self::WORD_COUNTER_TABLE)
        //                     ->fields([self::FIELD_NID, self::FIELD_WORD]);

        // foreach ($array as $nid => $words) {
        //     if(isset($nid) && isset($words)){
        //         $query->values([
        //             'nid' =>$nid,
        //             'words' => $words,
        //             ]);
        //     }
        // }
        // $query->execute();
    }

    /**
     * 
     * 
     */
    public function set(int $nid, string $str) {
        $values = [];
        $count_words = $this->getWordCount($str);
        $this->count_words[$nid] = $count_words;

        if(!isset($this->count_words[$nid])){
            \Drupal::messenger()->addMessage('add');
            $values[0][self::FIELD_NID] = $nid;
            $values[0][self::FIELD_WORD] = $count_words;
            $this->count_words[$nid] = $count_words;
            $this->addInDatabase($values);
        }else {
            \Drupal::messenger()->addMessage('edit');
            $values[self::FIELD_NID] = $nid;
            $values[self::FIELD_WORD] = $count_words;
            $this->updateInDatabase($values);
        }
        $this->savecfg($this->count_words);
    }


    /**
     * 
     * 
     */
    public function delete(string $nid) {
        if(!isset($this->count_words[$nid])){
            $this->deleteFromDatabase($nid);
            unset($this->count_words[$nid]);
            $this->savecfg($this->count_words);
        }
    }

    /**
     * 
     * 
     */
    public function get(string $nid) {
        if(isset($this->count_words[$nid]))
            return $this->count_words[$nid];
        return null;
        //return 0;
    }

    /**
     * 
     * 
     */
    public static function addInDatabase(array $values){
        if(!empty($values)){
            $connection = \Drupal::service('database');
            $query = $connection->insert(self::WORD_COUNTER_TABLE)
                                ->fields([self::FIELD_NID, self::FIELD_WORD]);
            foreach ($values as $record) {
                if(isset($record[self::FIELD_NID]) && isset($record[self::FIELD_WORD])){
                    $query->values($record);
                }
            }
            $query->execute();
        }
    }

    public function updateInDatabase(array $values){
        if(!empty($values)){
            $connection = \Drupal::service('database');
            $connection->update(self::WORD_COUNTER_TABLE)
                       ->fields([
                            self::FIELD_NID => $values[self::FIELD_NID],
                            self::FIELD_WORD => $values[self::FIELD_WORD],
                            ])
                       ->execute();
        }
    }

    public static function deleteFromDatabase(int $nid){
        if(isset($nid)){
            $connection = \Drupal::service('database');
            $connection->delete(self::WORD_COUNTER_TABLE)
                        ->condition( self::FIELD_NID, $nid)
                        ->execute();
        }
    } 

    public function getState(){
        $config = \Drupal::config(WordCounterSettingsForm::SETTINGS);
        return $config->get(WordCounterSettingsForm::SETTING_TURN);
    }

    public function savecfg($arr){
        \Drupal::messenger()->addMessage('saving');
        \Drupal::service('config.factory')
            ->getEditable(WordCounterSettingsForm::SETTINGS)
            ->set('word', $arr)
            ->save();    
    }
}
