# Developer Guide - Ray AJAX Search

## Project Structure
- `Session1.3/` - Initial search implementation
- `Session2.1/` - Enhanced search features  
- `src/` - Core source code (PSR-4)
- `mod_raysajaxsearch/` - Joomla module files

## Development Setup
1. Install PHP 8.3 and extensions
2. Install VS Code with recommended extensions
3. Clone repository
4. Run `./dev-server.sh` for local testing
5. Use `./debug-test.sh` for debugging

## Testing
- PHP syntax: `find . -name "*.php" -exec php -l {} \;`
- JavaScript: `npx eslint "**/*.js"`
- Debugging: Set breakpoints in VS Code and run debug configuration
