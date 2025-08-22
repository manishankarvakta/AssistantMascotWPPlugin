/**
 * Admin JavaScript for Assistant Mascot plugin
 *
 * @package AssistantMascot
 * @since 1.0.0
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Plugin URL for loading assets
    var ASSISTANT_MASCOT_PLUGIN_URL = '';
    
    // Try to get plugin URL from various sources
    if (typeof assistantMascotSettings !== 'undefined' && assistantMascotSettings.modelPath) {
        ASSISTANT_MASCOT_PLUGIN_URL = assistantMascotSettings.modelPath.replace('/assets/model/avater.glb', '');
    } else if (typeof wp !== 'undefined' && wp.ajax && wp.ajax.settings && wp.ajax.settings.url) {
        // Fallback: construct from WordPress AJAX URL
        var ajaxUrl = wp.ajax.settings.url;
        ASSISTANT_MASCOT_PLUGIN_URL = ajaxUrl.replace('/wp-admin/admin-ajax.php', '/wp-content/plugins/assistant-mascot/');
    } else {
        // Final fallback: use relative path
        ASSISTANT_MASCOT_PLUGIN_URL = window.location.origin + '/wp-content/plugins/assistant-mascot/';
    }
    
    console.log('Plugin URL constructed:', ASSISTANT_MASCOT_PLUGIN_URL);
    
    // Update preview when settings change
    $('#enabled, #position, #size, #animation_speed').on('change', function() {
        updatePreview();
    });
    
    // Handle range inputs
    $('input[type="range"]').on('input', function() {
        var value = $(this).val();
        var unit = $(this).attr('data-unit') || 'px';
        $(this).next('.range-value').text(value + unit);
    });
    
    // FAQ Management
    $('#add-faq-btn').on('click', function() {
        addNewFAQ();
    });
    
    // Function to update the preview
    function updatePreview() {
        var enabled = $('#enabled').is(':checked');
        var position = $('#position').val();
        var size = $('#size').val();
        var animationSpeed = $('#animation_speed').val();
        
        var previewBox = $('#assistant-mascot-preview-box');
        var previewSettings = $('#preview-settings');
        
        if (enabled) {
            previewBox.show();
            previewBox.removeClass().addClass('assistant-mascot-box');
            
            // Update preview box content based on size
            var sizeText = '';
            var sizeClass = '';
            switch(size) {
                case 'small':
                    sizeText = '200x200px';
                    sizeClass = 'size-small';
                    break;
                case 'medium':
                    sizeText = '300x300px';
                    sizeClass = 'size-medium';
                    break;
                case 'large':
                    sizeText = '400x400px';
                    sizeClass = 'size-large';
                    break;
            }
            
            previewBox.addClass(sizeClass);
            previewBox.html('<div style="text-align: center; padding: 20px;"><strong>3D Model Active</strong><br><small>avater.glb (' + sizeText + ')</small><br><small>Animation: ' + animationSpeed.charAt(0).toUpperCase() + animationSpeed.slice(1) + '</small></div>');
            
            // Update preview settings
            previewSettings.html(
                '<li>Position: ' + position.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase()) + '</li>' +
                '<li>Size: ' + size.charAt(0).toUpperCase() + size.slice(1) + ' (' + sizeText + ')</li>' +
                '<li>Animation: ' + animationSpeed.charAt(0).toUpperCase() + animationSpeed.slice(1) + '</li>'
            );
        } else {
            previewBox.hide();
            previewSettings.html('<li style="color: #999;">Plugin Disabled</li>');
        }
    }
    
    // Initialize preview on page load
    updatePreview();
    
    // 3D Model Preview functionality
    if ($('#3d-model-preview').length) {
        initialize3DPreview();
    }
    
    // Add enhanced interactivity to the preview
    $('#assistant-mascot-preview-box').on('click', function() {
        var $this = $(this);
        
        // Add click animation
        $this.addClass('clicked');
        
        // Show a temporary success message
        var originalContent = $this.html();
        $this.html('<div style="text-align: center; padding: 20px;"><strong>✓ Clicked!</strong><br><small>Preview Updated</small></div>');
        
        // Reset after 1.5 seconds
        setTimeout(function() {
            $this.removeClass('clicked');
            updatePreview();
        }, 1500);
    });
    
    // Add hover effects to feature items
    $('.feature-item').on('mouseenter', function() {
        $(this).addClass('hovered');
    }).on('mouseleave', function() {
        $(this).removeClass('hovered');
    });
    
    // FAQ Management Functions
    function addNewFAQ() {
        var faqHtml = `
            <div class="faq-item" data-faq-id="${Date.now()}">
                <div class="faq-header">
                    <input type="text" class="faq-question" placeholder="Enter your question here..." />
                    <div class="faq-actions">
                        <button type="button" class="button button-small save-faq">Save</button>
                        <button type="button" class="button button-small button-link-delete delete-faq">Delete</button>
                    </div>
                </div>
                <div class="faq-content">
                    <textarea class="faq-answer" placeholder="Enter your answer here..." rows="4"></textarea>
                </div>
            </div>
        `;
        
        $('.no-faqs').hide();
        $('#faq-list').append(faqHtml);
        
        // Focus on the new question input
        $('#faq-list .faq-item:last .faq-question').focus();
    }
    
    // Handle FAQ actions
    $(document).on('click', '.save-faq', function() {
        var $faqItem = $(this).closest('.faq-item');
        var question = $faqItem.find('.faq-question').val();
        var answer = $faqItem.find('.faq-answer').val();
        
        if (question.trim() && answer.trim()) {
            $faqItem.addClass('saved');
            $(this).text('Saved').prop('disabled', true);
            
            // Re-enable after 2 seconds
            setTimeout(function() {
                $faqItem.find('.save-faq').text('Save').prop('disabled', false);
            }, 2000);
        } else {
            alert('Please fill in both question and answer fields.');
        }
    });
    
    $(document).on('click', '.delete-faq', function() {
        if (confirm('Are you sure you want to delete this FAQ?')) {
            $(this).closest('.faq-item').remove();
            
            // Show "no FAQs" message if no FAQs remain
            if ($('#faq-list .faq-item').length === 0) {
                $('.no-faqs').show();
            }
        }
    });
    
    // Tab switching animation
    $('.nav-tab').on('click', function() {
        $('.tab-content').fadeOut(200, function() {
            $('.tab-content').fadeIn(200);
        });
    });
    
    // Load existing animation data from database on page load
    loadExistingAnimationData();
    
    // Initialize range inputs
    $('input[type="range"]').each(function() {
        var $input = $(this);
        var $value = $input.siblings('.range-value');
        var unit = $input.data('unit') || '';
        
        if ($value.length) {
            $value.text($input.val() + unit);
        }
    });
    
    // No auto-sync on page load - user must use sync button
    
    // Load existing animation data from database if available
    setTimeout(function() {
        if ($('#model-animations-list').length > 0) {
            loadExistingAnimationData();
        }
    }, 1000);
    
    // Form validation
    $('form').on('submit', function(e) {
        var enabled = $('#enabled').is(':checked');
        
        if (!enabled) {
            // if (confirm('Are you sure you want to disable the plugin? The mascot will not be visible on your website.')) {
                return true;
            // } else {
            //     e.preventDefault();
            //     return false;
            // }
        }
        
        return true;
    });
    
    // Add some helpful tooltips
    $('.form-table th').each(function() {
        var fieldName = $(this).text().toLowerCase();
        var tooltip = '';
        
        switch(fieldName) {
            case 'enable plugin':
                tooltip = 'Toggle the visibility of the mascot on your website';
                break;
            case 'position':
                tooltip = 'Choose where the mascot appears on your website';
                break;
            case 'text color':
                tooltip = 'Select the color of the text inside the mascot';
                break;
            case 'background color':
                tooltip = 'Select the background color of the mascot';
                break;
        }
        
        if (tooltip) {
            $(this).attr('title', tooltip);
        }
    });
    
    // Responsive behavior for mobile
    function handleMobileLayout() {
        if ($(window).width() <= 782) {
            $('.assistant-mascot-preview').addClass('mobile-layout');
        } else {
            $('.assistant-mascot-preview').removeClass('mobile-layout');
        }
    }
    
    $(window).on('resize', handleMobileLayout);
    handleMobileLayout();
    
    // 3D Model Preview Functions
    var adminScene, adminCamera, adminRenderer, adminModel, adminControls;
    var isAutoRotating = false;
    
    function initialize3DPreview() {
        var canvas = document.getElementById('admin-3d-canvas');
        if (!canvas) return;
        
        // Initialize Three.js scene
        adminScene = new THREE.Scene();
        
        // Create camera
        adminCamera = new THREE.PerspectiveCamera(45, canvas.width / canvas.height, 0.1, 1000);
        adminCamera.position.set(5, 3, 5);
        adminCamera.lookAt(0, 0, 0);
        
        // Create renderer
        adminRenderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true });
        adminRenderer.setSize(canvas.width, canvas.height);
        adminRenderer.setClearColor(0x000000, 0);
        adminRenderer.shadowMap.enabled = true;
        adminRenderer.shadowMap.type = THREE.PCFSoftShadowMap;
        
        // Add lights
        var ambientLight = new THREE.AmbientLight(0x404040, 0.6);
        adminScene.add(ambientLight);
        
        var directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(10, 10, 5);
        directionalLight.castShadow = true;
        adminScene.add(directionalLight);
        
        // Add grid helper
        var gridHelper = new THREE.GridHelper(10, 10, 0x888888, 0xcccccc);
        adminScene.add(gridHelper);
        
        // Load 3D model
        loadAdminModel();
        
        // Start render loop
        animate();
        
        // Handle preview controls
        $('.preview-reset').on('click', function() {
            resetAdminView();
        });
        
        $('.preview-rotate').on('click', function() {
            toggleAdminAutoRotate();
        });
        
        // Handle display settings changes
        $('input[name*="assistant_mascot_3d_settings"]').on('change', function() {
            update3DPreview();
        });
        
        // Handle model file changes - reload the model
        $('input[name="assistant_mascot_3d_settings[model_file]"]').on('change', function() {
            reloadAdminModel();
        });

        // Handle model file change
        $('input[name="assistant_mascot_3d_settings[model_file]"]').on('change', function() {
            var newModelFile = $(this).val();
            console.log('Model file changed to:', newModelFile);
            
            // Reload model in preview
            reloadAdminModel();
            
            // Reload animations from database for new model
            setTimeout(function() {
                loadExistingAnimationData();
            }, 1000); // Wait for model to load
        });
        
        // Handle tab switching
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            var targetTab = $(this).attr('href').substring(1);
            
            // Hide all tab panels
            $('.tab-panel').fadeOut(200);
            
            // Remove active class from all tabs
            $('.nav-tab').removeClass('nav-tab-active');
            
            // Show target tab panel
            $('#' + targetTab).fadeIn(200);
            
            // Add active class to clicked tab
            $(this).addClass('nav-tab-active');
            
            // Special handling for 3D Model tab
            if (targetTab === '3d-model') {
                // Initialize 3D preview if not already done
                if (!adminScene) {
                    initialize3DPreview();
                }
            }
        });
        
        // Handle refresh animations button
        $('#refresh-animations').on('click', function() {
            syncModelAnimations();
        });
        
        // Handle "All Animations" toggle
        $('#all-animations-toggle').on('change', function() {
            var allEnabled = $(this).is(':checked');
            toggleAllAnimations(allEnabled);
        });
        
        // Handle "Sync Animations" button
        $('#sync-animations').on('click', function() {
            // Show current database state before syncing
            showCurrentDatabaseState();
            
            // Then sync animations
            syncModelAnimations();
        });
        
        // Handle "Show Saved Data" button
        $('#show-saved-data').on('click', function() {
            console.log('=== MANUAL REQUEST - SHOW SAVED DATA ===');
            showSavedAnimationData();
            showFormSubmissionData();
        });
        
        // Handle individual animation checkbox changes
        $(document).on('change', 'input[name*="animation_"]', function() {
            var animationName = $(this).attr('name').replace('assistant_mascot_3d_settings[animation_', '').replace(']', '');
            var isEnabled = $(this).is(':checked');
            console.log(`Animation "${animationName}" ${isEnabled ? 'enabled' : 'disabled'}`);
            
            // Save this selection immediately
            saveSingleAnimationSelection(animationName, isEnabled);
        });
        
        // Handle "Export JSON" button (if you want to add one)
        $(document).on('click', '#export-json', function() {
            exportAnimationDataAsJSON();
        });
    }
    
    function loadAdminModel() {
        // Get the selected model file from settings
        var modelFile = $('input[name="assistant_mascot_3d_settings[model_file]"]').val() || 'avater.glb';
        var modelPath = ASSISTANT_MASCOT_PLUGIN_URL + 'assets/model/' + modelFile;
        
        console.log('Loading model from path:', modelPath);
        console.log('Model file:', modelFile);
        console.log('Plugin URL:', ASSISTANT_MASCOT_PLUGIN_URL);
        
        // Create GLTFLoader for loading GLB/GLTF files
        var loader = new THREE.GLTFLoader();
        
        // Show loading state
        var $preview = $('#3d-model-preview');
        $preview.addClass('loading');
        
        // Load the actual 3D model
        loader.load(
            modelPath,
            function(gltf) {
                // Success: Model loaded
                adminModel = gltf.scene;
                
                // Store the GLTF data including animations
                adminModel.userData.gltf = gltf;
                adminModel.userData.animations = gltf.animations;
                
                console.log('GLTF data:', gltf);
                console.log('Animations found:', gltf.animations ? gltf.animations.length : 0);
                
                // Center and scale the model
                var box = new THREE.Box3().setFromObject(adminModel);
                var center = box.getCenter(new THREE.Vector3());
                var size = box.getSize(new THREE.Vector3());
                
                // Center the model
                adminModel.position.sub(center);
                
                // Scale to fit in view
                var maxDim = Math.max(size.x, size.y, size.z);
                var scale = 2 / maxDim;
                adminModel.scale.setScalar(scale);
                
                // Enable shadows
                adminModel.traverse(function(child) {
                    if (child.isMesh) {
                        child.castShadow = true;
                        child.receiveShadow = true;
                    }
                });
                
                adminScene.add(adminModel);
                
                // Remove loading state
                $preview.removeClass('loading');
                $preview.addClass('loaded');
                
                console.log('3D Model loaded successfully:', modelFile);
                
                // No auto-sync when model is loaded
            },
            function(progress) {
                // Progress: Show loading progress
                var percent = Math.round((progress.loaded / progress.total) * 100);
                console.log('Loading model:', percent + '%');
            },
            function(error) {
                // Error: Fallback to placeholder cube
                console.error('Error loading 3D model:', error);
                createPlaceholderModel();
                $preview.removeClass('loading');
            }
        );
    }
    
    function createPlaceholderModel() {
        // Create a simple placeholder model (cube) as fallback
        var geometry = new THREE.BoxGeometry(1, 1, 1);
        var material = new THREE.MeshLambertMaterial({ 
            color: 0x00ff00,
            transparent: true,
            opacity: 0.8
        });
        
        adminModel = new THREE.Mesh(geometry, material);
        adminModel.castShadow = true;
        adminModel.receiveShadow = true;
        adminScene.add(adminModel);
        
        // Add some animation
        adminModel.rotation.x = 0.5;
        adminModel.rotation.y = 0.3;
    }
    
    function animate() {
        requestAnimationFrame(animate);
        
        // Update animation mixer
        if (animationMixer) {
            var deltaTime = 0.016; // Approximate 60fps
            animationMixer.update(deltaTime);
        }
        
        if (adminModel && isAutoRotating) {
            adminModel.rotation.y += 0.01;
        }
        
        if (adminRenderer && adminScene && adminCamera) {
            adminRenderer.render(adminScene, adminCamera);
        }
    }
    
    function resetAdminView() {
        if (adminCamera) {
            adminCamera.position.set(5, 3, 5);
            adminCamera.lookAt(0, 0, 0);
        }
        if (adminModel) {
            adminModel.rotation.x = 0.5;
            adminModel.rotation.y = 0.3;
        }
    }
    
    function toggleAdminAutoRotate() {
        isAutoRotating = !isAutoRotating;
        var $button = $('.preview-rotate');
        
        if (isAutoRotating) {
            $button.addClass('active');
        } else {
            $button.removeClass('active');
        }
    }
    
    function update3DPreview() {
        if (!adminScene || !adminModel) return;
        
        // Get current display settings
        var background = $('input[name="assistant_mascot_3d_settings[background]"]').is(':checked');
        var wireframe = $('input[name="assistant_mascot_3d_settings[wireframe]"]').is(':checked');
        var grid = $('input[name="assistant_mascot_3d_settings[grid]"]').is(':checked');
        var bgColor = $('input[name="assistant_mascot_3d_settings[bg_color]"]').val();
        
        // Update wireframe mode for all materials in the model
        adminModel.traverse(function(child) {
            if (child.isMesh && child.material) {
                if (Array.isArray(child.material)) {
                    child.material.forEach(function(mat) {
                        mat.wireframe = wireframe;
                    });
                } else {
                    child.material.wireframe = wireframe;
                }
            }
        });
        
        // Update grid visibility
        var gridHelper = adminScene.children.find(child => child instanceof THREE.GridHelper);
        if (gridHelper) {
            gridHelper.visible = grid;
        }
        
        // Update background
        if (background && bgColor) {
            adminRenderer.setClearColor(bgColor, 1);
        } else {
            adminRenderer.setClearColor(0x000000, 0);
        }
    }

    function reloadAdminModel() {
        // Hide current model
        if (adminModel) {
            adminScene.remove(adminModel);
            adminModel = null; // Clear the model object
        }

        // Show loading state
        var $preview = $('#3d-model-preview');
        $preview.addClass('loading');

        // Load the new model
        loadAdminModel();
    }
    
    // Animation Detection and Control Functions
    var modelAnimations = {};
    var animationMixer = null;
    var activeAnimations = {};
    
    function syncModelAnimations() {
        if (!adminModel || !adminModel.userData || !adminModel.userData.gltf) {
            console.log('No model loaded or no GLTF data available');
            return;
        }
        
        // Always load from database first
        loadExistingAnimationData();
        
        // Then detect new animations from model
        var animations = [];
        var gltf = adminModel.userData.gltf;
        
        if (gltf.animations && gltf.animations.length > 0) {
            console.log('Detected animations from model:', gltf.animations);
            
            gltf.animations.forEach(function(animation, index) {
                var animationName = animation.name || 'Animation_' + index;
                var duration = animation.duration || 0;
                
                animations.push({
                    name: animationName,
                    duration: duration
                });
            });
            
            // Save synced animations to database (this will skip existing ones)
            saveSyncedAnimations(animations);
        } else {
            console.log('No animations found in model');
            // Still load from database to show existing animations
            loadExistingAnimationData();
        }
    }
    
    function saveSyncedAnimations(animations) {
        // Create animation settings object with durations
        var animationSettings = {
            synced_animations: [],
            animation_durations: {}
        };
        
        // Add each animation name and duration to the synced list
        animations.forEach(function(animation) {
            var animationName = animation.name || 'Animation';
            var duration = animation.duration || 0;
            
            animationSettings.synced_animations.push(animationName);
            animationSettings.animation_durations[animationName] = duration;
        });
        
        // Save to WordPress options via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'save_synced_animations',
                settings: animationSettings,
                nonce: assistantMascotSettings.nonce || ''
            },
            success: function(response) {
                console.log('Synced animations saved:', response);
                console.log('Animation settings object:', animationSettings);
                
                // Show saved data in console
                showSavedAnimationData();
                
                // Show success message with detailed sync results
                var message = 'Animations synced successfully!';
                if (response.data) {
                    var data = response.data;
                    var details = [];
                    
                    if (data.new_animations > 0) {
                        details.push(data.new_animations + ' new animations added');
                    }
                    
                    if (data.skipped_animations > 0) {
                        details.push(data.skipped_animations + ' existing animations skipped');
                    }
                    
                    if (data.total_existing > 0) {
                        details.push(data.total_existing + ' total animations in database');
                    }
                    
                    if (details.length > 0) {
                        message += ' (' + details.join(', ') + ')';
                    }
                }
                
                showSuccessMessage(message);
                
                // Display sync summary in the UI
                displaySyncSummary(response.data);
                
                // Update the sync button to show success
                $('#sync-animations').text('✓ Synced').addClass('synced');
                setTimeout(function() {
                    $('#sync-animations').text('Sync Animations').removeClass('synced');
                }, 2000);
            },
            error: function(xhr, status, error) {
                console.error('Error saving synced animations:', error);
            }
        });
        
        console.log('Synced animations:', animationSettings);
    }
    
    function showSavedAnimationData() {
        // Get current model file
        var modelFile = $('input[name="assistant_mascot_3d_settings[model_file]"]').val() || 'avater.glb';
        
        console.log('=== FETCHING ANIMATION DATA FROM DATABASE ===');
        console.log('Model file:', modelFile);
        
        // Fetch data from database via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'load_animations_from_database',
                model_file: modelFile,
                nonce: assistantMascotSettings.nonce
            },
            success: function(response) {
                console.log('=== DATABASE RESPONSE ===');
                console.log(response);
                
                if (response.success && response.data && response.data.animations && response.data.animations.length > 0) {
                    // Display database data
                    console.log('=== ANIMATION DATA FROM DATABASE ===');
                    console.log(JSON.stringify(response.data, null, 2));
                    
                    // Show database table format
                    var dbAnimationTable = [];
                    response.data.animations.forEach(function(animation) {
                        dbAnimationTable.push({
                            'Animation Name': animation.animation_name,
                            'Duration': animation.animation_duration + 's',
                            'Status': animation.is_enabled == 1 ? 'Enabled' : 'Disabled',
                            'Created': animation.created_at,
                            'Updated': animation.updated_at
                        });
                    });
                    console.table(dbAnimationTable);
                    
                    // Show summary
                    console.log('=== DATABASE SUMMARY ===');
                    console.table({
                        'Total Animations': response.data.animations.length,
                        'Enabled Count': response.data.animations.filter(a => a.is_enabled == 1).length,
                        'Disabled Count': response.data.animations.filter(a => a.is_enabled == 0).length,
                        'Model File': modelFile
                    });
                    
                } else {
                    console.log('=== NO ANIMATIONS IN DATABASE ===');
                    console.log('No animations found in database for model:', modelFile);
                }
                
                // Also show current form state for comparison
                showCurrentFormState();
            },
            error: function(xhr, status, error) {
                console.error('=== DATABASE FETCH ERROR ===');
                console.error('Error fetching from database:', error);
                console.error('Response:', xhr.responseText);
                
                // Fallback to showing current form state
                showCurrentFormState();
            }
        });
    }
    
    function showCurrentFormState() {
        // Get current saved settings from the form
        var savedData = {
            all_animations_enabled: $('#all-animations-toggle').is(':checked'),
            global_animation_speed: parseFloat($('input[name="assistant_mascot_3d_settings[global_animation_speed]"]').val()) || 1.0,
            loop_animations: $('input[name="assistant_mascot_3d_settings[loop_animations]"]').is(':checked'),
            synced_animations: [],
            individual_animations: {}
        };
        
        // Get synced animations from the list
        $('#model-animations-list .animation-item').each(function() {
            var animationName = $(this).find('.animation-name').text();
            var isEnabled = $(this).find('input[type="checkbox"]').is(':checked');
            
            savedData.synced_animations.push(animationName);
            savedData.individual_animations[animationName] = isEnabled;
        });
        
        // Display form state in console
        console.log('=== CURRENT FORM STATE ===');
        console.log(JSON.stringify(savedData, null, 2));
        
        // Show form state in table format
        console.table({
            'All Animations': savedData.all_animations_enabled ? 'Enabled' : 'Disabled',
            'Global Speed': savedData.global_animation_speed + 'x',
            'Loop Mode': savedData.loop_animations ? 'On' : 'Off',
            'Total Animations': savedData.synced_animations.length
        });
        
        if (savedData.synced_animations.length > 0) {
            var animationTable = [];
            savedData.synced_animations.forEach(function(name) {
                animationTable.push({
                    'Animation Name': name,
                    'Status': savedData.individual_animations[name] ? 'Enabled' : 'Disabled'
                });
            });
            console.table(animationTable);
        }
    }
    
    function showCurrentSavedData() {
        // Get current form values
        var currentData = {
            all_animations_enabled: $('#all-animations-toggle').is(':checked'),
            global_animation_speed: parseFloat($('input[name="assistant_mascot_3d_settings[global_animation_speed]"]').val()) || 1.0,
            loop_animations: $('input[name="assistant_mascot_3d_settings[loop_animations]"]').is(':checked')
        };
        
        console.log('=== CURRENT FORM VALUES (JSON) ===');
        console.log(JSON.stringify(currentData, null, 2));
        
        // Try to get saved data from localStorage if available
        var savedFromStorage = localStorage.getItem('assistant_mascot_animation_settings');
        if (savedFromStorage) {
            try {
                var parsedData = JSON.parse(savedFromStorage);
                console.log('=== LOCALSTORAGE DATA (JSON) ===');
                console.log(JSON.stringify(parsedData, null, 2));
            } catch (e) {
                console.log('No valid data in localStorage');
            }
        }
        
        // Show what's currently in the database (from form fields)
        console.log('=== DATABASE VALUES (from form fields) ===');
        console.log('All Animations:', currentData.all_animations_enabled ? 'Enabled' : 'Disabled');
        console.log('Global Speed:', currentData.global_animation_speed + 'x');
        console.log('Loop Mode:', currentData.loop_animations ? 'On' : 'Off');
        
        // Check if there are any existing animation items
        var existingAnimations = $('#model-animations-list .animation-item');
        if (existingAnimations.length > 0) {
            console.log('Existing Animation Items:', existingAnimations.length);
            var animationList = [];
            existingAnimations.each(function(index) {
                var name = $(this).find('.animation-name').text();
                var enabled = $(this).find('input[type="checkbox"]').is(':checked');
                animationList.push({
                    index: index + 1,
                    name: name,
                    enabled: enabled
                });
                console.log(`  ${index + 1}. ${name}: ${enabled ? 'Enabled' : 'Disabled'}`);
            });
            
            console.log('=== EXISTING ANIMATIONS (JSON) ===');
            console.log(JSON.stringify(animationList, null, 2));
        } else {
            console.log('No animation items found - need to sync first');
        }
    }
    
    function showDatabaseState() {
        // This function will be implemented to fetch and display the actual database state
        // For now, it will just log a message indicating the function exists.
        console.log('=== DATABASE STATE ===');
        console.log('This function will fetch and display the actual database state.');
        console.log('=== END DATABASE STATE ===');
    }
    
    function showFormSubmissionData() {
        // Collect all form data that will be submitted
        var formData = {};
        
        // Get all 3D settings form fields
        $('input[name*="assistant_mascot_3d_settings"]').each(function() {
            var name = $(this).attr('name');
            var value = $(this).val();
            var type = $(this).attr('type');
            
            if (type === 'checkbox') {
                value = $(this).is(':checked');
            } else if (type === 'range') {
                value = parseFloat(value);
            }
            
            // Clean up the field name
            var cleanName = name.replace('assistant_mascot_3d_settings[', '').replace(']', '');
            formData[cleanName] = value;
        });
        
        // Show the complete form data in JSON format
        console.log('=== FORM DATA BEING SUBMITTED (JSON) ===');
        console.log(JSON.stringify(formData, null, 2));
        
        // Show animation-specific data
        var animationData = {
            all_animations_enabled: formData.all_animations_enabled,
            global_animation_speed: formData.global_animation_speed,
            loop_animations: formData.loop_animations,
            synced_animations: [],
            individual_animations: {}
        };
        
        // Extract animation fields
        Object.keys(formData).forEach(function(key) {
            if (key.startsWith('animation_')) {
                var animationName = key.replace('animation_', '');
                animationData.synced_animations.push(animationName);
                animationData.individual_animations[animationName] = formData[key];
            }
        });
        
        console.log('=== ANIMATION SETTINGS TO BE SAVED (JSON) ===');
        console.log(JSON.stringify(animationData, null, 2));
        
        // Show in table format for easy reading
        console.table({
            'All Animations': animationData.all_animations_enabled ? 'Enabled' : 'Disabled',
            'Global Speed': animationData.global_animation_speed + 'x',
            'Loop Mode': animationData.loop_animations ? 'On' : 'Off',
            'Total Animations': animationData.synced_animations.length
        });
        
        if (animationData.synced_animations.length > 0) {
            var animationTable = [];
            animationData.synced_animations.forEach(function(name) {
                animationTable.push({
                    'Animation': name,
                    'Status': animationData.individual_animations[name] ? 'Enabled' : 'Disabled'
                });
            });
            console.table(animationTable);
        }
        
        console.log('=== END FORM SUBMISSION DATA ===');
    }
    
    function saveAnimationSelectionsToDatabase() {
        // Collect all animation selections
        var animationSelections = {};
        
        // Get all animation checkboxes
        $('#model-animations-list input[type="checkbox"]').each(function() {
            var $checkbox = $(this);
            var animationName = $checkbox.attr('name');
            
            if (animationName && animationName.includes('animation_')) {
                var cleanName = animationName.replace('assistant_mascot_3d_settings[animation_', '').replace(']', '');
                animationSelections[cleanName] = $checkbox.is(':checked');
            }
        });
        
        // Save to database via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'save_animation_selections',
                selections: animationSelections,
                nonce: assistantMascotSettings.nonce || ''
            },
            success: function(response) {
                console.log('Animation selections saved to database:', response);
            },
            error: function(xhr, status, error) {
                console.error('Error saving animation selections:', error);
            }
        });
        
        console.log('Animation selections to save:', animationSelections);
    }
    
    function showCurrentDatabaseState() {
        // Get current model file
        var modelFile = $('input[name="assistant_mascot_3d_settings[model_file]"]').val() || 'avater.glb';
        
        // Show current database state
        console.log('=== CURRENT DATABASE STATE BEFORE SYNC ===');
        console.log('Model file:', modelFile);
        
        // Check if there are existing animations in the list
        var existingAnimations = $('#model-animations-list .animation-item');
        if (existingAnimations.length > 0) {
            console.log('Existing animations in UI:', existingAnimations.length);
            var animationNames = [];
            existingAnimations.each(function() {
                var name = $(this).find('.animation-name').text();
                var enabled = $(this).find('input[type="checkbox"]').is(':checked');
                animationNames.push({ name: name, enabled: enabled });
            });
            console.log('Animation details:', animationNames);
        } else {
            console.log('No existing animations found in UI');
        }
        
        // Show message to user
        if (existingAnimations.length > 0) {
            showInfoMessage('Checking database for existing animations...');
        } else {
            showInfoMessage('No existing animations found. Will create new entries.');
        }
    }
    
    function showInfoMessage(message) {
        // Remove any existing info messages
        $('.assistant-mascot-info-notice').remove();
        
        // Create info notice
        var infoNotice = $('<div class="notice notice-info is-dismissible assistant-mascot-info-notice"><p>' + message + '</p></div>');
        
        // Insert at the top of the page content
        $('.wrap h1').after(infoNotice);
        
        // Auto-dismiss after 3 seconds
        setTimeout(function() {
            infoNotice.fadeOut(500, function() {
                $(this).remove();
            });
        }, 3000);
        
        // Make dismissible
        infoNotice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
        
        // Handle dismiss button click
        infoNotice.find('.notice-dismiss').on('click', function() {
            infoNotice.fadeOut(500, function() {
                $(this).remove();
            });
        });
    }
    
    function displaySyncSummary(syncData) {
        if (!syncData) return;
        
        // Remove any existing sync summary
        $('.sync-summary').remove();
        
        // Create summary HTML
        var summaryHtml = '<div class="sync-summary notice notice-success is-dismissible">';
        summaryHtml += '<h4>Sync Summary</h4>';
        summaryHtml += '<ul>';
        
        if (syncData.new_animations > 0) {
            summaryHtml += '<li><strong>New Animations:</strong> ' + syncData.new_animations + ' added</li>';
        }
        
        if (syncData.skipped_animations > 0) {
            summaryHtml += '<li><strong>Existing Animations:</strong> ' + syncData.skipped_animations + ' skipped (preserved)</li>';
        }
        
        if (syncData.total_existing > 0) {
            summaryHtml += '<li><strong>Total in Database:</strong> ' + syncData.total_existing + ' animations</li>';
        }
        
        if (syncData.total_processed > 0) {
            summaryHtml += '<li><strong>Total Processed:</strong> ' + syncData.total_processed + ' animations from model</li>';
        }
        
        summaryHtml += '</ul>';
        summaryHtml += '<p><em>Existing animation selections have been preserved.</em></p>';
        summaryHtml += '</div>';
        
        // Insert after the animation list
        $('#model-animations-list').after(summaryHtml);
        
        // Auto-remove after 10 seconds
        setTimeout(function() {
            $('.sync-summary').fadeOut(500, function() {
                $(this).remove();
            });
        }, 10000);
        
        // Make dismissible
        $('.sync-summary').append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
        
        // Handle dismiss button click
        $('.sync-summary .notice-dismiss').on('click', function() {
            $('.sync-summary').fadeOut(500, function() {
                $(this).remove();
            });
        });
    }
    
    function loadExistingAnimationData() {
        // Get current model file
        var modelFile = $('input[name="assistant_mascot_3d_settings[model_file]"]').val() || 'avater.glb';
        
        console.log('Loading existing animation data for model:', modelFile);
        
        // Show loading state
        $('#model-animations-list').html('<div class="loading-animations"><span class="dashicons dashicons-update-alt"></span><span>Loading animations from database...</span></div>');
        
        // AJAX call to load animations from database
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'load_animations_from_database',
                model_file: modelFile,
                nonce: assistantMascotSettings.nonce
            },
            success: function(response) {
                console.log('Loaded animations from database:', response);
                
                if (response.success && response.data && response.data.animations && response.data.animations.length > 0) {
                    // Display existing animations
                    displayExistingAnimations(response.data);
                } else {
                    // No animations in database
                    $('#model-animations-list').html('<div class="no-animations"><span class="dashicons dashicons-info"></span><span>No animations found in database for this model. Click "Sync Animations" to detect and add animations from the model.</span></div>');
                    console.log('No animations found in database for model:', modelFile);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading animations from database:', error);
                $('#model-animations-list').html('<div class="no-animations"><span class="dashicons dashicons-warning"></span><span>Error loading animations from database. Please try again.</span></div>');
            }
        });
    }
    
    function displayExistingAnimations(data) {
        var $animationsList = $('#model-animations-list');
        
        // Clear existing list
        $animationsList.empty();
        
        // Create animation mixer if it doesn't exist
        if (!animationMixer && adminModel) {
            animationMixer = new THREE.AnimationMixer(adminModel);
        }
        
        // Process each animation from database
        data.animations.forEach(function(animationData, index) {
            var animationName = animationData.animation_name;
            var duration = parseFloat(animationData.animation_duration).toFixed(2);
            var isEnabled = animationData.is_enabled == 1;
            
            console.log('Displaying animation from database:', animationName, 'Duration:', duration, 'Enabled:', isEnabled);
            
            // Try to find the actual animation clip from the model
            var animationClip = null;
            if (adminModel && adminModel.userData && adminModel.userData.gltf && adminModel.userData.gltf.animations) {
                animationClip = adminModel.userData.gltf.animations.find(function(anim) {
                    return anim.name === animationName;
                });
            }
            
            // Store animation reference if found
            if (animationClip) {
                modelAnimations[animationName] = {
                    animation: animationClip,
                    action: null,
                    index: index
                };
                
                // Create animation action
                var action = animationMixer.clipAction(animationClip);
                modelAnimations[animationName].action = action;
                
                console.log('Found animation clip for:', animationName);
            } else {
                console.log('Animation clip not found in model for:', animationName);
            }
            
            // Create HTML for animation item with form field
            var animationHtml = `
                <div class="animation-item" data-animation="${animationName}">
                    <div class="animation-info">
                        <span class="dashicons dashicons-play"></span>
                        <div>
                            <div class="animation-name">${animationName}</div>
                            <div class="animation-duration">${duration}s</div>
                        </div>
                    </div>
                    <div class="animation-toggle">
                        <input type="checkbox" name="assistant_mascot_3d_settings[animation_${animationName}]" id="anim-${index}" data-animation="${animationName}" ${isEnabled ? 'checked' : ''} />
                        <label for="anim-${index}">Enable</label>
                    </div>
                </div>
            `;
            
            $animationsList.append(animationHtml);
        });
        
        // Start animation loop if we have animations
        if (animationMixer && Object.keys(modelAnimations).length > 0) {
            animate();
        }
        
        console.log('Displayed', data.animations.length, 'animations from database');
    }
    
    function saveSingleAnimationSelection(animationName, isEnabled) {
        // Get current animation selections
        var currentSelections = {};
        if (window.assistantMascotSettings && window.assistantMascotSettings.animationSettings && window.assistantMascotSettings.animationSettings.animation_selections) {
            currentSelections = window.assistantMascotSettings.animationSettings.animation_selections;
        }
        
        // Update the selection
        currentSelections[animationName] = isEnabled;
        
        // Save to database via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'save_animation_selections',
                selections: currentSelections,
                nonce: assistantMascotSettings.nonce || ''
            },
            success: function(response) {
                console.log('Single animation selection saved:', response);
                
                // Update local settings
                if (window.assistantMascotSettings && window.assistantMascotSettings.animationSettings) {
                    window.assistantMascotSettings.animationSettings.animation_selections = currentSelections;
                }
            },
            error: function(xhr, status, error) {
                console.error('Error saving single animation selection:', error);
            }
        });
    }
    
    function exportAnimationDataAsJSON() {
        // Collect all current animation data
        var exportData = {
            timestamp: new Date().toISOString(),
            plugin_version: '1.0.0',
            animation_settings: {
                all_animations_enabled: $('#all-animations-toggle').is(':checked'),
                global_animation_speed: parseFloat($('input[name="assistant_mascot_3d_settings[global_animation_speed]"]').val()) || 1.0,
                loop_animations: $('input[name="assistant_mascot_3d_settings[loop_animations]"]').is(':checked')
            },
            synced_animations: [],
            individual_animations: {},
            model_info: {
                model_file: $('input[name="assistant_mascot_3d_settings[model_file]"]').val() || 'avater.glb',
                total_animations: 0
            }
        };
        
        // Get synced animations from the list
        $('#model-animations-list .animation-item').each(function() {
            var animationName = $(this).find('.animation-name').text();
            var isEnabled = $(this).find('input[type="checkbox"]').is(':checked');
            
            exportData.synced_animations.push(animationName);
            exportData.individual_animations[animationName] = isEnabled;
        });
        
        exportData.model_info.total_animations = exportData.synced_animations.length;
        
        // Create and download JSON file
        var dataStr = JSON.stringify(exportData, null, 2);
        var dataBlob = new Blob([dataStr], {type: 'application/json'});
        var url = URL.createObjectURL(dataBlob);
        
        var link = document.createElement('a');
        link.href = url;
        link.download = 'assistant-mascot-animations-' + new Date().toISOString().split('T')[0] + '.json';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Also show in console
        console.log('=== EXPORTED ANIMATION DATA (JSON) ===');
        console.log(JSON.stringify(exportData, null, 2));
        console.log('Data exported to:', link.download);
    }
    
    function showSuccessMessage(message) {
        // Remove any existing success messages
        $('.assistant-mascot-success-notice').remove();
        
        // Create success notice
        var successNotice = $('<div class="notice notice-success is-dismissible assistant-mascot-success-notice"><p>' + message + '</p></div>');
        
        // Insert at the top of the page content
        $('.wrap h1').after(successNotice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            successNotice.fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Make dismissible
        successNotice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
        
        // Handle dismiss button click
        successNotice.find('.notice-dismiss').on('click', function() {
            successNotice.fadeOut(500, function() {
                $(this).remove();
            });
        });
    }
    
    function toggleModelAnimation(animationName, isEnabled) {
        if (!modelAnimations[animationName] || !modelAnimations[animationName].action) {
            return;
        }
        
        var action = modelAnimations[animationName].action;
        var $animationItem = $('.animation-item[data-animation="' + animationName + '"]');
        
        if (isEnabled) {
            // Enable animation
            action.play();
            activeAnimations[animationName] = action;
            $animationItem.addClass('active');
            
            // Set loop mode based on settings
            var loopAnimations = $('input[name="assistant_mascot_3d_settings[loop_animations]"]').is(':checked');
            action.setLoop(loopAnimations ? THREE.LoopRepeat : THREE.LoopOnce);
            
            // Set animation speed
            var globalSpeed = parseFloat($('input[name="assistant_mascot_3d_settings[global_animation_speed]"]').val()) || 1.0;
            action.timeScale = globalSpeed;
            
        } else {
            // Disable animation
            action.stop();
            delete activeAnimations[animationName];
            $animationItem.removeClass('active');
        }
    }

    function toggleAllAnimations(allEnabled) {
        var $allAnimationsToggle = $('#all-animations-toggle');
        var $animationItems = $('.animation-item');

        if (allEnabled) {
            $allAnimationsToggle.addClass('active');
            $animationItems.addClass('active');
            Object.keys(modelAnimations).forEach(function(animationName) {
                toggleModelAnimation(animationName, true);
            });
        } else {
            $allAnimationsToggle.removeClass('active');
            $animationItems.removeClass('active');
            Object.keys(modelAnimations).forEach(function(animationName) {
                toggleModelAnimation(animationName, false);
            });
        }
    }
    
    function updateAnimationSettings() {
        // Update all active animations with new settings
        Object.keys(activeAnimations).forEach(function(animationName) {
            var action = activeAnimations[animationName];
            
            // Update loop mode
            var loopAnimations = $('input[name="assistant_mascot_3d_settings[loop_animations]"]').is(':checked');
            action.setLoop(loopAnimations ? THREE.LoopRepeat : THREE.LoopOnce);
            
            // Update speed
            var globalSpeed = parseFloat($('input[name="assistant_mascot_3d_settings[global_animation_speed]"]').val()) || 1.0;
            action.timeScale = globalSpeed;
        });
        
        // Save settings to frontend
        saveAnimationSettingsToFrontend();
    }
    
    function saveAnimationSettingsToFrontend() {
        // Collect all animation settings
        var animationSettings = {
            all_animations_enabled: $('#all-animations-toggle').is(':checked'),
            global_animation_speed: parseFloat($('input[name="assistant_mascot_3d_settings[global_animation_speed]"]').val()) || 1.0,
            loop_animations: $('input[name="assistant_mascot_3d_settings[loop_animations]"]').is(':checked'),
            enabled_animations: []
        };
        
        // Collect enabled individual animations
        $('.animation-toggle input[type="checkbox"]').each(function() {
            var animationName = $(this).data('animation');
            if (animationName && $(this).is(':checked')) {
                animationSettings.enabled_animations.push(animationName);
            }
        });
        
        // Save to localStorage for frontend access
        localStorage.setItem('assistant_mascot_animation_settings', JSON.stringify(animationSettings));
        
        // Also save to WordPress options via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'save_assistant_mascot_animations',
                settings: animationSettings,
                nonce: assistantMascotSettings.nonce || ''
            },
            success: function(response) {
                console.log('Animation settings saved:', response);
                showSuccessMessage('Animation settings updated successfully!');
            },
            error: function(xhr, status, error) {
                console.error('Error saving animation settings:', error);
            }
        });
        
        console.log('Animation settings saved to frontend:', animationSettings);
    }
    
    // Handle animation settings changes
    $('input[name="assistant_mascot_3d_settings[loop_animations], input[name="assistant_mascot_3d_settings[global_animation_speed]"]').on('change', function() {
        updateAnimationSettings();
    });

    // Handle form submission for animation settings
    $('form').on('submit', function() {
        if ($(this).find('input[name*="assistant_mascot_3d_settings"]').length > 0) {
            console.log('=== FORM SUBMISSION - ANIMATION SETTINGS ===');
            showFormSubmissionData();
            
            // Show success message after form submission
            setTimeout(function() {
                showSuccessMessage('Animation settings saved successfully!');
            }, 500);
        }
    });
    
    // Handle individual animation checkbox changes
    $(document).on('change', 'input[name*="animation_"]', function() {
        var animationName = $(this).attr('name').replace('assistant_mascot_3d_settings[animation_', '').replace(']', '');
        var isEnabled = $(this).is(':checked');
        console.log(`Animation "${animationName}" ${isEnabled ? 'enabled' : 'disabled'}`);
        
        // Show updated saved data
        setTimeout(function() {
            showSavedAnimationData();
        }, 100);
    });
});
