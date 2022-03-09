<?php
    namespace IronPawsLLC;

    defined( 'ABSPATH' ) || exit;

    interface Container_HTML_Pattern {
        function makeOpeningHTML(?array $params = null);
        function makeListItemHTML(?array $params = null);
        function makeClosingHTML(?array $params = null);
    }
?>