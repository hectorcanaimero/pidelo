# Products Display V2 - Sticky Category Navigation

**Version:** 2.4.0
**Feature Issue:** #35
**Status:** ✅ Ready for Testing

## Overview

The Products V2 feature introduces a modern, redesigned product display system with a sticky category navigation menu. This is a complete fork of the original `[mydelivery-products]` shortcode with enhanced UX, modern design inspired by shadcn/ui, and improved mobile experience.

## Features

### ✨ Core Features

1. **Sticky Category Navigation**
   - Auto-hiding navigation that appears on scroll
   - Configurable position (top or bottom of screen)
   - Horizontal scrolling for many categories
   - Active category highlighting based on scroll position
   - Smooth scroll to category on click
   - Product count per category (optional)

2. **Modern Design System**
   - Design tokens inspired by shadcn/ui
   - CSS custom properties for easy theming
   - Smooth transitions and animations
   - Subtle shadows and borders
   - Improved typography scale
   - Better color contrast and accessibility

3. **Enhanced UX**
   - Lazy loading images
   - Smooth scroll behavior
   - Intersection Observer for performance
   - Keyboard navigation support
   - ARIA labels for accessibility
   - Mobile-first responsive design

## Usage

### Basic Shortcode

```
[mydelivery-products-v2]
```

### With Attributes

```
[mydelivery-products-v2
    sticky_position="top"
    sticky_offset="100"
    show_count="yes"
    design="modern"]
```

### Shortcode Attributes

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `sticky_position` | string | `top` | Position of sticky menu: `top` or `bottom` |
| `sticky_offset` | int | `100` | Pixels from top before showing sticky menu |
| `show_count` | string | `yes` | Show product count per category: `yes` or `no` |
| `design` | string | `modern` | Design style (reserved for future variants) |

## Examples

### Top Sticky Navigation (Default)
```
[mydelivery-products-v2]
```
Navigation appears at the top of the screen after scrolling 100px.

### Bottom Sticky Navigation
```
[mydelivery-products-v2 sticky_position="bottom"]
```
Navigation appears at the bottom of the screen (mobile-friendly).

### Custom Scroll Offset
```
[mydelivery-products-v2 sticky_offset="200"]
```
Navigation appears after scrolling 200px from the top.

### Without Product Count
```
[mydelivery-products-v2 show_count="no"]
```
Hides the product count badges in the navigation.

### Combined
```
[mydelivery-products-v2 sticky_position="bottom" sticky_offset="150" show_count="yes"]
```

## Integration with Elementor

The shortcode can be easily integrated into Elementor pages:

1. Add a **Shortcode** widget
2. Paste the shortcode: `[mydelivery-products-v2]`
3. Customize attributes as needed
4. Save and preview

## File Structure

```
myd-delivery-pro/
├── includes/
│   └── fdm-products-list-v2.php          # Main V2 class
├── templates/
│   └── products-v2/
│       ├── template.php                   # Main template
│       ├── sticky-menu.php                # Sticky navigation
│       ├── loop-products.php              # Product card
│       └── product-extra.php              # (Future) Product extras modal
├── assets/
│   ├── css/
│   │   └── myd-products-v2.css           # V2 styles with design system
│   └── js/
│       └── myd-products-v2.js            # V2 JavaScript functionality
└── PRODUCTS-V2-README.md                  # This file
```

## Design Tokens

The V2 system uses CSS custom properties for easy theming:

### Colors
```css
--background: 0 0% 100%;
--foreground: 240 10% 3.9%;
--primary: 240 5.9% 10%;
--secondary: 240 4.8% 95.9%;
--muted: 240 4.8% 95.9%;
--border: 240 5.9% 90%;
```

### Typography
```css
--font-size-xs: 0.75rem;
--font-size-sm: 0.875rem;
--font-size-base: 1rem;
--font-size-lg: 1.125rem;
--font-size-xl: 1.25rem;
--font-size-2xl: 1.5rem;
```

### Spacing
```css
--spacing-xs: 0.25rem;
--spacing-sm: 0.5rem;
--spacing-md: 1rem;
--spacing-lg: 1.5rem;
--spacing-xl: 2rem;
```

