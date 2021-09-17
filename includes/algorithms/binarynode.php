<?php
/**
 * This is a class for creating the binary nodes
 */
namespace Algorithms {

  defined( 'ABSPATH' ) || exit;

  require_once plugin_dir_path(__FILE__) . '../wp-defs.php';
  require_once plugin_dir_path(__FILE__) . '../debug.php';

  class BinaryNode { 

      public $data; 
      public $left; 
      public $right; 

      public function __construct($data = NULL) { 
        $this->data = $data; 
        $this->left = NULL; 
        $this->right = NULL; 
      } 

      /**
       * Adds child nodes
       */
      public function addChildren($left, $right) { 
        $this->left = $left;
        $this->right = $right;
      }

      // perform an in-order traversal of the current node
      public function dump() {
          if ($this->left !== null) {
              $this->left->dump();
          }
          var_dump($this->data);
          if ($this->right !== null) {
              $this->right->dump();
          }
      }

      public function walk($function, $arg) {
        if ($this->left !== null) {
            $this->left->walk($function, $arg);
        }
        call_user_func($function, $this->data, $arg);
        if ($this->right !== null) {
            $this->right->walk($function, $arg);
        }
    }
  }
}
?>