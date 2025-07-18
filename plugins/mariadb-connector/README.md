# MariaDB Connector Plugin

A FearlessCMS plugin that provides MariaDB database connectivity for other plugins.

## Features

- **Database Connection Management**: Centralized database connection handling
- **PDO Database Abstraction**: Uses PDO for secure, prepared statements
- **Connection Pooling**: Efficient connection reuse
- **Admin Interface**: Easy configuration through the admin panel
- **Hook System**: Other plugins can easily access database functionality

## Installation

1. Place the `mariadb-connector` folder in your `plugins/` directory
2. Add `mariadb-connector` to your `config/active_plugins.json` file
3. Configure your database settings in the admin panel

## Configuration

The plugin will create a default configuration file at `content/mariadb-connector/config.json` with these settings:

```json
{
    "host": "localhost",
    "database": "fearlesscms_test",
    "username": "fearlesscms",
    "password": "fearlesscms123",
    "charset": "utf8mb4",
    "options": {
        "PDO::ATTR_ERRMODE": "PDO::ERRMODE_EXCEPTION",
        "PDO::ATTR_DEFAULT_FETCH_MODE": "PDO::FETCH_ASSOC",
        "PDO::ATTR_EMULATE_PREPARES": false
    }
}
```

## Usage for Other Plugins

### Getting a Database Connection

```php
// Get the PDO connection object
$pdo = fcms_do_hook('database_connect');

if ($pdo) {
    // Use the connection
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll();
}
```

### Executing Queries

```php
// Execute a prepared statement
$stmt = fcms_do_hook('database_query', 'SELECT * FROM users WHERE id = ?', [1]);

if ($stmt) {
    $user = $stmt->fetch();
}
```

### Example Plugin Integration

```php
function my_plugin_init() {
    // Register your plugin hooks
    fcms_add_hook('init', 'my_plugin_setup');
}

function my_plugin_setup() {
    // Get database connection
    $pdo = fcms_do_hook('database_connect');
    
    if ($pdo) {
        // Create your plugin's tables
        $pdo->exec("CREATE TABLE IF NOT EXISTS my_plugin_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
}
```

## Admin Interface

Access the MariaDB Connector settings through:
**Admin Panel → Plugins → MariaDB Connector**

The admin interface provides:
- Database configuration form
- Connection testing
- Status monitoring
- Usage examples

## Requirements

- PHP with PDO MySQL extension
- MariaDB or MySQL server
- FearlessCMS with plugin system enabled

## Security

- Uses prepared statements to prevent SQL injection
- Connection credentials stored in separate config file
- Error logging for debugging
- Secure password handling

## Troubleshooting

1. **Connection Failed**: Check your database credentials and ensure MariaDB is running
2. **PDO Driver Missing**: Install the PHP PDO MySQL extension
3. **Permission Denied**: Ensure the database user has proper permissions

## License

This plugin is part of the FearlessCMS ecosystem. 