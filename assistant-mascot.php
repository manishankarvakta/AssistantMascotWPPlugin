<?php
/**
 * Plugin Name: Assistant Mascot
 * Plugin URI: https://example.com/assistant-mascot
 * Description: A WordPress plugin that displays a red square with "Plugin active" text on the frontend and provides an admin area.
 * Version: 1.0.0
 * Author: Manishankar Vakta
 * Author URI: https://techsoulbd.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: assistant-mascot
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package AssistantMascot
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ASSISTANT_MASCOT_VERSION', '1.0.0');
define('ASSISTANT_MASCOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ASSISTANT_MASCOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ASSISTANT_MASCOT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once ASSISTANT_MASCOT_PLUGIN_DIR . 'includes/class-assistant-mascot.php';
require_once ASSISTANT_MASCOT_PLUGIN_DIR . 'includes/class-assistant-mascot-admin.php';
require_once ASSISTANT_MASCOT_PLUGIN_DIR . 'includes/class-assistant-mascot-frontend.php';

// Include debug file (remove in production)
require_once ASSISTANT_MASCOT_PLUGIN_DIR . 'debug.php';

// Initialize the plugin
function assistant_mascot_init() {
    try {
        $plugin = new Assistant_Mascot();
        $plugin->init();
    } catch (Exception $e) {
        // Log error but don't break the site
        error_log('Assistant Mascot Plugin Error: ' . $e->getMessage());
    }
}
add_action('plugins_loaded', 'assistant_mascot_init');

// Activation hook
register_activation_hook(__FILE__, 'assistant_mascot_activate');
function assistant_mascot_activate() {
    // Create database tables
    assistant_mascot_create_tables();
    
    // Update existing tables if needed
    assistant_mascot_update_tables();
    
    // Check table health
    assistant_mascot_check_table_health();
    
    // Add default options
    add_option('assistant_mascot_settings', array(
        'enabled' => true,
        'position' => 'bottom-right',
        'size' => 'medium',
        'animation_speed' => 'normal'
    ));
    
    // Add AI settings
    add_option('assistant_mascot_ai_settings', array(
        'ai_enabled' => false,
        'ai_provider' => 'openai',
        'api_key' => ''
    ));
    
    // Add 3D model settings
    add_option('assistant_mascot_3d_settings', array(
        'model_file' => 'avater.glb',
        'rendering_quality' => 'medium',
        'scale_factor' => 1.0,
        'background' => false,
        'auto_rotate' => true,
        'wireframe' => false,
        'skeleton' => false,
        'grid' => false,
        'screen_space_pan' => true,
        'point_size' => 1.0,
        'bg_color' => '#5b5b5b',
        'all_animations_enabled' => false,
        'global_animation_speed' => 1.0,
        'loop_animations' => true,
        'synced_animations' => array(),
        'animation_selections' => array(),
        'lighting_intensity' => 1.0,
        'ambient_light' => 0.3,
        'directional_light' => 1.0,
        'light_color' => '#ffffff'
    ));
    
    // Add styles settings
    add_option('assistant_mascot_styles', array(
        'primary_color' => '#ff0000',
        'background_color' => '#ffffff',
        'border_radius' => 8
    ));
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Create database tables for the plugin
 */
function assistant_mascot_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Table for storing animation data
    $table_animations = $wpdb->prefix . 'assistant_mascot_animations';
    $table_models = $wpdb->prefix . 'assistant_mascot_models';
    $table_interactions = $wpdb->prefix . 'assistant_mascot_interactions';
    $table_presets = $wpdb->prefix . 'assistant_mascot_presets';
    
    // Check if tables already exist
    $animations_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_animations'") === $table_animations;
    $models_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_models'") === $table_models;
    $interactions_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_interactions'") === $table_interactions;
    $presets_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_presets'") === $table_presets;
    
    // Only create tables that don't exist
    if (!$animations_exists) {
        $sql_animations = "CREATE TABLE $table_animations (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            model_file varchar(255) NOT NULL,
            animation_name varchar(255) NOT NULL,
            animation_duration decimal(10,3) DEFAULT 0.000,
            is_enabled tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY model_animation (model_file, animation_name)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_animations);
        error_log("Assistant Mascot: Created animations table");
    } else {
        error_log("Assistant Mascot: Animations table already exists");
    }
    
    if (!$models_exists) {
        $sql_models = "CREATE TABLE $table_models (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            model_file varchar(255) NOT NULL,
            model_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size bigint(20) DEFAULT 0,
            upload_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_used datetime DEFAULT CURRENT_TIMESTAMP,
            is_active tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY model_file (model_file)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_models);
        error_log("Assistant Mascot: Created models table");
    } else {
        error_log("Assistant Mascot: Models table already exists");
    }
    
    if (!$interactions_exists) {
        $sql_interactions = "CREATE TABLE $table_interactions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT 0,
            session_id varchar(255) NOT NULL,
            action_type varchar(100) NOT NULL,
            action_data longtext,
            page_url varchar(500),
            user_agent text,
            ip_address varchar(45),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY action_type (action_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_interactions);
        error_log("Assistant Mascot: Created interactions table");
    } else {
        error_log("Assistant Mascot: Interactions table already exists");
    }
    
    if (!$presets_exists) {
        $sql_presets = "CREATE TABLE $table_presets (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            preset_name varchar(255) NOT NULL,
            preset_description text,
            model_file varchar(255) NOT NULL,
            animation_settings longtext NOT NULL,
            display_settings longtext NOT NULL,
            lighting_settings longtext NOT NULL,
            is_public tinyint(1) DEFAULT 0,
            created_by bigint(20) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY preset_name (preset_name),
            KEY model_file (model_file),
            KEY is_public (is_public)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_presets);
        error_log("Assistant Mascot: Created presets table");
    } else {
        error_log("Assistant Mascot: Presets table already exists");
    }
    
    // Insert default model record only if it doesn't exist
    if (!$models_exists || !$wpdb->get_var($wpdb->prepare("SELECT id FROM $table_models WHERE model_file = %s", 'avater.glb'))) {
        $wpdb->insert(
            $table_models,
            array(
                'model_file' => 'avater.glb',
                'model_name' => 'Default Avatar Model',
                'file_path' => ASSISTANT_MASCOT_PLUGIN_URL . 'assets/model/avater.glb',
                'is_active' => 1
            ),
            array('%s', '%s', '%s', '%d')
        );
        error_log("Assistant Mascot: Inserted default model record");
    } else {
        error_log("Assistant Mascot: Default model record already exists");
    }
    
    error_log('Assistant Mascot: Database tables check completed');
}

