# Product Availability Checker

## Overview

This plugin adds availability checking functionality to WooCommerce stores, preventing customers from ordering products that cannot be delivered to their area.

## Features

### Admin Features

- **WooCommerce Settings Integration**: Adds an "Availability" tab to WooCommerce settings
- **Zip Code Management**: Add, edit, and delete zip codes with availability status
- **Custom Messages**: Set custom messages for each zip code (optional)
- **AJAX Interface**: Real-time CRUD operations without page reloads
- **Search & Pagination**: Easy management of large zip code databases

### Frontend Features

- **Product Page Widget**: Availability checker on single product pages
- **Add to Cart Control**: Automatically disables add to cart for unavailable areas
- **Real-time Validation**: AJAX-powered availability checking

## How It Works

### 1. Admin Setup

1. Navigate to **WooCommerce → Settings → Availability**
2. Add zip codes with their availability status:
   - **Available**: Product can be delivered
   - **Unavailable**: Product cannot be delivered
3. Optionally add custom messages for specific zip codes

### 2. Customer Experience

1. Customer visits a product page
2. Sees "Check Product Availability" widget
3. Enters zip code and clicks "Check Availability"
4. System shows availability status with message
5. Add to cart button is:
   - **Enabled** if available in their area
   - **Disabled** if not available

### 3. Technical Flow

```
Customer Input → AJAX Request → Database Lookup → Response → UI Update → Cart Control
```

## File Structure

```
product-availability-checker/
├── includes/
│   ├── admin/                     # Admin functionality
│   │   ├── class-admin-loader.php
│   │   └── class-availability-settings-tab.php
│   ├── frontend/                  # Customer-facing features
│   │   ├── class-frontend-loader.php
│   │   ├── class-frontend-ajax-handler.php
│   │   ├── class-product-availability-display.php
│   │   └── class-cart-availability-display.php
│   ├── services/                  # Business logic
│   │   ├── class-codes-service.php
│   │   └── class-service-manager.php
│   └── rest-api/                  # REST API endpoints
├── assets/
│   ├── js/
│   │   ├── admin.js              # Admin interface JavaScript
│   │   └── frontend.js           # Customer interface JavaScript
│   └── css/
│       ├── admin.css             # Admin styling
│       └── frontend.css          # Customer widget styling
└── templates/
    ├── admin/                    # Admin templates
    └── frontend/                 # Customer templates
```

## Architecture

### Design Patterns

- **Singleton Pattern**: Ensures single instances of core classes
- **Service Layer**: Business logic separated from presentation
- **Template System**: Clean separation of PHP logic and HTML
- **AJAX Architecture**: No page reloads, smooth user experience

## Database Storage

Zip codes and availability data are stored in WordPress options table as structured data:

```php
[
    'id' => 1,
    'code' => '12345',
    'availability' => 'available', // or 'unavailable'
    'message' => 'Custom delivery message (optional)'
]
```

## AJAX Endpoints

### Frontend

- **Action**: `pavc_check_availability`
- **Purpose**: Check if product is available in customer's zip code
- **Security**: WordPress nonce verification

### Admin

- **Endpoint**: `/wp-json/pavc/v1/codes`
- **Methods**: GET, POST, PUT, DELETE
- **Purpose**: CRUD operations for zip code management
- **Security**: Admin capability checks

## Testing the Plugin

### Admin Testing

1. Go to WooCommerce → Settings → Availability
2. Add test zip codes (e.g., "12345" as available, "54321" as unavailable)
3. Test search and edit functionality

### Frontend Testing

1. Visit any single product page
2. Use the availability checker widget
3. Test with available and unavailable zip codes
4. Verify add to cart button behavior

## Security Features

- WordPress nonce verification
- Input sanitization and validation
- Capability-based access control
- SQL injection protection via WordPress APIs
