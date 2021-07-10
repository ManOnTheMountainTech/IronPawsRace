<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    interface Comparable {

        // @return: <0 $this < $other
        //          0 $this == $other
        //          >0 $this > $other
        function compareTo(Comparable $other): int;
    }