<?php
/**
 * Frontend functionality
 *
 * @package AssistantMascot
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Assistant_Mascot_Frontend {
    
    /**
     * Constructor
     */
    public function __construct() {
        try {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
            add_action('wp_footer', array($this, 'display_mascot'));
        } catch (Exception $e) {
            error_log('Assistant Mascot Frontend Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        try {
            wp_enqueue_style(
                'assistant-mascot-frontend',
                ASSISTANT_MASCOT_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                ASSISTANT_MASCOT_VERSION
                );
            
            // Enqueue Three.js from CDN
            wp_enqueue_script(
                'three-js',
                'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js',
                array(),
                'r128',
                true
            );
            
            // Enqueue GLTFLoader for .glb files
            wp_enqueue_script(
                'three-gltf-loader',
                'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js',
                array('three-js'),
                '0.128.0',
                true
            );
            
            wp_enqueue_script(
                'assistant-mascot-frontend',
                ASSISTANT_MASCOT_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery', 'three-js', 'three-gltf-loader'),
                ASSISTANT_MASCOT_VERSION,
                true
            );
            
            // Localize script with settings and model path
            $settings = get_option('assistant_mascot_settings', array());
            $animation_settings = get_option('assistant_mascot_3d_settings', array());
            
            wp_localize_script('assistant-mascot-frontend', 'assistantMascotSettings', array(
                'enabled' => isset($settings['enabled']) ? $settings['enabled'] : true,
                'position' => isset($settings['position']) ? $settings['position'] : 'bottom-right',
                'size' => isset($settings['size']) ? $settings['size'] : 'medium',
                'animationSpeed' => isset($settings['animation_speed']) ? $settings['animation_speed'] : 'normal',
                'modelPath' => ASSISTANT_MASCOT_PLUGIN_URL . 'assets/model/avater.glb',
                'animationSettings' => array(
                    'all_animations_enabled' => isset($animation_settings['all_animations_enabled']) ? $animation_settings['all_animations_enabled'] : false,
                    'global_animation_speed' => isset($animation_settings['global_animation_speed']) ? $animation_settings['global_animation_speed'] : 1.0,
                    'loop_animations' => isset($animation_settings['loop_animations']) ? $animation_settings['loop_animations'] : true,
                    'enabled_animations' => isset($animation_settings['enabled_animations']) ? $animation_settings['enabled_animations'] : array(),
                    'synced_animations' => isset($animation_settings['synced_animations']) ? $animation_settings['synced_animations'] : array(),
                    'animation_selections' => isset($animation_settings['animation_selections']) ? $animation_settings['animation_selections'] : array()
                )
            ));
        } catch (Exception $e) {
            error_log('Assistant Mascot Frontend Scripts Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Display the mascot
     */
    public function display_mascot() {
        try {
            $settings = get_option('assistant_mascot_settings', array());
            
            // Check if plugin is enabled
            if (!isset($settings['enabled']) || !$settings['enabled']) {
                return;
            }
            
            // Don't show on admin pages
            if (is_admin()) {
                return;
            }
            
            $position = isset($settings['position']) ? $settings['position'] : 'bottom-right';
            $size = isset($settings['size']) ? $settings['size'] : 'medium';
            
            ?>
            <div id="assistant-mascot" class="assistant-mascot assistant-mascot-<?php echo esc_attr($position); ?> assistant-mascot-<?php echo esc_attr($size); ?>">
                <canvas id="assistant-mascot-canvas"></canvas>
            </div>
            <?php
        } catch (Exception $e) {
            error_log('Assistant Mascot Display Error: ' . $e->getMessage());
        }
    }
}
