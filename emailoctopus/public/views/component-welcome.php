<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lobster&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">


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
</div>
