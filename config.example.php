<?php // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

/**
 * MOODLE COMPOSER
 *
 * Import utils configuration file.
 */
require_once(__DIR__ . '/../config/utils.php');

//=========================================================================
// 1. DATABASE SETUP
//=========================================================================
$CFG->dbtype    = get_env('MOODLE_DBTYPE', 'mysqli');
$CFG->dblibrary = get_env('MOODLE_DBLIBRARY', 'native');
$CFG->dbhost    = get_env('MOODLE_DBHOST', 'localhost');
$CFG->dbname    = get_env('MOODLE_DBNAME');
$CFG->dbuser    = get_env('MOODLE_DBUSER');
$CFG->dbpass    = get_env('MOODLE_DBPASS');
$CFG->prefix    = get_env('MOODLE_DBPREFIX', 'mdl_');
$CFG->dboptions = array(
    'dbpersist' => 0,
    'dbport' => get_env('MOODLE_DBPORT', ''),
    'dbsocket' => '',
    'dbcollation' => get_env('MOODLE_DBCOLLATION', 'utf8mb4_unicode_ci'),
);

//=========================================================================
// 2. WEB SITE LOCATION
//=========================================================================
$CFG->wwwroot   = get_env('MOODLE_WWWROOT');

//=========================================================================
// 3. DATA FILES LOCATION
//=========================================================================
$CFG->dataroot  = __DIR__ . '/../' . get_env('MOODLE_DATAROOT');

/**
 * MOODLE COMPOSER
 *
 * Import extra configuration file (if exists)
 */
$config_extras = __DIR__ . '/../config/extras.php';

if (file_exists($config_extras)) {
    require_once($config_extras);
}

unset($config_extras);

require_once(__DIR__ . '/lib/setup.php');

// There is no PHP closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
