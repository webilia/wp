<?php
namespace Webilia\WP;

use WP_Error;

/**
 * Class User
 * @package WordPress
 */
class User
{
    /**
     * Constructor method
     */
	public function __construct()
    {
	}

    /**
     * @param string $user_login
     * @param string $user_email
     * @param string $password
     * @return int|WP_Error
     */
    public static function register(string $user_login, string $user_email, string $password = null)
    {
        // Password
        if(!$password) $password = wp_generate_password(12);

        // Errors
        $errors = new WP_Error();
        $sanitized_user_login = sanitize_user($user_login);

        /*
         * Filters the email address of a user being registered.
         *
         * @since 2.1.0
         *
         * @param string $user_email The email address of the new user.
         */

        $user_email = apply_filters('user_registration_email', $user_email);

        // Check the username.
        if($sanitized_user_login === '')
        {
            $errors->add('empty_username', esc_html__("<strong>Error</strong>: Please enter a username."));
        }
        else if(!validate_username($user_login))
        {
            $errors->add(
                'invalid_username',
                esc_html__("<strong>Error</strong>: This username is invalid because it uses illegal characters.")
            );
            $sanitized_user_login = '';
        }
        else if(username_exists($sanitized_user_login))
        {
            $errors->add('username_exists', esc_html__('<strong>Error</strong>: This username is already registered.'));
        }
        else
        {
            // This filter is documented in wp-includes/user.php

            $illegal_user_logins = (array) apply_filters('illegal_user_logins', []);
            if(in_array(strtolower($sanitized_user_login), array_map('strtolower', $illegal_user_logins), true))
            {
                $errors->add('invalid_username', esc_html__("<strong>Error</strong>: Sorry, Username is not allowed."));
            }
        }

        // Check the email address.
        if($user_email === '')
        {
            $errors->add('empty_email', esc_html__("<strong>Error</strong>: Please type your email address."));
        }
        else if(!is_email($user_email))
        {
            $errors->add('invalid_email', esc_html__("<strong>Error</strong>: The email address isn't correct."));
            $user_email = '';
        }
        else if(email_exists($user_email))
        {
            $errors->add('email_exists', esc_html__('<strong>Error</strong>: This email is already registered.'));
        }

        /*
         * Fires when submitting registration form data, before the user is created.
         *
         * @since 2.1.0
         *
         * @param string   $sanitized_user_login The submitted username after being sanitized.
         * @param string   $user_email           The submitted email.
         * @param WP_Error $errors               Contains any errors with submitted username and email,
         *                                       e.g., an empty field, an invalid username or email,
         *                                       or an existing username or email.
         */

        do_action('register_post', $sanitized_user_login, $user_email, $errors);

        /*
         * Filters the errors encountered when a new user is being registered.
         *
         * The filtered WP_Error object may, for example, contain errors for an invalid
         * or existing username or email address. A WP_Error object should always be returned,
         * but may or may not contain errors.
         *
         * If any errors are present in $errors, this will abort the user's registration.
         *
         * @since 2.1.0
         *
         * @param WP_Error $errors               A WP_Error object containing any errors encountered
         *                                       during registration.
         * @param string   $sanitized_user_login User's username after it has been sanitized.
         * @param string   $user_email           User's email.
         */

        $errors = apply_filters('registration_errors', $errors, $sanitized_user_login, $user_email);

        // Return Errors
        if($errors->has_errors()) return $errors;

        $user_id = wp_create_user($sanitized_user_login, $password, $user_email);
        if(!$user_id || is_wp_error($user_id))
        {
            $errors->add('registerfail',
                sprintf(
                    esc_html__('Couldn\'t register you. please contact the <a href="mailto:%s">site admin</a>!'),
                    (string) get_option('admin_email')
                )
            );

            return $errors;
        }

        // Set up the password change nag.
        update_user_option($user_id, 'default_password_nag', true, true);

        /*
         * Fires after a new user registration has been recorded.
         *
         * @since 4.4.0
         *
         * @param int $user_id ID of the newly registered user.
         */

        do_action('register_new_user', $user_id);

        return $user_id;
    }

    /**
     * @param int $user_id
     * @return void
     */
    public static function login(int $user_id): void
    {
        wp_clear_auth_cookie();
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
    }
}
