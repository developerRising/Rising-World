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
define('DB_NAME', 'agechecked');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '');

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
define('AUTH_KEY',         'X>,~`grWpMD*LT+*kw<.`C8XnHD`zA%![0C!<a`D:-Ny_I^z!(jSE,Elp>~TsG@(');
define('SECURE_AUTH_KEY',  'Nq0|h9S*kPO9i2n%xJ,j*V&^BhZT)K.4L?._cFwF7?/OsLhj*umj5eUEE&wGj&|H');
define('LOGGED_IN_KEY',    '@Ng}}Wi-7@ Ol(~SoOSF9!n6EC.(])-!tHkeW~2aHAF(HT2H;_t,4;r{@x x%Q;~');
define('NONCE_KEY',        '`m>j)NEN_Ja~j}pFmJ]SClD3qGOi!+*nR2_FV/5@T?#O^b,si&},lk=Y>3&1t:7?');
define('AUTH_SALT',        'x]{qf:cd3r]oTwZr:[{OV{Ce+9Jlv~v3s6PHX))/6ib%=Lq1*`|[t%Y,8?>-~ACB');
define('SECURE_AUTH_SALT', 'lGim:cM|t @Nk22B8#EEuuj@mQOQzSb7&UO@?Q4Iumhc}r9`T5VjMg%aN:7wbBny');
define('LOGGED_IN_SALT',   '3-8XRmHRNQn1ci0};?]C_{,W1|d$QRJUHTH<QV{_BO>{OQ^V:O3n~dpP:KV5X7Ad');
define('NONCE_SALT',       '}@&OkEAW2&*.RG;${n+%@3^GE kYGyG$LY&F1c`[QuLCil>4;D.!h7EW_jeS_2W$');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

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
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
