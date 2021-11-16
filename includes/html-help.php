<?php
    namespace IronPaws;
    
    defined( 'ABSPATH' ) || exit;

    //require_once plugin_dir_path(__FILE__) . 'autoloader.php';

    class HTML_Help { 
        public array $http_method;
        public array $query_args;

        public static $yes_no_qps;

        function __construct(array $query_args, array $http_method) {
            $this->http_method = $http_method;
            $this->query_args = $query_args;
            Strings::init();
        }

        function validate_arg(int $idx) {
            return array_key_exists($this->query_args[$idx], $this->http_method);
        }

        // Generates a labeled entry in a form
        function form_id_and_label(int $idx, string $user_string): string {
                $id = $this->query_args[$idx];

            return
                "<label for=\"{$id}\">{$user_string}</label>\n
                <input id=\"{$id}\" name=\"{$id}\"";
        }

        static function makeHTMLOptionString(string $value, string $description) {
            return <<<FORM_OPTION
              <option value="{$value}">{$description}</option>
            FORM_OPTION;
          }

        static function makeSelect(string $id, string $user_label, array $options) {
            $html = "<label for=\"{$id}\">$user_label</label>\"
                \"<select id=\"{$id}\" name=\"{$id}\" class=\"def-pad\">\n";

            foreach ($options as $value => $description) {
                $html .= self::makeHTMLOptionString($value, $description);
            }

            $html .= '</select>';
            return $html;
        }
      
        static function makeHTMLYesNoOptionString(string $id, string $user_label) {
            $html = <<<HTML
                <label for="{$id}">$user_label</label>
                <select id="{$id}" name="{$id}">
            HTML;
        
            $html .= Html_Help::makeHTMLOptionString($id, Strings::$USER_YES);
            $html .= Html_Help::makeHTMLOptionString($id, Strings::$USER_NO);
        
            $html .= '</select>';
        return $html;
        }

                // Validates that all the arguments are present for a timed race.
        // @param: array -> $submit_args -> The array that corresponds to the
        //  HTTPS method used.
        //  returns:
        //  true = all arguments present
        //  false = no arguments matched, so moove on to the next one.
        // @throws  \Exception -> a user friendly exception.
        function validateQueryArgs(): bool {
            $foundAQueryArg = false;
            $incomplete = false;

            foreach($this->query_args as $arg) {
                if (array_key_exists($arg, $this->http_method)) {
                    $foundAQueryArg = true;
                } else {
                    $incomplete = true;
                }
            }

            if ($foundAQueryArg) {
                if ($incomplete) {
                    return User_Visible_Exception_Thrower::throwErrorCoreException(
                        __("Not all query arguments were provided."), 0);
                }
                else {
                    return true;
                }
            }
            else {
                return false;
            }

            return false;
        }
    }
?>