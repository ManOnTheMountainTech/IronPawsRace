<?php
    namespace IronPaws;
    
    defined( 'ABSPATH' ) || exit;

    //require_once plugin_dir_path(__FILE__) . 'autoloader.php';

    class HTML_Status {

        public int $status;
        public string $html;

        function __construct(string $html_in = "", int $status_in = 0)
        {
            $this->html = $html_in;
            $this->status = $status_in;
        }

        // The returned html is the first part of a form. The </select> and </form>
        // tags need to be supplied.
        const STATUS_TRY_NEXT = 0;
        
        // The returned html closed html. No closing HTML tags need to be supplied.
        const STATUS_DONE = 1;
    }
?>