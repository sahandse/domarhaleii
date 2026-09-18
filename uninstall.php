<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }
// Security data is intentionally retained on uninstall to avoid unexpectedly weakening accounts.
// Site owners can remove user meta manually if permanent deletion is desired.
