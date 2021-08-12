<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    class Users {
        CONST KEY_FIRST_NAME = 'first_name';
        CONST KEY_LAST_NAME = 'last_name';

        protected const SEARCH_PARAM = 'meta_key';
        protected const SEARCH_VALUE = 'meta_value';

        static function lookup(?string $login_email_nicename, $first_name, $last_name) {
            //search usertable

            $users;

            if (!is_null($login_email_nicename)) {
                $wp_user_query = new \WP_User_Query(
                    array(
                    'search' => "*{$login_email_nicename}*",
                    'search_columns' => array(
                    'user_login',
                    'user_nicename',
                    'user_email'
                    )
                
                ) );
                $users = $wp_user_query->get_results();
            }

            $users2;

            if (!(is_null($first_name) || is_null($last_name))) {
  
                //search usermeta
                $wp_user_query2 = new \WP_User_Query(
                    array(
                    'meta_query' => array(
                        'relation' => 'OR',
                            array(
                            'key' => 'first_name',
                            'value' => $first_name,
                            'compare' => 'LIKE'
                            ),
                        array(
                            'key' => 'last_name',
                            'value' => $last_name,
                            'compare' => 'LIKE'
                            )
                        )
                    )
                );

                $users2 = $wp_user_query2->get_results();
            }

            if (isset($users)) {
                if (isset($users2)) {
                    $totalusers_dup = array_merge($users,$users2);

                    $totalusers = array_unique($totalusers_dup, SORT_REGULAR);
                    var_debug($totalusers);

                    return $totalusers;
                }

                return $users;
            }

            return $users2;
        }

        static function get(string $key, string $value): string {
            $result = "";
            global $wpdb;

            $search_param = self::SEARCH_PARAM;
            $search_value = self::SEARCH_VALUE;

            // "SELECT user_id FROM {$wpdb->usermeta} WHERE {$search_param} = %s AND {$search_value} = %s"

            $user_ids = $wpdb->get_results(
                $GLOBALS['wpdb']->prepare(
                    "SELECT user_id FROM {$wpdb->usermeta} WHERE {$search_param} = %s AND {$search_value} = %s",
                    $key,
                    $value));

            //var_debug($wpdb->queries);
            if (empty($user_ids)) {
                return "Unable to find {$value}";
            }

            if (is_null($user_ids)) {
                return "Internal error users-1. Please file a bug or contact support.";
            }

            if (\is_array($user_ids) || \is_object($user_ids)) { 
                foreach ( $user_ids as $user_id ) {
                    $result .= '<p>' . $user_id->user_id . '</p>';
                }
            } else {
                return '<p>' . $user_ids->user_id . '</p>';
            } 

            return $result;
        }
    }
?>