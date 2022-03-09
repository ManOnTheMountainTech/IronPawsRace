<?php
    namespace IronPawsLLC;

    defined( 'ABSPATH' ) || exit;
    // Returns a login form if the user is not logged on
    // @return: if not logged in, the logon form
    //          if logged in, null
    class DogDefs {
        const NAME = "dogName";
        const AGE = "dogAge";
        const FORM_ID = "dogFormId";
    }
?>