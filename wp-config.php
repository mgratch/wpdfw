<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME','marcgrat_wp688_st2');

/** MySQL database username */
define('DB_USER','marcgrat_st2');

/** MySQL database password */
define('DB_PASSWORD','eMXu70MlVG7Jdgd9una1');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'mljeuxoda7krgdaq7gt6kto2otjlpfvsfpih8re1mxtqmrfxlzyysisbdf3jdgqs');
define('SECURE_AUTH_KEY',  'j9odnwujwroahuv56ecx59xhfulhisr34tksijhcwrtuj4pjhezhrnopml2ir70u');
define('LOGGED_IN_KEY',    'ggepkhwmfzajuzfifw10j7watskjxw32yoe2gsbimhweybfsm6dqhxp2wknszwuz');
define('NONCE_KEY',        'oxplvufopxahnhmttrv3nqbyqlsjwmnefcihcasjommot2xwwa8bokn6ktlzepus');
define('AUTH_SALT',        'cwn7nn67pvdsl5vo3poktuilvolrlxyxdsfa0ydabx7zoiok0urfanz2cpxspvxj');
define('SECURE_AUTH_SALT', 'k0cjtk0ijfwhkrfvwch4puophqsvmo92mezxuwnknyzccjunois5yfyonoybqtqm');
define('LOGGED_IN_SALT',   'wq8eecsj1wcggmmjupvpn9cvx4vd3glfumylc31wc9kk4gigtiq6dvkh6634o230');
define('NONCE_SALT',       'oxidrke6ne0aehy1ilf1nt7tm3yilsgk07ptu6fuif8jt1ocwrjehcmxwzbogdkb');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wpqk_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', true);
define('WP_DEBUG_DISPLAY', true);
define('WP_DEBUG_LOG', true);
define( 'WP_MEMORY_LIMIT', '128M' );
define( 'WP_AUTO_UPDATE_CORE', false );

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