## Customization

### Override Styles

Add custom CSS to your theme:

```css
/* Change primary color */
:root {
    --primary: 210 100% 50%; /* Blue */
}

/* Adjust card spacing */
.myd-product-list-v2 {
    gap: 2rem;
}

/* Customize sticky nav background */
.myd-sticky-nav-v2 {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
}
```

### Extend Functionality

Use WordPress hooks to extend functionality:

```php
// Modify product card output
add_filter('myd_product_card_v2_html', function($html, $product_id) {
    // Custom modifications
    return $html;
}, 10, 2);

// Add custom data to localized script
add_filter('myd_products_v2_config', function($config) {
    $config['customOption'] = 'value';
    return $config;
});
```

## Browser Support

- ✅ Chrome (latest 2 versions)
- ✅ Firefox (latest 2 versions)
- ✅ Safari (latest 2 versions)
- ✅ Edge (latest 2 versions)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

**Note:** Intersection Observer is used for scroll detection. Fallback behavior is provided for older browsers.

## Performance

- **Lazy Loading:** Images load only when visible
- **Intersection Observer:** Efficient scroll detection
- **Debounced Search:** 300ms delay to prevent excessive filtering
- **Request Animation Frame:** Smooth scroll handling
- **CSS Transitions:** Hardware-accelerated animations

## Accessibility

- ✅ ARIA labels for navigation and buttons
- ✅ Keyboard navigation support
- ✅ Focus states for all interactive elements
- ✅ Semantic HTML structure
- ✅ Screen reader friendly
- ✅ Color contrast ratios meet WCAG AA standards

## Migration from V1

The V2 shortcode is completely independent from V1:

| Feature | V1 `[mydelivery-products]` | V2 `[mydelivery-products-v2]` |
|---------|---------------------------|-------------------------------|
| Category Filter | Static tags, always visible | Sticky navigation, auto-hide |
| Design | Classic | Modern (shadcn-inspired) |
| Scroll Behavior | Jump to section | Smooth scroll with active highlight |
| Mobile UX | Basic | Enhanced with bottom sticky option |
| Customization | Limited | CSS custom properties |

**Note:** You can use both shortcodes on the same site. They don't conflict.

## Troubleshooting

### Sticky Navigation Not Appearing

1. Check scroll offset: Try `sticky_offset="50"`
2. Verify categories exist in plugin settings
3. Check for CSS conflicts with theme
4. Inspect browser console for JavaScript errors

### Categories Not Highlighting

1. Ensure `IntersectionObserver` is supported
2. Check that product sections have proper IDs
3. Verify no custom CSS is hiding sections
4. Test with reduced `sticky_offset`

### Slow Performance

1. Enable lazy loading for images
2. Reduce number of products per page
3. Optimize product images
4. Check for conflicting plugins

## Testing Checklist

- [ ] Shortcode renders without errors
- [ ] Sticky navigation appears after scrolling
- [ ] Navigation hides when scrolling to top
- [ ] Clicking category scrolls to section
- [ ] Active category highlights correctly
- [ ] Product count displays (if enabled)
- [ ] Search functionality works
- [ ] Add to cart works
- [ ] Image preview works
- [ ] Mobile responsive (320px+)
- [ ] Works in Elementor
- [ ] No console errors
- [ ] Accessible via keyboard
- [ ] Works with screen readers

## Future Enhancements

- [ ] Dark mode support
- [ ] Category icons
- [ ] Animation variants
- [ ] Filter by price/availability
- [ ] Grid/List view toggle
- [ ] Quick view modal
- [ ] Wishlist integration
- [ ] Compare products

## Support

For issues or questions:
- **GitHub Issues:** [Create an issue](#)
- **Documentation:** [Plugin docs](#)

## Changelog

### v2.4.0 (2025-01-XX)
- ✨ Initial release of Products V2
- ✨ Sticky category navigation
- ✨ Modern design system
- ✨ Enhanced mobile UX
- ✨ Accessibility improvements
- ✨ Performance optimizations

---

**Author:** MyD Delivery Pro Team
**License:** GPL v3
**Requires:** WordPress 5.5+, PHP 7.4+