/**
 * Safely update existing tables with new columns if needed
 */
function assistant_mascot_update_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Check if tables exist before attempting updates
    $table_animations = $wpdb->prefix . 'assistant_mascot_animations';
    $table_models = $wpdb->prefix . 'assistant_mascot_models';
    $table_interactions = $wpdb->prefix . 'assistant_mascot_interactions';
    $table_presets = $wpdb->prefix . 'assistant_mascot_presets';
    
    // Only update tables that exist
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_animations'") === $table_animations) {
        // Check if new columns need to be added
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_animations");
        $column_names = array_column($columns, 'Field');
        
        // Add new columns if they don't exist
        if (!in_array('priority', $column_names)) {
            $wpdb->query("ALTER TABLE $table_animations ADD COLUMN priority int(11) DEFAULT 0 AFTER is_enabled");
            error_log("Assistant Mascot: Added priority column to animations table");
        }
    }
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_models'") === $table_models) {
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_models");
        $column_names = array_column($columns, 'Field');
        
        if (!in_array('version', $column_names)) {
            $wpdb->query("ALTER TABLE $table_models ADD COLUMN version varchar(50) DEFAULT '1.0' AFTER model_name");
            error_log("Assistant Mascot: Added version column to models table");
        }
    }
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_presets'") === $table_presets) {
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_presets");
        $column_names = array_column($columns, 'Field');
        
        if (!in_array('tags', $column_names)) {
            $wpdb->query("ALTER TABLE $table_presets ADD COLUMN tags text AFTER preset_description");
            error_log("Assistant Mascot: Added tags column to presets table");
        }
    }
    
    error_log('Assistant Mascot: Table updates completed');
}

/**
 * Safely check and repair table structures
 */
function assistant_mascot_check_table_health() {
    global $wpdb;
    
    $tables_to_check = array(
        $wpdb->prefix . 'assistant_mascot_animations',
        $wpdb->prefix . 'assistant_mascot_models',
        $wpdb->prefix . 'assistant_mascot_interactions',
        $wpdb->prefix . 'assistant_mascot_presets'
    );
    
    $health_report = array();
    
    foreach ($tables_to_check as $table_name) {
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            // Check table structure
            $result = $wpdb->get_row("CHECK TABLE $table_name");
            if ($result && $result->Msg_text === 'OK') {
                $health_report[$table_name] = 'OK';
            } else {
                $health_report[$table_name] = 'NEEDS REPAIR';
                // Attempt to repair
                $repair_result = $wpdb->get_row("REPAIR TABLE $table_name");
                if ($repair_result && $repair_result->Msg_text === 'OK') {
                    $health_report[$table_name] = 'REPAIRED';
                }
            }
        } else {
            $health_report[$table_name] = 'MISSING';
        }
    }
    
    error_log('Assistant Mascot: Table health check completed: ' . json_encode($health_report));
    return $health_report;
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'assistant_mascot_deactivate');
function assistant_mascot_deactivate() {
    // Note: Tables are NOT deleted on deactivation
    // Only flush rewrite rules
    flush_rewrite_rules();
    
    // Log deactivation
    error_log('Assistant Mascot: Plugin deactivated - tables preserved');
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'assistant_mascot_uninstall');
function assistant_mascot_uninstall() {
    // Remove options
    delete_option('assistant_mascot_settings');
    delete_option('assistant_mascot_3d_settings');
    delete_option('assistant_mascot_ai_settings');
    delete_option('assistant_mascot_styles');
    
    // Remove database tables
    assistant_mascot_remove_tables();
    
    // Log uninstall
    error_log('Assistant Mascot: Plugin uninstalled - all data removed');
}

/**
 * Remove database tables when plugin is uninstalled
 */
function assistant_mascot_remove_tables() {
    global $wpdb;
    
    // Tables to remove
    $tables = array(
        $wpdb->prefix . 'assistant_mascot_animations',
        $wpdb->prefix . 'assistant_mascot_models',
        $wpdb->prefix . 'assistant_mascot_interactions',
        $wpdb->prefix . 'assistant_mascot_presets'
    );
    
    // Drop each table
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
    
    error_log('Assistant Mascot: Database tables removed');
}
