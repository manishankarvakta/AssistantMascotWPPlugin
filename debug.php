<?php
/**
 * Debug file for Assistant Mascot plugin
 * This file helps identify any issues with the plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add debug action
add_action('admin_notices', 'assistant_mascot_debug_notice');

function assistant_mascot_debug_notice() {
    if (isset($_GET['page']) && $_GET['page'] === 'assistant-mascot') {
        // Check if all required classes exist
        $classes_exist = class_exists('Assistant_Mascot') && 
                        class_exists('Assistant_Mascot_Admin') && 
                        class_exists('Assistant_Mascot_Frontend');
        
        if (!$classes_exist) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Assistant Mascot Debug:</strong> Some required classes are missing. Please check the plugin files.</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible"><p><strong>Assistant Mascot Debug:</strong> All required classes are loaded successfully.</p></div>';
        }
        
        // Check if settings exist
        $settings = get_option('assistant_mascot_settings');
        $ai_settings = get_option('assistant_mascot_ai_settings');
        $styles = get_option('assistant_mascot_styles');
        
        echo '<div class="notice notice-info is-dismissible"><p><strong>Settings Status:</strong> ';
        echo 'Main: ' . ($settings ? 'OK' : 'Missing') . ' | ';
        echo 'AI: ' . ($ai_settings ? 'OK' : 'Missing') . ' | ';
        echo 'Styles: ' . ($styles ? 'OK' : 'Missing');
        echo '</p></div>';
        
        // Show actual database values for debugging
        if (current_user_can('manage_options')) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Database Debug Info:</strong></p>';
            
            // Show main settings
            if ($settings) {
                echo '<p><strong>Main Settings:</strong> ' . esc_html(print_r($settings, true)) . '</p>';
            }
            
            // Show 3D settings
            $d_settings = get_option('assistant_mascot_3d_settings');
            if ($d_settings) {
                echo '<p><strong>3D Settings:</strong> ' . esc_html(print_r($d_settings, true)) . '</p>';
            }
            
            // Show AI settings
            if ($ai_settings) {
                echo '<p><strong>AI Settings:</strong> ' . esc_html(print_r($ai_settings, true)) . '</p>';
            }
            
            // Show styles
            if ($styles) {
                echo '<p><strong>Styles:</strong> ' . esc_html(print_r($styles, true)) . '</p>';
            }
            
            echo '</div>';
        }
    }
}

// Add function to check plugin options
function check_assistant_mascot_options() {
    if (isset($_GET['page']) && $_GET['page'] === 'assistant-mascot' && current_user_can('manage_options')) {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>Plugin Options Check:</strong></p>';
        
        // Check each option group
        $options_to_check = array(
            'assistant_mascot_settings' => 'Main Settings',
            'assistant_mascot_3d_settings' => '3D Model Settings',
            'assistant_mascot_ai_settings' => 'AI Settings',
            'assistant_mascot_styles' => 'Styles Settings'
        );
        
        foreach ($options_to_check as $option_name => $display_name) {
            $option_value = get_option($option_name);
            if ($option_value !== false) {
                echo '<p><strong>' . esc_html($display_name) . ':</strong> Found (' . count($option_value) . ' fields)</p>';
                echo '<details><summary>View Data</summary><pre>' . esc_html(print_r($option_value, true)) . '</pre></details>';
            } else {
                echo '<p><strong>' . esc_html($display_name) . ':</strong> <span style="color: red;">NOT FOUND</span></p>';
            }
        }
        
        echo '</div>';
    }
}

// Hook the options check
add_action('admin_notices', 'check_assistant_mascot_options');

// Add function to manually create options if missing
function create_missing_assistant_mascot_options() {
    if (isset($_GET['page']) && $_GET['page'] === 'assistant-mascot' && current_user_can('manage_options')) {
        if (isset($_GET['create_options']) && $_GET['create_options'] === '1') {
            // Create main settings if missing
            if (get_option('assistant_mascot_settings') === false) {
                add_option('assistant_mascot_settings', array(
                    'enabled' => true,
                    'position' => 'bottom-right',
                    'size' => 'medium',
                    'animation_speed' => 'normal'
                ));
                echo '<div class="notice notice-success is-dismissible"><p>Main settings created successfully!</p></div>';
            }
            
            // Create 3D settings if missing
            if (get_option('assistant_mascot_3d_settings') === false) {
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
                echo '<div class="notice notice-success is-dismissible"><p>3D settings created successfully!</p></div>';
            }
            
            // Create AI settings if missing
            if (get_option('assistant_mascot_ai_settings') === false) {
                add_option('assistant_mascot_ai_settings', array(
                    'ai_enabled' => false,
                    'ai_provider' => 'openai',
                    'api_key' => ''
                ));
                echo '<div class="notice notice-success is-dismissible"><p>AI settings created successfully!</p></div>';
            }
            
            // Create styles if missing
            if (get_option('assistant_mascot_styles') === false) {
                add_option('assistant_mascot_styles', array(
                    'primary_color' => '#ff0000',
                    'background_color' => '#ffffff',
                    'border_radius' => 8
                ));
                echo '<div class="notice notice-success is-dismissible"><p>Styles created successfully!</p></div>';
            }
        }
        
        // Show create options button if any are missing
        $missing_options = array();
        if (get_option('assistant_mascot_settings') === false) $missing_options[] = 'Main Settings';
        if (get_option('assistant_mascot_3d_settings') === false) $missing_options[] = '3D Settings';
        if (get_option('assistant_mascot_ai_settings') === false) $missing_options[] = 'AI Settings';
        if (get_option('assistant_mascot_styles') === false) $missing_options[] = 'Styles';
        
        if (!empty($missing_options)) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Missing Options:</strong> ' . implode(', ', $missing_options) . '</p>';
            echo '<p><a href="?page=assistant-mascot&create_options=1" class="button button-primary">Create Missing Options</a></p>';
            echo '</div>';
        }
    }
}

// Hook the options creation
add_action('admin_notices', 'create_missing_assistant_mascot_options');

// Add function to check database tables
function check_assistant_mascot_tables() {
    if (isset($_GET['page']) && $_GET['page'] === 'assistant-mascot' && current_user_can('manage_options')) {
        global $wpdb;
        
        $tables_to_check = array(
            $wpdb->prefix . 'assistant_mascot_animations' => 'Animations Table',
            $wpdb->prefix . 'assistant_mascot_models' => 'Models Table',
            $wpdb->prefix . 'assistant_mascot_interactions' => 'Interactions Table',
            $wpdb->prefix . 'assistant_mascot_presets' => 'Presets Table'
        );
        
        $missing_tables = array();
        $existing_tables = array();
        
        foreach ($tables_to_check as $table_name => $display_name) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            if ($table_exists) {
                $existing_tables[] = $display_name;
                
                // Get row count for existing tables
                $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>' . esc_html($display_name) . ':</strong> Found (' . $row_count . ' rows)</p>';
                echo '</div>';
            } else {
                $missing_tables[] = $display_name;
            }
        }
        
        if (!empty($missing_tables)) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>Missing Database Tables:</strong> ' . implode(', ', $missing_tables) . '</p>';
            echo '<p><strong>Action Required:</strong> Deactivate and reactivate the plugin to create missing tables.</p>';
            echo '</div>';
        }
        
        if (!empty($existing_tables)) {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>Database Tables Status:</strong> All required tables are present.</p>';
            echo '</div>';
        }
    }
}

// Hook the table check
add_action('admin_notices', 'check_assistant_mascot_tables');

// Add function to manually create tables if missing
function create_missing_assistant_mascot_tables() {
    if (isset($_GET['page']) && $_GET['page'] === 'assistant-mascot' && current_user_can('manage_options')) {
        if (isset($_GET['create_tables']) && $_GET['create_tables'] === '1') {
            // Include the table creation function from main plugin file
            if (function_exists('assistant_mascot_create_tables')) {
                assistant_mascot_create_tables();
                echo '<div class="notice notice-success is-dismissible"><p>Database tables check completed successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Error: Table creation function not found. Please reactivate the plugin.</p></div>';
            }
        }
        
        // Check if tables are missing and show create button
        global $wpdb;
        $missing_tables = array();
        
        $tables_to_check = array(
            $wpdb->prefix . 'assistant_mascot_animations' => 'Animations Table',
            $wpdb->prefix . 'assistant_mascot_models' => 'Models Table',
            $wpdb->prefix . 'assistant_mascot_interactions' => 'Interactions Table',
            $wpdb->prefix . 'assistant_mascot_presets' => 'Presets Table'
        );
        
        foreach ($tables_to_check as $table_name => $display_name) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            if (!$table_exists) {
                $missing_tables[] = $display_name;
            }
        }
        
        if (!empty($missing_tables)) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Missing Database Tables:</strong> ' . implode(', ', $missing_tables) . '</p>';
            echo '<p><a href="?page=assistant-mascot&create_tables=1" class="button button-primary">Check/Create Missing Tables</a></p>';
            echo '</div>';
        }
    }
}

// Hook the table creation
add_action('admin_notices', 'create_missing_assistant_mascot_tables');

// Add function to show table data
function show_assistant_mascot_table_data() {
    if (isset($_GET['page']) && $_GET['page'] === 'assistant-mascot' && current_user_can('manage_options')) {
        if (isset($_GET['show_table_data']) && $_GET['show_table_data'] === '1') {
            global $wpdb;
            
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>Database Table Contents:</strong></p>';
            
            // Show models table data
            $models_table = $wpdb->prefix . 'assistant_mascot_models';
            if ($wpdb->get_var("SHOW TABLES LIKE '$models_table'") === $models_table) {
                $models = $wpdb->get_results("SELECT * FROM $models_table");
                echo '<details><summary>Models Table (' . count($models) . ' rows)</summary>';
                echo '<pre>' . esc_html(print_r($models, true)) . '</pre>';
                echo '</details>';
            }
            
            // Show animations table data
            $animations_table = $wpdb->prefix . 'assistant_mascot_animations';
            if ($wpdb->get_var("SHOW TABLES LIKE '$animations_table'") === $animations_table) {
                $animations = $wpdb->get_results("SELECT * FROM $animations_table");
                echo '<details><summary>Animations Table (' . count($animations) . ' rows)</summary>';
                echo '<pre>' . esc_html(print_r($animations, true)) . '</pre>';
                echo '</details>';
            }
            
            // Show presets table data
            $presets_table = $wpdb->prefix . 'assistant_mascot_presets';
            if ($wpdb->get_var("SHOW TABLES LIKE '$presets_table'") === $presets_table) {
                $presets = $wpdb->get_results("SELECT * FROM $presets_table");
                echo '<details><summary>Presets Table (' . count($presets) . ' rows)</summary>';
                echo '<pre>' . esc_html(print_r($presets, true)) . '</pre>';
                echo '</details>';
            }
            
            // Show interactions table data (limited to last 10)
            $interactions_table = $wpdb->prefix . 'assistant_mascot_interactions';
            if ($wpdb->get_var("SHOW TABLES LIKE '$interactions_table'") === $interactions_table) {
                $interactions = $wpdb->get_results("SELECT * FROM $interactions_table ORDER BY created_at DESC LIMIT 10");
                echo '<details><summary>Interactions Table (Last 10 rows)</summary>';
                echo '<pre>' . esc_html(print_r($interactions, true)) . '</pre>';
                echo '</details>';
            }
            
            echo '</div>';
        }
        
        // Show button to view table data
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><a href="?page=assistant-mascot&show_table_data=1" class="button button-secondary">View Table Data</a></p>';
        echo '</div>';
    }
}

// Hook the table data display
add_action('admin_notices', 'show_assistant_mascot_table_data');
