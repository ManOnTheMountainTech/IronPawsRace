<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;
    // Returns a login form if the user is not logged on
    // @return: if not logged in, the logon form
    //          if logged in, null
    class Race_Definition {
        const CORE_STAGES = 0;
        const CORE_MASTER_NUM_DAYS_PER_STAGE = 1;
        const CORE_RACE_TYPE = 2;

        const TIMED = 'timed';
        const MILEAGE = 'mileage';
    }
?>