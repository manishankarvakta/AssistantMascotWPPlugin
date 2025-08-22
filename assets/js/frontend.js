/**
 * Frontend JavaScript for Assistant Mascot plugin - 3D Model Version
 *
 * @package AssistantMascot
 * @since 1.0.0
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Check if settings are available
    if (typeof assistantMascotSettings === 'undefined') {
        return;
    }
    
    var settings = assistantMascotSettings;
    var mascot = $('#assistant-mascot');
    var canvas = $('#assistant-mascot-canvas')[0];
    
    // Don't proceed if plugin is disabled
    if (!settings.enabled) {
        return;
    }
    
    // Three.js variables
    var scene, camera, renderer, model;
    
    // Initialize 3D scene
    function init3DScene() {
        // Get size from settings
        var size = settings.size || 'medium';
        var canvasSize = 300; // default medium size
        
        switch(size) {
            case 'small':
                canvasSize = 200;
                break;
            case 'medium':
                canvasSize = 300;
                break;
            case 'large':
                canvasSize = 400;
                break;
        }
        
        // Ensure canvas has proper dimensions
        canvas.width = canvasSize;
        canvas.height = canvasSize;
        
        // Create scene
        scene = new THREE.Scene();
        scene.background = null; // Transparent background
        
        // Create camera positioned on positive X-axis to view model from right side
        camera = new THREE.PerspectiveCamera(45, canvas.width / canvas.height, 0.1, 1000);
        camera.position.set(12, 0, 0);
        camera.lookAt(0, -1.5, 0);
        
        // Create renderer
        renderer = new THREE.WebGLRenderer({
            canvas: canvas,
            alpha: true,
            antialias: true,
            preserveDrawingBuffer: false
        });
        renderer.setSize(canvas.width, canvas.height);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2)); // Limit pixel ratio for performance
        renderer.setClearColor(0x000000, 0); // Transparent background
        
        // Handle Three.js version differences
        if (renderer.outputEncoding !== undefined) {
            renderer.outputEncoding = THREE.sRGBEncoding;
        }
        if (renderer.toneMapping !== undefined) {
            renderer.toneMapping = THREE.ACESFilmicToneMapping;
            renderer.toneMappingExposure = 1.2;
        }
        
        // Add comprehensive lighting for X-axis facing camera
        var ambientLight = new THREE.AmbientLight(0xffffff, 0.8);
        scene.add(ambientLight);
        
        var directionalLight1 = new THREE.DirectionalLight(0xffffff, 1.0);
        directionalLight1.position.set(0, 5, 5);
        scene.add(directionalLight1);
        
        var directionalLight2 = new THREE.DirectionalLight(0xffffff, 0.6);
        directionalLight2.position.set(0, -5, -5);
        scene.add(directionalLight2);
        
        var pointLight = new THREE.PointLight(0xffffff, 1.5, 50);
        pointLight.position.set(0, 3, 0);
        scene.add(pointLight);
        
        // Add hemisphere light for better overall illumination
        var hemisphereLight = new THREE.HemisphereLight(0xffffff, 0x444444, 0.6);
        scene.add(hemisphereLight);
        
        // Add a simple test cube to verify rendering is working
        addTestCube();
        
        // Load 3D model
        loadModel();
        
        // Start render loop
        render();
    }
    
    // Add a test cube to verify rendering
    function addTestCube() {
        var geometry = new THREE.BoxGeometry(1, 1, 1);
        var material = new THREE.MeshBasicMaterial({ 
            color: 0x00ff00, 
            transparent: true, 
            opacity: 0.5 
        });
        var cube = new THREE.Mesh(geometry, material);
        cube.position.set(0, 0, 0);
        scene.add(cube);
        
        console.log('Test cube added to scene');
        
        // Remove test cube after model loads
        setTimeout(function() {
            if (scene && cube.parent) {
                scene.remove(cube);
                console.log('Test cube removed');
            }
        }, 3000);
    }
    
    // Adjust camera distance to ensure model fits perfectly
    function adjustCameraForModelFit() {
        if (!model) return;
        
        // Get the bounding box of the scaled model
        var box = new THREE.Box3().setFromObject(model);
        var size = box.getSize(new THREE.Vector3());
        
        // Calculate the maximum dimension
        var maxDim = Math.max(size.x, size.y, size.z);
        
        // Calculate optimal camera distance based on FOV and model size
        var fov = camera.fov * (Math.PI / 180); // Convert to radians
        var optimalDistance = (maxDim / 2) / Math.tan(fov / 2);
        
        // Add some margin (20%) for better fit
        var cameraDistance = optimalDistance * 1.2;
        
        // Update camera position while maintaining X-axis orientation
        // Look at the model's actual position (bottom center)
        camera.position.set(cameraDistance, 0, 0);
        camera.lookAt(0, -1.5, 0);
        
        console.log('Camera adjusted for perfect fit:', {
            'Model size': size,
            'Max dimension': maxDim,
            'Optimal distance': optimalDistance,
            'Camera distance': cameraDistance,
            'Camera position': camera.position
        });
        
        // Force a render to show the adjusted view
        renderer.render(scene, camera);
    }
    
    // Load 3D model
    function loadModel() {
        var loader = new THREE.GLTFLoader();
        
        console.log('Loading 3D model from:', settings.modelPath);
        
        loader.load(
            settings.modelPath,
            function(gltf) {
                console.log('3D model loaded successfully:', gltf);
                
                model = gltf.scene;
                
                // Ensure model is visible
                model.traverse(function(child) {
                    if (child.isMesh) {
                        child.castShadow = true;
                        child.receiveShadow = true;
                        
                        // Ensure materials are properly set
                        if (child.material) {
                            child.material.side = THREE.DoubleSide;
                            child.material.transparent = true;
                            child.material.opacity = 1.0;
                        }
                    }
                });
                
                // Center the model properly
                var box = new THREE.Box3().setFromObject(model);
                var center = box.getCenter(new THREE.Vector3());
                model.position.sub(center);
                
                // Scale model to fit perfectly within canvas
                var size = box.getSize(new THREE.Vector3());
                var maxDim = Math.max(size.x, size.y, size.z);
                
                // Calculate optimal scale to fit within canvas bounds
                // Use 80% of canvas size to ensure model fits with some margin
                var canvasSize = Math.min(canvas.width, canvas.height);
                var targetSize = canvasSize * 0.8;
                var scale = targetSize / maxDim;
                
                model.scale.setScalar(scale);
                
                console.log('Model scaled to fit canvas:', {
                    'Original size': size,
                    'Max dimension': maxDim,
                    'Canvas size': canvasSize,
                    'Target size': targetSize,
                    'Scale factor': scale
                });
                
                // Position model at bottom center of canvas
                model.position.set(0, -1.5, 0);
                
                // Rotate model to face right (side view)
                model.rotation.y = Math.PI;
                
                // Add model to scene
                scene.add(model);
                
                // Adjust camera distance to ensure model fits perfectly
                adjustCameraForModelFit();
                
                console.log('Model added to scene. Position:', model.position, 'Scale:', model.scale, 'Rotation:', model.rotation);
                
                // Add click interaction
                addClickInteraction();
                
                // Add smooth entrance animation
                mascot.hide().fadeIn(800);
                
                // Force a render to ensure model is visible
                renderer.render(scene, camera);
                
                // Load and setup animations
                if (gltf.animations && gltf.animations.length > 0) {
                    console.log('Found animations:', gltf.animations);
                    
                    // Filter to only show Walk and Survey animations
                    var allowedAnimations = ['Walk', 'Survey'];
                    var filteredAnimations = gltf.animations.filter(function(animation) {
                        return allowedAnimations.includes(animation.name);
                    });
                    
                    console.log('Filtered animations (Walk & Survey only):', filteredAnimations);
                    
                    // Create animation mixer
                    animationMixer = new THREE.AnimationMixer(gltf.scene);
                    
                    // Setup only the allowed animations
                    filteredAnimations.forEach(function(animation) {
                        var action = animationMixer.clipAction(animation);
                        modelAnimations[animation.name] = {
                            animation: animation,
                            action: action
                        };
                        
                        console.log('Frontend: Setup animation:', animation.name, 'Duration:', animation.duration);
                    });
                    
                    console.log('Frontend: Available animations:', Object.keys(modelAnimations));
                    console.log('Frontend: Total animations found:', gltf.animations.length, 'Filtered to:', filteredAnimations.length);
                    
                    // Start with Walk animation if available
                    if (modelAnimations['Walk']) {
                        startAnimation('Walk');
                        console.log('Frontend: Started with Walk animation');
                    } else if (modelAnimations['Survey']) {
                        startAnimation('Survey');
                        console.log('Frontend: Started with Survey animation');
                    } else {
                        console.log('Frontend: No Walk or Survey animations found');
                    }
                    
                    // Start both animations if available
                    if (modelAnimations['Walk'] && modelAnimations['Survey']) {
                        startAnimation('Walk');
                        startAnimation('Survey');
                        console.log('Frontend: Started both Walk and Survey animations');
                    } else if (modelAnimations['Walk']) {
                        startAnimation('Walk');
                        console.log('Frontend: Started Walk animation only');
                    } else if (modelAnimations['Survey']) {
                        startAnimation('Survey');
                        console.log('Frontend: Started Survey animation only');
                    } else {
                        console.log('Frontend: No Walk or Survey animations found');
                    }
                    
                    // Display active animation list
                    showActiveAnimations();
                }
            },
            function(xhr) {
                var progress = (xhr.loaded / xhr.total * 100);
                console.log('Loading progress:', progress.toFixed(1) + '%');
            },
            function(error) {
                console.error('An error occurred loading the 3D model:', error);
                console.error('Error details:', error.message || error);
                // Fallback to text if model fails to load
                fallbackToText();
            }
        );
    }
    
    // Fallback to text if 3D model fails
    function fallbackToText() {
        mascot.html('<div class="assistant-mascot-text">Plugin active</div>');
        mascot.addClass('fallback-text');
    }
    
    // Add click interaction
    function addClickInteraction() {
        mascot.on('click', function() {
            if (model) {
                // Rotate model on click
                model.rotation.y += Math.PI / 2;
                
                // Add click animation class
                $(this).addClass('clicked');
                setTimeout(function() {
                    mascot.removeClass('clicked');
                }, 300);
            }
        });
    }
    
    // Animation control functions
    var modelAnimations = {};
    var animationMixer;
    var currentAnimations = []; // Track multiple active animations
    
    // Dummy FAQ list
    var dummyFAQList = [
        {
            id: 1,
            question: "What is the Assistant Mascot?",
            answer: "The Assistant Mascot is a 3D animated character that helps guide users through your website. It provides visual assistance and makes the user experience more engaging.",
            category: "General",
            created_at: "2024-01-01",
            is_active: true
        },
        {
            id: 2,
            question: "How do I customize the mascot?",
            answer: "You can customize the mascot through the WordPress admin panel. Go to Assistant Mascot settings to change position, size, animations, and other properties.",
            category: "Customization",
            created_at: "2024-01-01",
            is_active: true
        },
        {
            id: 3,
            question: "Which animations are available?",
            answer: "Currently, the mascot supports Walk and Survey animations. These can be enabled/disabled and controlled through the admin settings.",
            category: "Animations",
            created_at: "2024-01-01",
            is_active: true
        },
        {
            id: 4,
            question: "Can I change the mascot position?",
            answer: "Yes! You can position the mascot in four locations: bottom-right, bottom-left, top-right, or top-left. This is configurable in the admin settings.",
            category: "Positioning",
            created_at: "2024-01-01",
            is_active: true
        },
        {
            id: 5,
            question: "What sizes are available?",
            answer: "The mascot comes in three sizes: Small (200x200px), Medium (300x300px), and Large (400x400px). Choose the size that best fits your website design.",
            category: "Sizing",
            created_at: "2024-01-01",
            is_active: true
        },
        {
            id: 6,
            question: "How do I disable the mascot?",
            answer: "You can disable the mascot completely by going to the admin settings and unchecking the 'Enable Plugin' option. This will hide the mascot from your website.",
            category: "General",
            created_at: "2024-01-01",
            is_active: true
        },
        {
            id: 7,
            question: "Is the mascot mobile-friendly?",
            answer: "Yes! The mascot is fully responsive and adapts to different screen sizes. It automatically adjusts its size and position for mobile devices.",
            category: "Mobile",
            created_at: "2024-01-01",
            is_active: true
        },
        {
            id: 8,
            question: "Can I use custom 3D models?",
            answer: "Currently, the plugin uses the default avater.glb model. Custom model support may be added in future updates.",
            category: "Models",
            created_at: "2024-01-01",
            is_active: false
        }
    ];
    
    // Console log the dummy FAQ list
    console.log('=== DUMMY FAQ LIST ===');
    console.log('Total FAQs:', dummyFAQList.length);
    console.log('Active FAQs:', dummyFAQList.filter(faq => faq.is_active).length);
    console.log('Inactive FAQs:', dummyFAQList.filter(faq => !faq.is_active).length);
    
    // Show FAQ list in table format
    console.table(dummyFAQList);
    
    // Show FAQs by category
    var categories = {};
    dummyFAQList.forEach(function(faq) {
        if (!categories[faq.category]) {
            categories[faq.category] = [];
        }
        categories[faq.category].push(faq.question);
    });
    
    console.log('=== FAQS BY CATEGORY ===');
    Object.keys(categories).forEach(function(category) {
        console.log(`${category} (${categories[category].length}):`, categories[category]);
    });
    
    // Show sample FAQ object structure
    console.log('=== SAMPLE FAQ OBJECT STRUCTURE ===');
    console.log(JSON.stringify(dummyFAQList[0], null, 2));
    
    console.log('=== END DUMMY FAQ LIST ===');

    function startAnimation(animationName) {
        // Only allow Walk and Survey animations
        var allowedAnimations = ['Walk', 'Survey'];
        if (!allowedAnimations.includes(animationName)) {
            console.log('Animation not allowed on frontend:', animationName);
            return;
        }
        
        if (modelAnimations[animationName]) {
            // Start the selected animation without stopping others
            var action = modelAnimations[animationName].action;
            action.reset();
            action.setLoop(THREE.LoopRepeat);
            action.play();
            
            currentAnimations.push(animationName);
            console.log('Started animation:', animationName);
        }
    }
    
    function stopAnimation(animationName) {
        // Only allow Walk and Survey animations
        var allowedAnimations = ['Walk', 'Survey'];
        if (!allowedAnimations.includes(animationName)) {
            return;
        }
        
        if (modelAnimations[animationName]) {
            modelAnimations[animationName].action.stop();
            
            // Remove from current animations array
            var index = currentAnimations.indexOf(animationName);
            if (index > -1) {
                currentAnimations.splice(index, 1);
            }
            
            console.log('Stopped animation:', animationName);
        }
    }
    
    function toggleAnimation(animationName) {
        // Only allow Walk and Survey animations
        var allowedAnimations = ['Walk', 'Survey'];
        if (!allowedAnimations.includes(animationName)) {
            return;
        }
        
        if (modelAnimations[animationName]) {
            var action = modelAnimations[animationName].action;
            if (action.isRunning()) {
                action.stop();
                
                // Remove from current animations array
                var index = currentAnimations.indexOf(animationName);
                if (index > -1) {
                    currentAnimations.splice(index, 1);
                }
                
                console.log('Stopped animation:', animationName);
            } else {
                action.reset();
                action.setLoop(THREE.LoopRepeat);
                action.play();
                
                // Add to current animations array
                currentAnimations.push(animationName);
                
                console.log('Started animation:', animationName);
            }
        }
    }
    
    function showActiveAnimations() {
        console.log('=== FRONTEND ACTIVE ANIMATIONS ===');
        console.log('Model file:', settings.modelPath || 'avater.glb');
        console.log('Animation speed setting:', settings.animationSpeed || 'normal');
        
        if (Object.keys(modelAnimations).length > 0) {
            console.log('Active animations on model:');
            
            var animationTable = [];
            Object.keys(modelAnimations).forEach(function(animationName) {
                var animation = modelAnimations[animationName];
                var isRunning = animation.action.isRunning();
                var isLooping = animation.action.loop === THREE.LoopRepeat;
                
                animationTable.push({
                    'Animation Name': animationName,
                    'Duration': animation.animation.duration.toFixed(2) + 's',
                    'Status': isRunning ? 'Playing' : 'Stopped',
                    'Loop Mode': isLooping ? 'Looping' : 'Once',
                    'Current Time': animation.action.time.toFixed(2) + 's'
                });
                
                console.log(`- ${animationName}: ${animation.animation.duration.toFixed(2)}s (${isRunning ? 'Playing' : 'Stopped'})`);
            });
            
            console.table(animationTable);
            
            // Show current animation
            if (currentAnimations.length > 0) {
                console.log('Currently playing:', currentAnimations);
            } else {
                console.log('No animation currently playing');
            }
        } else {
            console.log('No animations loaded on model');
        }
        
        console.log('=== END ANIMATION LIST ===');
    }
    

    
    // Render function with continuous loop
    function render() {
        requestAnimationFrame(render);
        
        // Update animation mixer if available
        if (animationMixer) {
            var deltaTime = 0.008; // Reduced from 0.016 for slower animations
            animationMixer.update(deltaTime);
        }
        
        renderer.render(scene, camera);
    }
    
    // Handle window resize
    function onWindowResize() {
        if (camera && renderer && canvas) {
            // Get the actual container dimensions
            var container = mascot[0];
            var rect = container.getBoundingClientRect();
            
            // Update canvas size to match container
            canvas.width = rect.width;
            canvas.height = rect.height;
            
            // Update camera aspect ratio
            camera.aspect = canvas.width / canvas.height;
            camera.updateProjectionMatrix();
            
            // Update renderer size
            renderer.setSize(canvas.width, canvas.height);
            
            console.log('Resized to:', canvas.width, 'x', canvas.height);
            
            // Recalculate camera fit for new canvas size
            if (model) {
                adjustCameraForModelFit();
            }
            
            // Force a render
            renderer.render(scene, camera);
        }
    }
    
    // Apply position settings
    function applySettings() {
        if (mascot.length) {
            // Remove all position classes and add the current one
            mascot.removeClass('assistant-mascot-top-left assistant-mascot-top-right assistant-mascot-bottom-left assistant-mascot-bottom-right');
            mascot.addClass('assistant-mascot-' + settings.position);
        }
    }
    
    // Initialize
    applySettings();
    
    // Debug function to check Three.js loading
    function checkThreeJSLoaded() {
        if (typeof THREE !== 'undefined' && typeof THREE.GLTFLoader !== 'undefined') {
            console.log('Three.js and GLTFLoader loaded successfully');
            return true;
        } else {
            console.log('Waiting for Three.js to load...');
            return false;
        }
    }
    
    // Wait for Three.js to load
    if (checkThreeJSLoaded()) {
        init3DScene();
    } else {
        // Wait for Three.js to load
        var checkThreeJS = setInterval(function() {
            if (checkThreeJSLoaded()) {
                clearInterval(checkThreeJS);
                init3DScene();
            }
        }, 100);
        
        // Timeout after 10 seconds
        setTimeout(function() {
            if (checkThreeJS) {
                clearInterval(checkThreeJS);
                console.error('Three.js failed to load within 10 seconds');
                fallbackToText();
            }
        }, 10000);
    }
    
    // Handle window resize
    $(window).on('resize', onWindowResize);
    
    // Add keyboard accessibility
    mascot.attr('tabindex', '0').attr('role', 'button').attr('aria-label', '3D Plugin status indicator');
    
    mascot.on('keydown', function(e) {
        if (e.keyCode === 13 || e.keyCode === 32) { // Enter or Space
            e.preventDefault();
            $(this).click();
        }
    });
    
    // Performance optimization: Throttle resize events
    var resizeTimeout;
    $(window).on('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            onWindowResize();
        }, 100);
    });
});
