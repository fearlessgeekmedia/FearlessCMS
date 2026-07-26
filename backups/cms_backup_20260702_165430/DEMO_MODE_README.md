# FearlessCMS Demo Mode

Demo Mode allows users to explore FearlessCMS with temporary credentials without affecting real data. This creates a safe environment for testing and demonstration purposes.

## Features

- **Temporary Demo User**: Username `demo`, password `demo`
- **Isolated Environment**: Demo content is separate from real content
- **Session Management**: Automatic session timeout (1 hour)
- **Sample Content**: Pre-loaded demo pages and blog posts
- **Full Admin Access**: Complete admin functionality for testing
- **Automatic Cleanup**: Sessions and content are automatically cleaned up

## Quick Start

### Enable Demo Mode

Run the setup script:
```bash
php enable-demo.php
```

Or manually enable through the admin panel:
1. Log in as an administrator
2. Go to "Demo Mode" in the navigation
3. Click "Enable Demo Mode"

### Access Demo

1. Go to `/admin/login`
2. Use credentials:
   - **Username**: `demo`
   - **Password**: `demo`
3. Explore the demo website and admin panel

## Demo Content

The demo includes:

### Pages
- **Home**: Welcome page with feature overview
- **About**: Information about the demo
- **Contact**: Contact page template

### Blog Posts
- **Welcome to FearlessCMS**: Introduction to the CMS
- **Customizing Your Theme**: Theme customization guide

### Configuration
- Demo-specific site settings
- Sample theme options
- Isolated configuration files

## Session Management

### Session Timeout
- Demo sessions expire after 1 hour of inactivity
- Users are automatically logged out when sessions expire
- Expired sessions are cleaned up automatically

### Session Cleanup
Run cleanup manually:
```bash
php demo-cleanup.php
```

Or via web (with proper key):
```
http://localhost/demo-cleanup.php?cleanup_key=demo_cleanup_2024
```

## Security Features

### Isolation
- Demo content is stored in separate directories
- Demo configuration is isolated from production config
- No real data is affected by demo activities

### Access Control
- Demo mode can only be enabled by administrators
- Demo sessions are clearly marked in the admin interface
- Automatic cleanup prevents resource accumulation

### Session Security
- Demo sessions use the same security measures as regular sessions
- CSRF protection is maintained
- Session regeneration on login

## File Structure

```
FearlessCMS/
├── demo_content/           # Demo content directory
│   ├── pages/              # Demo pages
│   └── blog/               # Demo blog posts
├── demo_config/            # Demo configuration
│   ├── config.json         # Demo site settings
│   └── theme_options.json  # Demo theme options
├── config/
│   └── demo_mode.json      # Demo mode configuration
├── includes/
│   └── DemoModeManager.php # Demo mode management class
└── admin/templates/
    └── demo-mode.php       # Demo mode admin interface
```

## Configuration

### Demo Mode Settings

The demo mode configuration is stored in `config/demo_mode.json`:

```json
{
    "enabled": true,
    "demo_user": {
        "username": "demo",
        "password": "demo",
        "role": "administrator"
    },
    "session_timeout": 3600,
    "cleanup_interval": 86400,
    "max_demo_sessions": 10
}
```

### Environment Variables

- `FCMS_DEBUG=true`: Enables debug mode for development
- `FCMS_CONFIG_DIR`: Override config directory location

## Admin Interface

### Demo Mode Management

Access demo mode management through:
- Admin Panel → Demo Mode
- Direct URL: `/admin?action=demo_mode`

Features:
- Enable/disable demo mode
- View demo session status
- Monitor active demo sessions
- Configure demo settings

### Demo Mode Indicator

When in demo mode, the admin interface shows:
- "DEMO MODE" badge in the navigation
- Demo session information
- Clear indication of temporary environment

## Troubleshooting

### Common Issues

1. **Demo mode not working**
   - Check if demo mode is enabled in admin panel
   - Verify demo user exists in users.json
   - Check file permissions on demo directories

2. **Session expires too quickly**
   - Adjust `session_timeout` in demo_mode.json
   - Check server session configuration

3. **Demo content not loading**
   - Verify demo content directories exist
   - Check file permissions
   - Run demo setup script again

### Debug Mode

Enable debug mode to see detailed logs:
```bash
export FCMS_DEBUG=true
php enable-demo.php
```

## Development

### Adding Demo Content

To add new demo content:

1. Create content files in `demo_content/` directory
2. Use the same format as regular content files
3. Include `"demo_page": true` in frontmatter
4. Run demo setup to refresh content

### Customizing Demo Settings

Modify `DemoModeManager.php` to:
- Change session timeout
- Add new demo content
- Modify cleanup behavior
- Add custom demo features

## Production Considerations

### Security
- Disable demo mode in production environments
- Use strong cleanup keys for web cleanup
- Monitor demo session activity
- Regular cleanup of expired sessions

### Performance
- Demo mode adds minimal overhead
- Cleanup scripts prevent resource accumulation
- Isolated content prevents conflicts

### Maintenance
- Regular cleanup of expired sessions
- Monitor demo mode usage
- Update demo content periodically

## API Reference

### DemoModeManager Class

```php
$demoManager = new DemoModeManager();

// Check if demo mode is enabled
$demoManager->isEnabled();

// Enable/disable demo mode
$demoManager->enable();
$demoManager->disable();

// Check if current session is demo
$demoManager->isDemoSession();

// Start demo session
$demoManager->startDemoSession($username);

// End demo session
$demoManager->endDemoSession();

// Get demo status
$status = $demoManager->getStatus();
```

## Support

For issues with demo mode:
1. Check the troubleshooting section
2. Review error logs
3. Verify file permissions
4. Contact support with specific error messages

---

**Note**: Demo mode is designed for testing and demonstration purposes. Always disable demo mode in production environments and use strong authentication for real users.