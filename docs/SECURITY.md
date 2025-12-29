# Security Configuration Guide

## Database Credentials Management

As part of our security improvements, all hardcoded database credentials have been removed from the codebase and replaced with environment variables.

### Changes Made:

1. **Created configuration loader** (`config/config.php`):
   - Loads environment variables from `.env` file
   - Provides secure access to configuration values

2. **Updated Database class** (`config/database.php`):
   - Removed hardcoded credentials
   - Now reads from environment variables via Config class
   - Includes validation to ensure credentials are properly configured

3. **Updated processing files**:
   - `process/procesar_ticket.php` now uses Database class instead of hardcoded credentials

### Environment Variables:

Add these to your `.env` file:
```bash
# Database Configuration
DB_HOST=localhost
DB_NAME=teqmedcl_intranet
DB_USER=teqmedcl_intranet
DB_PASSWORD=your_secure_password_here
```

### Security Best Practices:

1. **Never commit `.env` file** - It's already in `.gitignore`
2. **Use strong, unique passwords** for production environments
3. **Limit database user permissions** to only what's necessary
4. **Regularly rotate passwords** in production
5. **Use different credentials** for development and production

### Migration Instructions:

1. Copy `.env.example` to `.env` if it doesn't exist
2. Update the database credentials in `.env` with your actual values
3. Ensure the `.env` file has appropriate permissions (readable by web server, not by others)

```bash
# Set appropriate permissions (Linux/Mac)
chmod 640 .env
chown www-data:www-data .env
```

### Verification:

To verify the configuration is working:
1. Check that the application connects successfully
2. Monitor error logs for any configuration issues
3. Test with invalid credentials to ensure proper error handling
