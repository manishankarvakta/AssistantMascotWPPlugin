        // Handle form submission for animation settings
        $('form').on('submit', function() {
            if ($(this).find('input[name*="assistant_mascot_3d_settings"]').length > 0) {
                console.log('=== FORM SUBMISSION - ANIMATION SETTINGS ===');
                showFormSubmissionData();
                
                // Save animation selections to database before form submission
                saveAnimationSelectionsToDatabase();
                
                // Show success message after form submission
                setTimeout(function() {
                    showSuccessMessage('Animation settings saved successfully!');
                }, 500);
            }
        });
