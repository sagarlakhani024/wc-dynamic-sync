<?php
/**
 * Custom WooCommerce Email - Customer Credentials
 *
 * @package WC_Dynamic_Sync
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Email_Customer_Credentials' ) ) {

	/**
	 * Class WC_Email_Customer_Credentials
	 *
	 * Sends login credentials to newly created users.
	 */
	class WC_Email_Customer_Credentials extends WC_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_credentials';
			$this->title          = __( 'Customer Credentials', 'wc-dynamic-sync' );
			$this->description    = __( 'This email sends login credentials to newly created customers.', 'wc-dynamic-sync' );
			$this->customer_email = true;

			$this->template_html  = 'emails/customer-credentials.php';
			$this->template_plain = 'emails/plain/customer-credentials.php';

			$this->placeholders = array(
				'{username}' => '',
				'{password}' => '',
			);

			add_action( 'wc_dynamic_send_customer_credentials_notification', array( $this, 'trigger' ), 10, 2 );

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Trigger the email.
		 *
		 * @param int    $user_id  User ID.
		 * @param string $password User password.
		 */
		public function trigger( $user_id, $password ) {
			if ( ! $user_id || ! $password ) {
				return;
			}

			$user = get_user_by( 'ID', $user_id );

			if ( ! $user ) {
				return;
			}

			$this->recipient                  = $user->user_email;
			$this->placeholders['{username}'] = $user->user_login;
			$this->placeholders['{password}'] = $password;

			if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
				return;
			}

			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		/**
		 * Get the email subject.
		 *
		 * @return string
		 */
		public function get_subject() {
			return __( 'Your new account details', 'wc-dynamic-sync' );
		}

		/**
		 * Get HTML content of the email.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'email_heading' => $this->get_heading(),
					'username'      => $this->placeholders['{username}'],
					'password'      => $this->placeholders['{password}'],
					'email'         => $this,
				),
				'',
				plugin_dir_path( __FILE__ ) . '../../templates/'
			);
		}

		/**
		 * Get plain content version of the email.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return sprintf(
				/* translators: 1: Username, 2: Password */
				__( "Your account has been created.\nUsername: %1\$s\nPassword: %2\$s\n", 'wc-dynamic-sync' ),
				$this->placeholders['{username}'],
				$this->placeholders['{password}']
			);
		}

		/**
		 * Get default heading.
		 *
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Welcome to our store!', 'wc-dynamic-sync' );
		}
	}
}
