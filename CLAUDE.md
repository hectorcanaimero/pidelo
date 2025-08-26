# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**MyD Delivery Pro** is a commercial WordPress plugin that creates a complete delivery management system with products, orders, customer management, WhatsApp integration, and payment processing.

## Architecture

### Core Structure
- **Plugin Entry Point**: `myd-delivery-pro.php` - Main plugin file with version definitions and initialization
- **Main Class**: `includes/class-plugin.php` - Singleton pattern plugin controller
- **Namespace**: `MydPro\Includes\*` - All classes use this namespace structure

### Key Components
- **Admin Interface**: `includes/admin/` - WordPress admin pages, settings, custom post types
- **AJAX Handlers**: `includes/ajax/` - Cart operations, order processing, payment handling  
- **REST API**: `includes/api/` - Server-sent events for real-time order tracking
- **Custom Fields Framework**: `includes/custom-fields/` - Extensible field system for products/orders
- **License Management**: `includes/license/` - Commercial plugin licensing and auto-updates
- **Repositories**: `includes/repositories/` - Data access layer for orders, products, customers
- **Templates**: `templates/` - PHP template files for frontend rendering
- **Localization**: `languages/` - Translation files (ES, IT, PT-BR, EN)

### Frontend Architecture
- **Assets**: Pre-minified CSS/JS files in `assets/` directory
- **Template System**: PHP templates in `templates/` with admin/cart/order/products sections
- **JavaScript**: jQuery-based with vanilla JS conversion in progress
- **Real-time Updates**: Server-sent events for order status tracking

## Development Workflow

### No Build Process
This plugin uses traditional WordPress development without modern build tools:
- No package.json, webpack, or similar build configuration
- Assets are pre-minified and stored in `assets/` directory
- Direct PHP development with WordPress hooks and filters

### File Structure Conventions
- **Classes**: Use namespaced autoloading pattern
- **Templates**: Organized by functionality (admin, cart, order, products)
- **Assets**: Minified CSS/JS files loaded via WordPress enqueue system
- **Custom Fields**: Extensible framework in `includes/custom-fields/`

### WordPress Integration
- **Custom Post Types**: Products (`myd-product`) and Orders (`myd-order`)
- **Hooks System**: Extensive use of WordPress actions and filters
- **Admin Pages**: Custom admin interface integrated with WordPress admin
- **Capabilities**: Role-based permissions for different features

## Key Features & Functionality

### Business Logic
- Product catalog with categories, extras, and pricing
- Shopping cart system with real-time updates
- Order processing workflow with multiple status states
- Customer management and data storage
- Delivery methods (pickup, delivery, distance-based pricing)
- WhatsApp integration for order notifications
- Coupon/discount system
- Multi-language support (ES, IT, PT-BR, EN)

### Technical Features
- **Real-time Tracking**: Server-sent events for order status updates
- **Google Maps API**: Address validation and distance calculations
- **Payment Integration**: Support for external payment processors
- **Audio Notifications**: Custom notification system for order updates
- **License System**: Commercial plugin with auto-update functionality

## Version Information
- **Current Version**: 2.2.19
- **PHP Requirement**: 7.4+
- **WordPress Requirement**: 5.5+
- **License**: GPL v3 (with commercial licensing system)

## Important Constants
```php
MYD_PLUGIN_PATH       // Plugin directory path
MYD_PLUGN_URL         // Plugin URL (note: typo in constant name)
MYD_CURRENT_VERSION   // Current plugin version
MYD_PLUGIN_MAIN_FILE  // Main plugin file path
```

## Database Structure
- Uses WordPress custom post types for core entities
- Custom fields framework for extensible data storage
- Order items stored as post meta with JSON structure
- Customer data integrated with WordPress users

## Internationalization
- Text domain: `myd-delivery-pro`
- Domain path: `/languages`
- Supported languages: Spanish, Italian, Portuguese (Brazil), English
- Translation functions: Standard WordPress `__()`, `_e()`, `esc_html__()`

## Security Considerations
- All AJAX endpoints use WordPress nonces
- Input sanitization using WordPress functions
- Capability checks for admin functions
- SQL queries use WordPress database abstraction