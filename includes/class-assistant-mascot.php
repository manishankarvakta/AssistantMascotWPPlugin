<?php
/**
 * Main plugin class
 *
 * @package AssistantMascot
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Assistant_Mascot {
    
    /**
     * Plugin version
     *
     * @var string
     */
    private $version;
    
    /**
     * Plugin settings
     *
     * @var array
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->version = ASSISTANT_MASCOT_VERSION;
        $this->settings = get_option('assistant_mascot_settings', array());
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        try {
            // Load text domain
            add_action('init', array($this, 'load_textdomain'));
            
            // Initialize admin
            if (is_admin()) {
                new Assistant_Mascot_Admin();
            }
            
            // Initialize frontend
            new Assistant_Mascot_Frontend();
            
            // Add settings link to plugins page
            add_filter('plugin_action_links_' . ASSISTANT_MASCOT_PLUGIN_BASENAME, array($this, 'add_settings_link'));
        } catch (Exception $e) {
            error_log('Assistant Mascot Init Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Load text domain for internationalization
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'assistant-mascot',
            false,
            dirname(ASSISTANT_MASCOT_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Add settings link to plugins page
     *
     * @param array $links Plugin action links
     * @return array Modified plugin action links
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=assistant-mascot') . '">' . __('Settings', 'assistant-mascot') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Get plugin settings
     *
     * @return array Plugin settings
     */
    public function get_settings() {
        return $this->settings;
    }
    
    /**
     * Update plugin settings
     *
     * @param array $settings New settings
     * @return bool Whether the settings were updated successfully
     */
    public function update_settings($settings) {
        $this->settings = $settings;
        return update_option('assistant_mascot_settings', $settings);
    }
}
