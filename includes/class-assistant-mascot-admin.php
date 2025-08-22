<?php
/**
 * Admin functionality
 *
 * @package AssistantMascot
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Assistant_Mascot_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        try {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'init_settings'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
            
            // Add AJAX handlers
            add_action('wp_ajax_save_assistant_mascot_animations', array($this, 'save_animation_settings_ajax'));
            add_action('wp_ajax_save_synced_animations', array($this, 'save_synced_animations_ajax'));
            add_action('wp_ajax_save_animation_selections', array($this, 'save_animation_selections_ajax'));
            add_action('wp_ajax_load_animations_from_database', array($this, 'load_animations_from_database_ajax'));
        } catch (Exception $e) {
            error_log('Assistant Mascot Admin Error: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for saving animation settings
     */
    public function save_animation_settings_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'assistant_mascot_nonce')) {
            wp_die('Security check failed');
        }
        
        // Get animation settings
        $settings = $_POST['settings'];
        
        // Update the 3D settings option
        $current_settings = get_option('assistant_mascot_3d_settings', array());
        $current_settings['all_animations_enabled'] = $settings['all_animations_enabled'];
        $current_settings['global_animation_speed'] = $settings['global_animation_speed'];
        $current_settings['loop_animations'] = $settings['loop_animations'];
        $current_settings['enabled_animations'] = $settings['enabled_animations'];
        
        // Save the updated settings
        update_option('assistant_mascot_3d_settings', $current_settings);
        
        // Return success response
        wp_send_json_success('Animation settings saved successfully');
    }
    
    /**
     * AJAX handler for saving synced animations
     */
    public function save_synced_animations_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'assistant_mascot_nonce')) {
            wp_die('Security check failed');
        }
        
        // Get synced animations
        $settings = $_POST['settings'];
        
        // Update the 3D settings option
        $current_settings = get_option('assistant_mascot_3d_settings', array());
        $current_settings['synced_animations'] = $settings['synced_animations'];
        
        // Save the updated settings
        update_option('assistant_mascot_3d_settings', $current_settings);
        
        // Also save to database table
        $sync_result = $this->save_animations_to_database($settings['synced_animations']);
        
        // Log user interaction
        $this->save_user_interaction('sync_animations', array(
            'model_file' => $current_settings['model_file'] ?? 'avater.glb',
            'animations_count' => count($settings['synced_animations']),
            'animations' => $settings['synced_animations'],
            'sync_result' => $sync_result
        ));
        
        // Return success response with sync details
        wp_send_json_success(array(
            'message' => 'Animations synced successfully',
            'synced_count' => count($settings['synced_animations']),
            'new_animations' => $sync_result['new_count'],
            'skipped_animations' => $sync_result['skipped_count'],
            'total_existing' => $sync_result['total_existing'],
            'total_processed' => $sync_result['total_processed']
        ));
    }
    
    /**
     * AJAX handler for saving animation selections
     */
    public function save_animation_selections_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'assistant_mascot_nonce')) {
            wp_die('Security check failed');
        }
        
        // Get animation selections
        $selections = $_POST['selections'];
        
        // Update the 3D settings option with animation selections
        $current_settings = get_option('assistant_mascot_3d_settings', array());
        $current_settings['animation_selections'] = $selections;
        
        // Save the updated settings
        update_option('assistant_mascot_3d_settings', $current_settings);
        
        // Also save to database table
        $this->save_animation_selections_to_database($selections);
        
        // Log user interaction
        $this->save_user_interaction('save_animation_selections', array(
            'model_file' => $current_settings['model_file'] ?? 'avater.glb',
            'selections_count' => count($selections),
            'selections' => $selections
        ));
        
        // Return success response
        wp_send_json_success(array(
            'message' => 'Animation selections saved successfully',
            'selections_count' => count($selections)
        ));
    }
    
    /**
     * AJAX handler for loading existing animation data from database
     */
    public function load_animations_from_database_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'assistant_mascot_nonce')) {
            wp_die('Security check failed');
        }

        $model_file = sanitize_text_field($_POST['model_file']);

        $animations_data = $this->load_animations_from_database($model_file);

        wp_send_json_success($animations_data);
    }
    
    /**
     * Save animations to database table
     */
    private function save_animations_to_database($animations) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'assistant_mascot_animations';
        $models_table = $wpdb->prefix . 'assistant_mascot_models';
        
        // Get current model file
        $current_settings = get_option('assistant_mascot_3d_settings', array());
        $model_file = isset($current_settings['model_file']) ? $current_settings['model_file'] : 'avater.glb';
        
        // First, ensure model exists in models table
        $model_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $models_table WHERE model_file = %s",
            $model_file
        ));
        
        if (!$model_exists) {
            // Insert model if it doesn't exist
            $wpdb->insert(
                $models_table,
                array(
                    'model_file' => $model_file,
                    'model_name' => 'Model: ' . $model_file,
                    'file_path' => ASSISTANT_MASCOT_PLUGIN_URL . 'assets/model/' . $model_file,
                    'is_active' => 1
                ),
                array('%s', '%s', '%s', '%d')
            );
            error_log("Assistant Mascot: Inserted new model: $model_file");
        }
        
        // Get existing animations for this model
        $existing_animations = $wpdb->get_results($wpdb->prepare(
            "SELECT animation_name, is_enabled FROM $table_name WHERE model_file = %s",
            $model_file
        ));
        
        $existing_animation_names = array();
        $existing_animation_states = array();
        
        foreach ($existing_animations as $existing) {
            $existing_animation_names[] = $existing->animation_name;
            $existing_animation_states[$existing->animation_name] = $existing->is_enabled;
        }
        
        $new_animations_count = 0;
        $skipped_animations_count = 0;
        
        // Process each animation
        foreach ($animations as $animation_name) {
            if (in_array($animation_name, $existing_animation_names)) {
                // Animation already exists - skip it
                $skipped_animations_count++;
                error_log("Assistant Mascot: Skipped existing animation: $animation_name for model: $model_file");
                continue;
            }
            
            // New animation - insert it
            $duration = 0.000;
            if (isset($settings['animation_durations'][$animation_name])) {
                $duration = floatval($settings['animation_durations'][$animation_name]);
            }
            
            $wpdb->insert(
                $table_name,
                array(
                    'model_file' => $model_file,
                    'animation_name' => $animation_name,
                    'animation_duration' => $duration,
                    'is_enabled' => 0, // Default to disabled
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%f', '%d', '%s', '%s')
            );
            
            $new_animations_count++;
            error_log("Assistant Mascot: Added new animation: $animation_name for model: $model_file");
        }
        
        // Log summary
        error_log("Assistant Mascot: Sync completed for model: $model_file - New: $new_animations_count, Skipped: $skipped_animations_count");
        
        // Return summary for user feedback
        return array(
            'new_count' => $new_animations_count,
            'skipped_count' => $skipped_animations_count,
            'total_existing' => count($existing_animation_names),
            'total_processed' => count($animations)
        );
    }
    
    /**
     * Save animation selections to database table
     */
    private function save_animation_selections_to_database($selections) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'assistant_mascot_animations';
        $current_settings = get_option('assistant_mascot_3d_settings', array());
        $model_file = isset($current_settings['model_file']) ? $current_settings['model_file'] : 'avater.glb';
        
        // Update each animation's enabled status
        foreach ($selections as $animation_name => $is_enabled) {
            $wpdb->update(
                $table_name,
                array(
                    'is_enabled' => $is_enabled ? 1 : 0,
                    'updated_at' => current_time('mysql')
                ),
                array(
                    'model_file' => $model_file,
                    'animation_name' => $animation_name
                ),
                array('%d', '%s'),
                array('%s', '%s')
            );
        }
        
        error_log("Assistant Mascot: Updated animation selections in database for model: $model_file");
    }
    
    /**
     * Save 3D settings to database tables
     */
    private function save_3d_settings_to_database($settings) {
        global $wpdb;
        
        // Update model information
        $models_table = $wpdb->prefix . 'assistant_mascot_models';
        $model_file = isset($settings['model_file']) ? $settings['model_file'] : 'avater.glb';
        
        // Check if model exists
        $model_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $models_table WHERE model_file = %s",
            $model_file
        ));
        
        if ($model_exists) {
            // Update existing model
            $wpdb->update(
                $models_table,
                array(
                    'last_used' => current_time('mysql'),
                    'is_active' => 1
                ),
                array('model_file' => $model_file),
                array('%s', '%d'),
                array('%s')
            );
        } else {
            // Insert new model
            $wpdb->insert(
                $models_table,
                array(
                    'model_file' => $model_file,
                    'model_name' => 'Model: ' . $model_file,
                    'file_path' => ASSISTANT_MASCOT_PLUGIN_URL . 'assets/model/' . $model_file,
                    'is_active' => 1,
                    'upload_date' => current_time('mysql'),
                    'last_used' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%d', '%s', '%s')
            );
        }
        
        // Save display and lighting settings as a preset
        $presets_table = $wpdb->prefix . 'assistant_mascot_presets';
        $preset_name = 'Auto-Save: ' . $model_file . ' - ' . current_time('mysql', true);
        
        $preset_data = array(
            'preset_name' => $preset_name,
            'preset_description' => 'Auto-saved configuration for ' . $model_file,
            'model_file' => $model_file,
            'animation_settings' => json_encode(array(
                'all_animations_enabled' => isset($settings['all_animations_enabled']) ? $settings['all_animations_enabled'] : false,
                'global_animation_speed' => isset($settings['global_animation_speed']) ? $settings['global_animation_speed'] : 1.0,
                'loop_animations' => isset($settings['loop_animations']) ? $settings['loop_animations'] : true
            )),
            'display_settings' => json_encode(array(
                'background' => isset($settings['background']) ? $settings['background'] : false,
                'auto_rotate' => isset($settings['auto_rotate']) ? $settings['auto_rotate'] : true,
                'wireframe' => isset($settings['wireframe']) ? $settings['wireframe'] : false,
                'skeleton' => isset($settings['skeleton']) ? $settings['skeleton'] : false,
                'grid' => isset($settings['grid']) ? $settings['grid'] : false,
                'screen_space_pan' => isset($settings['screen_space_pan']) ? $settings['screen_space_pan'] : true,
                'point_size' => isset($settings['point_size']) ? $settings['point_size'] : 1.0,
                'bg_color' => isset($settings['bg_color']) ? $settings['bg_color'] : '#5b5b5b'
            )),
            'lighting_settings' => json_encode(array(
                'lighting_intensity' => isset($settings['lighting_intensity']) ? $settings['lighting_intensity'] : 1.0,
                'ambient_light' => isset($settings['ambient_light']) ? $settings['ambient_light'] : 0.3,
                'directional_light' => isset($settings['directional_light']) ? $settings['directional_light'] : 1.0,
                'light_color' => isset($settings['light_color']) ? $settings['light_color'] : '#ffffff'
            )),
            'is_public' => 0,
            'created_by' => get_current_user_id()
        );
        
        $wpdb->insert(
            $presets_table,
            $preset_data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d')
        );
        
        error_log("Assistant Mascot: Saved 3D settings to database for model: $model_file");
    }
    
    /**
     * Save user interaction to database
     */
    public function save_user_interaction($action_type, $action_data = array(), $page_url = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'assistant_mascot_interactions';
        
        $interaction_data = array(
            'user_id' => get_current_user_id(),
            'session_id' => session_id() ?: uniqid(),
            'action_type' => $action_type,
            'action_data' => json_encode($action_data),
            'page_url' => $page_url ?: $_SERVER['REQUEST_URI'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip_address' => $this->get_client_ip(),
            'created_at' => current_time('mysql')
        );
        
        $wpdb->insert(
            $table_name,
            $interaction_data,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Load existing animation data from database
     */
    public function load_animations_from_database($model_file) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'assistant_mascot_animations';
        
        $animations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE model_file = %s ORDER BY animation_name ASC",
            $model_file
        ));
        
        $result = array(
            'animations' => array(),
            'selections' => array()
        );
        
        foreach ($animations as $animation) {
            $result['animations'][] = array(
                'animation_name' => $animation->animation_name,
                'animation_duration' => $animation->animation_duration,
                'is_enabled' => $animation->is_enabled,
                'created_at' => $animation->created_at,
                'updated_at' => $animation->updated_at
            );
            $result['selections'][$animation->animation_name] = (bool) $animation->is_enabled;
        }
        
        return $result;
    }
    
    /**
     * Get model information from database
     */
    public function get_model_from_database($model_file) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'assistant_mascot_models';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE model_file = %s",
            $model_file
        ));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Assistant Mascot', 'assistant-mascot'),
            __('Assistant Mascot', 'assistant-mascot'),
            'manage_options',
            'assistant-mascot',
            array($this, 'admin_page'),
            'dashicons-admin-generic',
            30
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting(
            'assistant_mascot_settings',
            'assistant_mascot_settings',
            array($this, 'sanitize_settings')
        );
        
        add_settings_section(
            'assistant_mascot_general',
            __('General Settings', 'assistant-mascot'),
            array($this, 'general_section_callback'),
            'assistant-mascot'
        );
        
        add_settings_field(
            'enabled',
            __('Enable Plugin', 'assistant-mascot'),
            array($this, 'enabled_field_callback'),
            'assistant-mascot',
            'assistant_mascot_general'
        );
        
        add_settings_field(
            'position',
            __('Position', 'assistant-mascot'),
            array($this, 'position_field_callback'),
            'assistant-mascot',
            'assistant_mascot_general'
        );
        
        add_settings_field(
            'size',
            __('Mascot Size', 'assistant-mascot'),
            array($this, 'size_field_callback'),
            'assistant-mascot',
            'assistant_mascot_general'
        );
        
        add_settings_field(
            'animation_speed',
            __('Animation Speed', 'assistant-mascot'),
            array($this, 'animation_speed_field_callback'),
            'assistant-mascot',
            'assistant_mascot_general'
        );
        
        // Register 3D Model Settings
        register_setting(
            'assistant_mascot_3d_settings',
            'assistant_mascot_3d_settings',
            array($this, 'sanitize_3d_settings')
        );
        
        // Register AI Settings
        register_setting(
            'assistant_mascot_ai_settings',
            'assistant_mascot_ai_settings',
            array($this, 'sanitize_ai_settings')
        );
        
        // Register Styles Settings
        register_setting(
            'assistant_mascot_styles',
            'assistant_mascot_styles',
            array($this, 'sanitize_styles')
        );
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        try {
            $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
            ?>
            <div class="wrap">
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                <p><?php _e('Configure your Assistant Mascot plugin settings below.', 'assistant-mascot'); ?></p>
                
                            <!-- Tab Navigation -->
            <nav class="nav-tab-wrapper wp-clearfix">
                <a href="?page=assistant-mascot&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('General', 'assistant-mascot'); ?>
                </a>
                <a href="?page=assistant-mascot&tab=3d-model" class="nav-tab <?php echo $active_tab === '3d-model' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-3d"></span>
                    <?php _e('3D Model', 'assistant-mascot'); ?>
                </a>
                <a href="?page=assistant-mascot&tab=faq" class="nav-tab <?php echo $active_tab === 'faq' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-format-chat"></span>
                    <?php _e('FAQ', 'assistant-mascot'); ?>
                </a>
                <a href="?page=assistant-mascot&tab=ai-settings" class="nav-tab <?php echo $active_tab === 'ai-settings' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php _e('AI Settings', 'assistant-mascot'); ?>
                </a>
                <a href="?page=assistant-mascot&tab=styles" class="nav-tab <?php echo $active_tab === 'styles' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-appearance"></span>
                    <?php _e('Styles', 'assistant-mascot'); ?>
                </a>
            </nav>
                
                <!-- Tab Content -->
                <div class="tab-content">
                    <?php
                    switch ($active_tab) {
                        case 'general':
                            $this->render_general_tab();
                            break;
                        case '3d-model':
                            $this->render_3d_model_tab();
                            break;
                        case 'faq':
                            $this->render_faq_tab();
                            break;
                        case 'ai-settings':
                            $this->render_ai_settings_tab();
                            break;
                        case 'styles':
                            $this->render_styles_tab();
                            break;
                        default:
                            $this->render_general_tab();
                            break;
                    }
                    ?>
                </div>
            </div>
            <?php
        } catch (Exception $e) {
            echo '<div class="wrap"><div class="notice notice-error"><p>Error loading Assistant Mascot admin page: ' . esc_html($e->getMessage()) . '</p></div></div>';
            error_log('Assistant Mascot Admin Page Error: ' . $e->getMessage());
        }
    }
    
    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure the appearance and behavior of your Assistant Mascot.', 'assistant-mascot') . '</p>';
    }
    
    /**
     * Enabled field callback
     */
    public function enabled_field_callback() {
        $options = get_option('assistant_mascot_settings');
        $enabled = isset($options['enabled']) ? $options['enabled'] : true;
        ?>
        <input type="checkbox" id="enabled" name="assistant_mascot_settings[enabled]" value="1" <?php checked(1, $enabled); ?> />
        <label for="enabled"><?php _e('Show the mascot on the frontend', 'assistant-mascot'); ?></label>
        <?php
    }
    
    /**
     * Position field callback
     */
    public function position_field_callback() {
        $options = get_option('assistant_mascot_settings');
        $position = isset($options['position']) ? $options['position'] : 'bottom-right';
        ?>
        <select id="position" name="assistant_mascot_settings[position]">
            <option value="top-left" <?php selected('top-left', $position); ?>><?php _e('Top Left', 'assistant-mascot'); ?></option>
            <option value="top-right" <?php selected('top-right', $position); ?>><?php _e('Top Right', 'assistant-mascot'); ?></option>
            <option value="bottom-left" <?php selected('bottom-left', $position); ?>><?php _e('Bottom Left', 'assistant-mascot'); ?></option>
            <option value="bottom-right" <?php selected('bottom-right', $position); ?>><?php _e('Bottom Right', 'assistant-mascot'); ?></option>
        </select>
        <?php
    }
    
    /**
     * Size field callback
     */
    public function size_field_callback() {
        $options = get_option('assistant_mascot_settings');
        $size = isset($options['size']) ? $options['size'] : 'medium';
        ?>
        <select id="size" name="assistant_mascot_settings[size]">
            <option value="small" <?php selected('small', $size); ?>><?php _e('Small (200x200px)', 'assistant-mascot'); ?></option>
            <option value="medium" <?php selected('medium', $size); ?>><?php _e('Medium (300x300px)', 'assistant-mascot'); ?></option>
            <option value="large" <?php selected('large', $size); ?>><?php _e('Large (400x400px)', 'assistant-mascot'); ?></option>
        </select>
        <p class="description"><?php _e('Choose the size of the mascot on your website.', 'assistant-mascot'); ?></p>
        <?php
    }
    
    /**
     * Animation speed field callback
     */
    public function animation_speed_field_callback() {
        $options = get_option('assistant_mascot_settings');
        $speed = isset($options['animation_speed']) ? $options['animation_speed'] : 'normal';
        ?>
        <select id="animation_speed" name="assistant_mascot_settings[animation_speed]">
            <option value="slow" <?php selected('slow', $speed); ?>><?php _e('Slow', 'assistant-mascot'); ?></option>
            <option value="normal" <?php selected('normal', $speed); ?>><?php _e('Normal', 'assistant-mascot'); ?></option>
            <option value="fast" <?php selected('fast', $speed); ?>><?php _e('Fast', 'assistant-mascot'); ?></option>
        </select>
        <p class="description"><?php _e('Choose the animation speed for the mascot.', 'assistant-mascot'); ?></p>
        <?php
    }
    
    /**
     * Render General tab content
     */
    private function render_general_tab() {
        try {
            ?>
            <div class="tab-panel">
                <div class="tab-header">
                    <h2><?php _e('General Settings', 'assistant-mascot'); ?></h2>
                    <p><?php _e('Configure the basic settings and appearance of your Assistant Mascot.', 'assistant-mascot'); ?></p>
                </div>
                
                <form method="post" action="options.php">
                    <?php
                    settings_fields('assistant_mascot_settings');
                    do_settings_sections('assistant-mascot');
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>Error rendering General tab: ' . esc_html($e->getMessage()) . '</p></div>';
            error_log('Assistant Mascot General Tab Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Render 3D Model tab content
     */
    private function render_3d_model_tab() {
        try {
            $active_subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : 'model';
            ?>
            <div class="tab-panel">
                <div class="tab-header">
                    <h2><?php _e('3D Model Configuration', 'assistant-mascot'); ?></h2>
                    <p><?php _e('Configure advanced 3D model settings and rendering options.', 'assistant-mascot'); ?></p>
                </div>
                
                <div class="three-column-layout">
                    <!-- Left Sidebar - Sub-tabs (2 columns) -->
                    <div class="sidebar-column">
                        <nav class="sub-tabs-nav">
                            <a href="?page=assistant-mascot&tab=3d-model&subtab=model" 
                               class="sub-tab <?php echo $active_subtab === 'model' ? 'sub-tab-active' : ''; ?>">
                                <span class="dashicons dashicons-admin-3d"></span>
                                <?php _e('Model', 'assistant-mascot'); ?>
                            </a>
                            <a href="?page=assistant-mascot&tab=3d-model&subtab=display" 
                               class="sub-tab <?php echo $active_subtab === 'display' ? 'sub-tab-active' : ''; ?>">
                                <span class="dashicons dashicons-admin-appearance"></span>
                                <?php _e('Display', 'assistant-mascot'); ?>
                            </a>
                            <a href="?page=assistant-mascot&tab=3d-model&subtab=animations" 
                               class="sub-tab <?php echo $active_subtab === 'animations' ? 'sub-tab-active' : ''; ?>">
                                <span class="dashicons dashicons-controls-play"></span>
                                <?php _e('Animations', 'assistant-mascot'); ?>
                            </a>
                            <a href="?page=assistant-mascot&tab=3d-model&subtab=lights" 
                               class="sub-tab <?php echo $active_subtab === 'lights' ? 'sub-tab-active' : ''; ?>">
                                <span class="dashicons dashicons-lightbulb"></span>
                                <?php _e('Lights', 'assistant-mascot'); ?>
                            </a>
                        </nav>
                    </div>
                    
                    <!-- Middle Section - Settings (4 columns) -->
                    <div class="settings-column">
                        <div class="settings-content">
                            <?php
                            switch ($active_subtab) {
                                case 'model':
                                    $this->render_3d_model_subtab();
                                    break;
                                case 'display':
                                    $this->render_3d_display_subtab();
                                    break;
                                case 'animations':
                                    $this->render_3d_animations_subtab();
                                    break;
                                case 'lights':
                                    $this->render_3d_lights_subtab();
                                    break;
                                default:
                                    $this->render_3d_model_subtab();
                                    break;
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- Right Section - Live Preview (6 columns) -->
                    <div class="preview-column">
                        <div class="model-preview-wrapper">
                            <h3><?php _e('3D Model Preview', 'assistant-mascot'); ?></h3>
                            <div class="model-preview-container">
                                <div id="3d-model-preview" class="model-preview-canvas">
                                    <canvas id="admin-3d-canvas" width="400" height="300"></canvas>
                                    <div class="preview-placeholder" style="display: none;">
                                        <span class="dashicons dashicons-admin-3d"></span>
                                        <p><?php _e('3D Model Preview', 'assistant-mascot'); ?></p>
                                        <small><?php _e('Live preview will appear here', 'assistant-mascot'); ?></small>
                                    </div>
                                </div>
                                <div class="preview-controls">
                                    <button type="button" class="button preview-reset">
                                        <span class="dashicons dashicons-update"></span>
                                        <?php _e('Reset View', 'assistant-mascot'); ?>
                                    </button>
                                    <button type="button" class="button preview-rotate">
                                        <span class="dashicons dashicons-rotate"></span>
                                        <?php _e('Auto Rotate', 'assistant-mascot'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>Error rendering 3D Model tab: ' . esc_html($e->getMessage()) . '</p></div>';
            error_log('Assistant Mascot 3D Model Tab Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Render 3D Model sub-tab content
     */
    private function render_3d_model_subtab() {
        try {
            ?>
            <div class="subtab-content">
                <h3><?php _e('Model Configuration', 'assistant-mascot'); ?></h3>
                <form method="post" action="options.php">
                    <?php settings_fields('assistant_mascot_3d_settings'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Model File', 'assistant-mascot'); ?></th>
                            <td>
                                <input type="text" name="assistant_mascot_3d_settings[model_file]" value="avater.glb" class="regular-text" />
                                <p class="description"><?php _e('Enter the 3D model file name (e.g., avater.glb)', 'assistant-mascot'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Rendering Quality', 'assistant-mascot'); ?></th>
                            <td>
                                <select name="assistant_mascot_3d_settings[rendering_quality]">
                                    <option value="low"><?php _e('Low (Performance)', 'assistant-mascot'); ?></option>
                                    <option value="medium"><?php _e('Medium (Balanced)', 'assistant-mascot'); ?></option>
                                    <option value="high"><?php _e('High (Quality)', 'assistant-mascot'); ?></option>
                                </select>
                                <p class="description"><?php _e('Choose rendering quality vs performance balance', 'assistant-mascot'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Scale Factor', 'assistant-mascot'); ?></th>
                            <td>
                                <input type="range" name="assistant_mascot_3d_settings[scale_factor]" min="0.1" max="3.0" step="0.1" value="1.0" data-unit="" />
                                <span class="range-value">1.0</span>
                                <p class="description"><?php _e('Adjust the scale of the 3D model', 'assistant-mascot'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>Error rendering Model sub-tab: ' . esc_html($e->getMessage()) . '</p></div>';
            error_log('Assistant Mascot Model Sub-tab Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Render 3D Display sub-tab content
     */
    private function render_3d_display_subtab() {
        try {
            ?>
            <div class="subtab-content">
                <h3><?php _e('Display Settings', 'assistant-mascot'); ?></h3>
                <form method="post" action="options.php">
                    <?php settings_fields('assistant_mascot_3d_settings'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Background', 'assistant-mascot'); ?></th>
                            <td>
                                <input type="checkbox" name="assistant_mascot_3d_settings[background]" value="1" />
                                <label><?php _e('Show background in the 3D scene', 'assistant-mascot'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Auto Rotate', 'assistant-mascot'); ?></th>
                            <td>
                                <input type="checkbox" name="assistant_mascot_3d_settings[auto_rotate]" value="1" />
                                <label><?php _e('Enable automatic model rotation', 'assistant-mascot'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Wireframe', 'assistant-mascot'); ?></th>
                            <td>
                                <input type="checkbox" name="assistant_mascot_3d_settings[wireframe]" value="1" />
                                <label><?php _e('Display model in wireframe mode', 'assistant-mascot'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Skeleton', 'assistant-mascot'); ?></th>
                            <td>
                                <input type="checkbox" name="assistant_mascot_3d_settings[skeleton]" value="1" />
                                <label><?php _e('Show model skeleton/bones', 'assistant-mascot'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Grid', 'assistant-mascot'); ?></th>
                            <td>
                                <input type="checkbox" name="assistant_mascot_3d_settings[grid]" value="1" />
                                <label><?php _e('Show reference grid in the scene', 'assistant-mascot'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Screen Space Pan', 'assistant-mascot'); ?></th>
                            <td>
                                <input type="checkbox" name="assistant_mascot_3d_settings[screen_space_pan]" value="1" />
                                <label><?php _e('Enable screen space panning', 'assistant-mascot'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Point Size', 'assistant-mascot'); ?></th>
                            <td>
                                <input type="number" name="assistant_mascot_3d_settings[point_size]" value="1" min="0.1" max="10" step="0.1" class="small-text" />
                                <p class="description"><?php _e('Adjust the size of point clouds and vertices', 'assistant-mascot'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Background Color', 'assistant-mascot'); ?></th>
                            <td>
                                <input type="color" name="assistant_mascot_3d_settings[bg_color]" value="#5b5b5b" />
                                <p class="description"><?php _e('Choose the background color for the 3D scene', 'assistant-mascot'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>Error rendering Display sub-tab: ' . esc_html($e->getMessage()) . '</p></div>';
            error_log('Assistant Mascot Display Sub-tab Error: ' . $e->getMessage());
        }
    }
    
        /**
     * Render 3D Animations sub-tab content
     */
    private function render_3d_animations_subtab() {
        try {
            ?>
            <div class="subtab-content">
                <!-- <h3><?php _e('Animation Settings', 'assistant-mascot'); ?></h3> -->
                
                <!-- Model Animation Detection -->
                <div class="animation-detection">
                    <div class="animation-header">
                        <h4><?php _e('Model Animations', 'assistant-mascot'); ?></h4>
                        <button type="button" id="sync-animations" class="button button-secondary">
                            <span class="dashicons dashicons-update-alt"></span>
                            <?php _e('Sync Animations', 'assistant-mascot'); ?>
                        </button>
                        <button type="button" id="show-saved-data" class="button button-secondary">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php _e('Show Saved Data', 'assistant-mascot'); ?>
                        </button>
                        <button type="button" id="export-json" class="button button-secondary">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Export JSON', 'assistant-mascot'); ?>
                        </button>
                    </div>
                    <p><?php _e('Model animations will be synced and stored as individual options:', 'assistant-mascot'); ?></p>
                    
                    <!-- All Animations Control -->
                    <div class="all-animations-control">
                        <div class="animation-item all-animations-item">
                            <div class="animation-info">
                                <span class="dashicons dashicons-controls-play"></span>
                                <div>
                                    <div class="animation-name"><?php _e('All Animations', 'assistant-mascot'); ?></div>
                                    <div class="animation-duration"><?php _e('Master Control', 'assistant-mascot'); ?></div>
                                </div>
                            </div>
                            <div class="animation-toggle">
                                <input type="checkbox" id="all-animations-toggle" name="assistant_mascot_3d_settings[all_animations_enabled]" value="1" />
                                <label for="all-animations-toggle"><?php _e('Enable All', 'assistant-mascot'); ?></label>
                            </div>
                        </div>
                    </div>
                    
                    <div id="model-animations-list" class="animations-list">
                        <div class="loading-animations">
                            <span class="dashicons dashicons-database"></span>
                            <span><?php _e('Loading animations from database...', 'assistant-mascot'); ?></span>
                        </div>
                    </div>
                </div>
                
                <form method="post" action="options.php">
                    <?php settings_fields('assistant_mascot_3d_settings'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row" title="<?php _e('Control the overall speed of all model animations. 0.1x (very slow) to 3.0x (very fast)', 'assistant-mascot'); ?>">
                                <?php _e('Global Animation Speed', 'assistant-mascot'); ?>
                            </th>
                            <td>
                                <input type="range" name="assistant_mascot_3d_settings[global_animation_speed]" min="0.1" max="3.0" step="0.1" value="1.0" data-unit="" />
                                <span class="range-value">1.0</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" title="<?php _e('Enable continuous looping for all model animations', 'assistant-mascot'); ?>">
                                <?php _e('Loop Animations', 'assistant-mascot'); ?>
                            </th>
                            <td>
                                <input type="checkbox" name="assistant_mascot_3d_settings[loop_animations]" value="1" checked />
                                <label><?php _e('Loop model animations continuously', 'assistant-mascot'); ?></label>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>Error rendering Animations sub-tab: ' . esc_html($e->getMessage()) . '</p></div>';
            error_log('Assistant Mascot Animations Sub-tab Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Render 3D Lights sub-tab content
     */
    private function render_3d_lights_subtab() {
        try {
            ?>
            <div class="subtab-content">
                <h3><?php _e('Lighting Configuration', 'assistant-mascot'); ?></h3>
                <form method="post" action="options.php">
                    <?php settings_fields('assistant_mascot_3d_settings'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Lighting Intensity', 'assistant-mascot'); ?></th>
                            <td>
                                <input type="range" name="assistant_mascot_3d_settings[lighting_intensity]" min="0.1" max="2.0" step="0.1" value="1.0" data-unit="" />
                                <span class="range-value">1.0</span>
                                <p class="description"><?php _e('Adjust the overall lighting intensity', 'assistant-mascot'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Ambient Light', 'assistant-mascot'); ?></th>
                            <td>
                                <input type="range" name="assistant_mascot_3d_settings[ambient_light]" min="0.0" max="1.0" step="0.1" value="0.3" data-unit="" />
                                <span class="range-value">0.3</span>
                                <p class="description"><?php _e('Control ambient lighting level', 'assistant-mascot'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Directional Light', 'assistant-mascot'); ?></th>
                            <td>
                                <input type="range" name="assistant_mascot_3d_settings[directional_light]" min="0.0" max="2.0" step="0.1" value="1.0" data-unit="" />
                                <span class="range-value">1.0</span>
                                <p class="description"><?php _e('Control directional lighting strength', 'assistant-mascot'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Light Color', 'assistant-mascot'); ?></th>
                            <td>
                                <input type="color" name="assistant_mascot_3d_settings[light_color]" value="#ffffff" />
                                <p class="description"><?php _e('Choose the color of the main light source', 'assistant-mascot'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>Error rendering Lights sub-tab: ' . esc_html($e->getMessage()) . '</p></div>';
            error_log('Assistant Mascot Lights Sub-tab Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Render FAQ tab content
     */
    private function render_faq_tab() {
        try {
            ?>
            <div class="tab-panel">
                <div class="tab-header">
                    <h2><?php _e('FAQ Management', 'assistant-mascot'); ?></h2>
                    <p><?php _e('Manage frequently asked questions and their answers.', 'assistant-mascot'); ?></p>
                </div>
                
                <div class="faq-management">
                    <div class="faq-actions">
                        <button type="button" class="button button-primary" id="add-faq-btn">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <?php _e('Add New FAQ', 'assistant-mascot'); ?>
                        </button>
                    </div>
                    
                    <div class="faq-list" id="faq-list">
                        <p class="no-faqs"><?php _e('No FAQs added yet. Click "Add New FAQ" to get started.', 'assistant-mascot'); ?></p>
                    </div>
                </div>
            </div>
            <?php
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>Error rendering FAQ tab: ' . esc_html($e->getMessage()) . '</p></div>';
            error_log('Assistant Mascot FAQ Tab Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Render AI Settings tab content
     */
    private function render_ai_settings_tab() {
        try {
            ?>
            <div class="tab-panel">
                <div class="tab-header">
                    <h2><?php _e('AI Configuration', 'assistant-mascot'); ?></h2>
                    <p><?php _e('Configure AI-powered features and chatbot settings.', 'assistant-mascot'); ?></p>
                </div>
                
                <div class="ai-settings-form">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('assistant_mascot_ai_settings');
                        ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('AI Enabled', 'assistant-mascot'); ?></th>
                                <td>
                                    <input type="checkbox" id="ai_enabled" name="assistant_mascot_ai_settings[ai_enabled]" value="1" />
                                    <label for="ai_enabled"><?php _e('Enable AI-powered features', 'assistant-mascot'); ?></label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('AI Provider', 'assistant-mascot'); ?></th>
                                <td>
                                    <select name="assistant_mascot_ai_settings[ai_provider]">
                                        <option value="openai">OpenAI</option>
                                        <option value="anthropic">Anthropic</option>
                                        <option value="google">Google AI</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('API Key', 'assistant-mascot'); ?></th>
                                <td>
                                    <input type="password" name="assistant_mascot_ai_settings[api_key]" class="regular-text" />
                                    <p class="description"><?php _e('Enter your AI service API key', 'assistant-mascot'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button(); ?>
                    </form>
                </div>
            </div>
            <?php
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>Error rendering AI Settings tab: ' . esc_html($e->getMessage()) . '</p></div>';
            error_log('Assistant Mascot AI Settings Tab Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Render Styles tab content
     */
    private function render_styles_tab() {
        try {
            ?>
            <div class="tab-panel">
                <div class="tab-header">
                    <h2><?php _e('Style Customization', 'assistant-mascot'); ?></h2>
                    <p><?php _e('Customize the appearance and styling of your mascot.', 'assistant-mascot'); ?></p>
                </div>
                
                <div class="styles-form">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('assistant_mascot_styles');
                        ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Primary Color', 'assistant-mascot'); ?></th>
                                <td>
                                    <input type="color" name="assistant_mascot_styles[primary_color]" value="#ff0000" />
                                    <p class="description"><?php _e('Choose the primary color for your mascot', 'assistant-mascot'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Background Color', 'assistant-mascot'); ?></th>
                                <td>
                                    <input type="color" name="assistant_mascot_styles[background_color]" value="#ffffff" />
                                    <p class="description"><?php _e('Choose the background color', 'assistant-mascot'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Border Radius', 'assistant-mascot'); ?></th>
                                <td>
                                    <input type="range" name="assistant_mascot_styles[border_radius]" min="0" max="50" value="8" data-unit="px" />
                                    <span class="range-value">8px</span>
                                    <p class="description"><?php _e('Adjust the border radius of elements', 'assistant-mascot'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button(); ?>
                    </form>
                </div>
            </div>
            <?php
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>Error rendering Styles tab: ' . esc_html($e->getMessage()) . '</p></div>';
            error_log('Assistant Mascot Styles Tab Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Sanitize settings
     *
     * @param array $input Input settings
     * @return array Sanitized settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['enabled'] = isset($input['enabled']) ? true : false;
        $sanitized['position'] = sanitize_text_field($input['position']);
        $sanitized['size'] = sanitize_text_field($input['size']);
        $sanitized['animation_speed'] = sanitize_text_field($input['animation_speed']);
        
        return $sanitized;
    }
    
    /**
     * Sanitize 3D settings
     *
     * @param array $input Input settings
     * @return array Sanitized settings
     */
    public function sanitize_3d_settings($input) {
        $sanitized = array();
        
        // Model settings
        $sanitized['model_file'] = sanitize_text_field($input['model_file']);
        $sanitized['rendering_quality'] = sanitize_text_field($input['rendering_quality']);
        $sanitized['scale_factor'] = floatval($input['scale_factor']);
        
        // Display settings
        $sanitized['background'] = isset($input['background']) ? true : false;
        $sanitized['auto_rotate'] = isset($input['auto_rotate']) ? true : false;
        $sanitized['wireframe'] = isset($input['wireframe']) ? true : false;
        $sanitized['skeleton'] = isset($input['skeleton']) ? true : false;
        $sanitized['grid'] = isset($input['grid']) ? true : false;
        $sanitized['screen_space_pan'] = isset($input['screen_space_pan']) ? true : false;
        $sanitized['point_size'] = floatval($input['point_size']);
        $sanitized['bg_color'] = $this->sanitize_hex_color($input['bg_color']);
        
        // Animation settings
        $sanitized['all_animations_enabled'] = isset($input['all_animations_enabled']) ? true : false;
        $sanitized['global_animation_speed'] = floatval($input['global_animation_speed']);
        $sanitized['loop_animations'] = isset($input['loop_animations']) ? true : false;
        
        // Dynamic animation fields - store each animation as individual boolean option
        foreach ($input as $key => $value) {
            if (strpos($key, 'animation_') === 0 && $key !== 'all_animations_enabled' && $key !== 'global_animation_speed' && $key !== 'loop_animations') {
                $sanitized[$key] = isset($value) ? true : false;
            }
        }
        
        // Preserve synced animations list
        $current_settings = get_option('assistant_mascot_3d_settings', array());
        if (isset($current_settings['synced_animations'])) {
            $sanitized['synced_animations'] = $current_settings['synced_animations'];
        }
        
        // Preserve animation selections
        if (isset($current_settings['animation_selections'])) {
            $sanitized['animation_selections'] = $current_settings['animation_selections'];
        }
        
        // Lighting settings
        $sanitized['lighting_intensity'] = floatval($input['lighting_intensity']);
        $sanitized['ambient_light'] = floatval($input['ambient_light']);
        $sanitized['directional_light'] = floatval($input['directional_light']);
        $sanitized['light_color'] = $this->sanitize_hex_color($input['light_color']);
        
        // Save to database tables after sanitization
        $this->save_3d_settings_to_database($sanitized);
        
        return $sanitized;
    }
    
    /**
     * Sanitize AI settings
     *
     * @param array $input Input settings
     * @return array Sanitized settings
     */
    public function sanitize_ai_settings($input) {
        $sanitized = array();
        
        $sanitized['ai_enabled'] = isset($input['ai_enabled']) ? true : false;
        $sanitized['ai_provider'] = sanitize_text_field($input['ai_provider']);
        $sanitized['api_key'] = sanitize_text_field($input['api_key']);
        
        return $sanitized;
    }
    
    /**
     * Sanitize styles
     *
     * @param array $input Input settings
     * @return array Sanitized settings
     */
    public function sanitize_styles($input) {
        $sanitized = array();
        
        $sanitized['primary_color'] = $this->sanitize_hex_color($input['primary_color']);
        $sanitized['background_color'] = $this->sanitize_hex_color($input['background_color']);
        $sanitized['border_radius'] = intval($input['border_radius']);
        
        return $sanitized;
    }
    
    /**
     * Sanitize hex color
     *
     * @param string $color Color to sanitize
     * @return string Sanitized color
     */
    private function sanitize_hex_color($color) {
        if ('' === $color) {
            return '';
        }
        
        // 3 or 6 hex digits, or the empty string.
        if (preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color)) {
            return $color;
        }
        
        return '';
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_assistant-mascot') {
            return;
        }
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Enqueue Three.js for 3D preview
        wp_enqueue_script(
            'three-js',
            'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js',
            array(),
            'r128',
            true
        );
        
        // Enqueue GLTFLoader for loading 3D models
        wp_enqueue_script(
            'three-gltf-loader',
            'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js',
            array('three-js'),
            '0.128.0',
            true
        );
        
        wp_enqueue_script(
            'assistant-mascot-admin',
            ASSISTANT_MASCOT_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker', 'three-js', 'three-gltf-loader'),
            ASSISTANT_MASCOT_VERSION,
            true
        );
        
        // Localize script with nonce and other data
        wp_localize_script('assistant-mascot-admin', 'assistantMascotSettings', array(
            'nonce' => wp_create_nonce('assistant_mascot_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
        
        wp_enqueue_style(
            'assistant-mascot-admin',
            ASSISTANT_MASCOT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ASSISTANT_MASCOT_VERSION
        );
    }
}
