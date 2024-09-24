
<div class="emailoctopus-welcome-container card">
    <div class="emailoctopus-welcome">
        <img src="<?php echo esc_url( $utils->get_logo_url() ); ?>"
            alt="<?php esc_attr_e( 'EmailOctopus logo', 'emailoctopus' ); ?>"
            width="200"
        >
        <h2>
            <?php esc_html_e( 'Email marketing made easy', 'emailoctopus' ); ?>
        </h2>

        <p>
            <?php esc_html_e( 'To set up your forms, create an EmailOctopus account or connect an existing one.', 'emailoctopus' ); ?>
        </p>

        <div class="emailoctopus-welcome-actions">
            <a class="button button-large button-primary" href="https://emailoctopus.com/account/sign-up?utm_source=wordpress_plugin&utm_medium=referral&utm_campaign=welcome_banner" target="_blank" rel="noopener">
                <?php esc_html_e( 'Create an account for free', 'emailoctopus' ); ?>
            </a>
            <a class="button button-large button-primary" href="<?php echo admin_url( 'admin.php?page=emailoctopus-settings' ); ?>">
                <?php esc_html_e( 'Connect an existing account', 'emailoctopus' ); ?>
            </a>
        </div>
    </div>
    <svg class="emailoctopus-welcome-wave" preserveAspectRatio="none" viewBox="0 0 1366 73" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M158.5 0C417.5 0 635.5 59.5 889.5 59.5C1090.05 59.5 1288.6 42.2904 1366 33.3495V73H0V7.5C44.5897 2.48657 98.3282 0 158.5 0Z"></path>
    </svg>
</div>
