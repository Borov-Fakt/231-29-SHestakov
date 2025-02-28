<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'shestakov' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         ',n.(_sd+zR3,hMNn)z4B9v&KpR98}q2NU18T6vvC2:>&NC%*7E(F5^d<if(g63`!' );
define( 'SECURE_AUTH_KEY',  ',(x<wZxH`gn~260$vR2`m.,q|!@3q&UV9zB#nG8.^E&L_p|dO(/#x}+:+Ld~`2^8' );
define( 'LOGGED_IN_KEY',    ' }=}aMDd0{3Q2rbp+qAB^#*+1K2=_nL;Scm~)40lp3A_l$L9@1XvTCs@2xT;+g!r' );
define( 'NONCE_KEY',        '/){7:gaCkM=(R4kgxJzu3B4ynHbow1I#4C6@IV,x|[?3)8?3!X5|*H}%lj(HNRYn' );
define( 'AUTH_SALT',        'MAk[&.@pCYApd(ZSX&ePoaha)!K1T6Aa%>V1:A+/l$p~VrO%MAWq.lt8%d^)rb>6' );
define( 'SECURE_AUTH_SALT', '%X+$`c.1Q(X33gtrs!{^gG;6~HUj_~|o~=|FN!}kTvK_uLa[DzuD1=hGP7k4qO=.' );
define( 'LOGGED_IN_SALT',   '+XDU.(~.XMu#Y&/Z]yYq+myC;`@-#p0sIt#?{#o3?tv[-Ga1% Q`9;H}{-qX.8Kg' );
define( 'NONCE_SALT',       'uN:!^Pk.QsTJ,-N(4uk6L=9t?HOGu+!<m?3GXWi:Wi.B9o^&0xei_mLp  v0:F7t' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
