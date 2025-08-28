# SQLite Connector Plugin

A FearlessCMS plugin that provides SQLite database connectivity for other plugins.

## Features

- **File-based Database**: Lightweight SQLite database stored as a single file
- **Database Connection Management**: Centralized database connection handling
- **PDO Database Abstraction**: Uses PDO for secure, prepared statements
- **Connection Pooling**: Efficient connection reuse
- **Admin Interface**: Easy configuration through the admin panel
- **Hook System**: Other plugins can easily access database functionality
- **Database Statistics**: View table counts, database size, and connection status

## Installation

1. Place the `sqlite-connector` folder in your `plugins/` directory
2. Add `sqlite-connector` to your `config/active_plugins.json` file
3. Configure your database settings in the admin panel

## Configuration

The plugin will create a default configuration file at `content/sqlite-connector/config.json` with these settings:

```json
{
    "database_path": "content/sqlite-connector/fearlesscms.db",
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
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }
}
```

## Admin Interface

Access the SQLite Connector settings through:
**Admin Panel → Plugins → SQLite Connector**

The admin interface provides:
- Database file path configuration
- Connection testing
- Database statistics (table count, file size, row counts)
- Status monitoring
- Usage examples

## Advantages of SQLite

- **Zero Configuration**: No server setup required
- **Portable**: Single file database that can be easily backed up
- **Lightweight**: Minimal resource usage
- **ACID Compliant**: Full transaction support
- **Self-contained**: No external dependencies
- **Perfect for Development**: Easy to set up and tear down

## Requirements

- PHP with PDO SQLite extension
- FearlessCMS with plugin system enabled
- Writable directory for the database file

## Security

- Uses prepared statements to prevent SQL injection
- Database file stored in separate config directory
- Error logging for debugging
- Foreign key constraints enabled by default

## Troubleshooting

1. **Connection Failed**: Check that the database directory is writable
2. **PDO Driver Missing**: Install the PHP PDO SQLite extension
3. **Permission Denied**: Ensure the web server can write to the database directory
4. **Database Locked**: Check for concurrent access or file permissions

## Migration from MariaDB

If you're migrating from MariaDB to SQLite, note these differences:

- **AUTO_INCREMENT** becomes **AUTOINCREMENT**
- **VARCHAR** becomes **TEXT**
- **DATETIME** becomes **DATETIME** (SQLite doesn't enforce format)
- **INT** becomes **INTEGER**
- **UNSIGNED** is not supported

## License

This plugin is part of the FearlessCMS ecosystem. 