<?php
/**
 * Email template for sending customer credentials.
 *
 * @var string $email_heading
 * @var string $username
 * @var string $password
 *
 * @package WC_Dynamic_Sync
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php esc_html_e( 'Thanks for signing up! Your account has been created.', 'wc-dynamic-sync' ); ?></p>

<p><strong><?php esc_html_e( 'Username:', 'wc-dynamic-sync' ); ?></strong> <?php echo esc_html( $username ); ?></p>
<p><strong><?php esc_html_e( 'Password:', 'wc-dynamic-sync' ); ?></strong> <?php echo esc_html( $password ); ?></p>

<p><?php esc_html_e( 'You can log in to your account here:', 'wc-dynamic-sync' ); ?></p>
<p><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php echo esc_html( wc_get_page_permalink( 'myaccount' ) ); ?></a></p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
