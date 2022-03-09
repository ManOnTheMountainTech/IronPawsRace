<?php
     namespace IronPawsLLC;

     defined( 'ABSPATH' ) || exit;

     class Race_Stage_Common {
          public function __construct()
          {
               $this->user_error_msg = __("A failure occured writing this team race stage entry.");    
          }

          public ?string $outcome = null;
          public int $race_stage = 0;
          public int $run_class_id = -1;
          public string $user_error_msg;
     }
?>