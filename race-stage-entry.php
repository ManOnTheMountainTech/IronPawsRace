<?php
        defined( 'ABSPATH' ) || exit;

        namespace IronPaws;

        define("FORM_NAME", "RSE_Form");
    
        require_once plugin_dir_path(__FILE__) . 'autoloader.php';
        require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
        require_once plugin_dir_path(__FILE__) . 'includes/debug.php';

        class Race_Stage_Entry { 


            public WP_User $user;

            public function __construct() {
                $logon_form = ensure_loggedon();
                if (!is_null($logon_form)) {
                    return $logon_form;
                }
        
                $user = wp_get_current_user();
            }

            static function do_shortcode() {
                return (new Race_Stage_Entry())->makeHTMLRaceStageEntry();
            }

            function makeHTMLRaceStageEntry() {
                $stage = 1;

                $teams_selections_html = <<<FORM_HEADER
                    <form method="get" id="{FORM_NAME}" action="race-stage-entry">
                        Race Stage: <strong>{$stage}</strong><br><br>

                        <div class="border">
                            <div>
                                <div class="hide-overflow def-pad">
                                    <label for="mileage">Mileage:</label>
                                    <input type="number" id="mileage" name="mileage">
                                </div> 
                            </div>  
                            <div class="hide-overflow disp-flex">
                                <div class="def-pad">
                                    <label for="hours">Hours:</label>
                                    <input type="number" id="hours" name="hours" class="disp-block">
                                </div>
                                <div class="def-pad">
                                    <label for="minutes">Minutes:</label>
                                    <input type="number" id="minutes" name="minutes" class="disp-block">
                                </div>
                            </div>
                        </div>
                        <br>
                        <button type="submit">Record to server.</button>
                    </form>
                FORM_HEADER;

                return $teams_selections_html;
            }
        }
?>