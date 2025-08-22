# Assistant Mascot WordPress Plugin

A professional WordPress plugin that displays a customizable red square with "Plugin active" text on the frontend and provides a comprehensive admin area for configuration.

## Features

- **Visual Indicator**: Displays a red square with "Plugin active" text on your website
- **Customizable Position**: Choose from 4 different positions (top-left, top-right, bottom-left, bottom-right)
- **Color Customization**: Customize both text and background colors
- **Admin Interface**: Easy-to-use settings page in WordPress admin
- **Responsive Design**: Works perfectly on all devices and screen sizes
- **Accessibility**: Keyboard navigation and screen reader support
- **Performance Optimized**: Lightweight and fast loading
- **Internationalization Ready**: Full i18n support with POT files

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Modern web browser with JavaScript enabled

## Installation

### Manual Installation

1. Download the plugin files
2. Upload the `assistant-mascot` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to 'Assistant Mascot' in your admin menu to configure settings

### Development Installation

1. Clone this repository to your local development environment
2. Copy the plugin folder to your WordPress development site's `/wp-content/plugins/` directory
3. Activate the plugin
4. Make your changes and test

## File Structure

```
assistant-mascot/
├── assistant-mascot.php          # Main plugin file
├── includes/                     # PHP classes
│   ├── class-assistant-mascot.php
│   ├── class-assistant-mascot-admin.php
│   └── class-assistant-mascot-frontend.php
├── assets/                       # Frontend assets
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   └── js/
│       ├── admin.js
│       └── frontend.js
├── languages/                    # Internationalization files
│   └── assistant-mascot.pot
├── readme.txt                    # WordPress.org readme
├── README.md                     # This file
├── .gitignore                    # Git ignore rules
└── index.php                     # Security file
```

## Usage

### Admin Configuration

1. Navigate to **Assistant Mascot** in your WordPress admin menu
2. Configure the following settings:
   - **Enable Plugin**: Toggle the mascot visibility
   - **Position**: Choose from 4 corner positions
   - **Text Color**: Select the text color
   - **Background Color**: Select the background color
3. Save your changes

### Frontend Display

The plugin automatically displays the mascot on your website based on your settings. The mascot will appear as a red square with "Plugin active" text in the position you've configured.

## Development

### Adding New Features

1. **New Settings**: Add new fields to the admin class and update the sanitization method
2. **New Positions**: Extend the position options in both admin and frontend classes
3. **Custom Styling**: Modify the CSS files to change the appearance
4. **JavaScript Functionality**: Enhance the frontend JavaScript for additional interactions

### Code Standards

- Follow WordPress coding standards
- Use proper PHPDoc comments
- Maintain consistent naming conventions
- Keep functions and classes focused and single-purpose
- Use WordPress hooks and filters appropriately

### Testing

- Test on different WordPress versions (5.0+)
- Test on various devices and screen sizes
- Verify accessibility features work correctly
- Test with different themes and plugins

## Hooks and Filters

The plugin provides several hooks for customization:

### Actions

- `assistant_mascot_init` - Fired when the plugin initializes
- `assistant_mascot_admin_page` - Fired when rendering the admin page
- `assistant_mascot_frontend_display` - Fired when displaying the frontend mascot

### Filters

- `assistant_mascot_settings` - Filter the plugin settings
- `assistant_mascot_position` - Filter the mascot position
- `assistant_mascot_colors` - Filter the mascot colors

## Internationalization

The plugin is fully internationalization-ready:

1. Use the provided POT file as a template
2. Translate strings to your target language
3. Save as `.po` file and compile to `.mo`
4. Place in the `languages/` directory

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+
- Internet Explorer 11+ (with polyfills)

## Performance

- Minimal database queries
- Optimized CSS and JavaScript
- Efficient DOM manipulation
- Responsive design without performance impact

## Security

- All user inputs are sanitized
- Nonce verification for forms
- Capability checks for admin functions
- XSS protection implemented
- CSRF protection enabled

## Troubleshooting

### Common Issues

1. **Mascot not visible**: Check if the plugin is enabled in settings
2. **Colors not updating**: Clear browser cache and refresh
3. **Position not changing**: Verify JavaScript is loading correctly
4. **Admin page not accessible**: Check user capabilities

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support and questions:
- Check the FAQ section
- Review the code documentation
- Create an issue on GitHub
- Contact the plugin author

## Changelog

### Version 1.0.0
- Initial release
- Basic mascot functionality
- Admin settings interface
- Color and position customization
- Responsive design
- Accessibility features

## Credits

- Built with WordPress best practices
- Uses WordPress native UI components
- Follows accessibility guidelines
- Optimized for performance and user experience
