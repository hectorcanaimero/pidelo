/**
 * MyD Delivery Pro - Products V2 JavaScript
 * Sticky category navigation and modern interactions
 *
 * @package MydPro
 * @since 2.4.0
 */

(function() {
	'use strict';

	/**
	 * Sticky Navigation Controller
	 */
	class StickyNavigation {
		constructor() {
			this.nav = document.querySelector('.myd-sticky-nav-v2');
			if (!this.nav) return;

			this.config = window.mydProductsV2Config || {
				stickyPosition: 'top',
				stickyOffset: 100,
				showCount: true
			};

			this.items = this.nav.querySelectorAll('.myd-sticky-nav-v2__item');
			this.sections = document.querySelectorAll('.myd-product-section-v2');
			this.offset = parseInt(this.config.stickyOffset, 10);
			this.isVisible = false;
			this.activeSection = null;

			this.init();
		}

		init() {
			this.bindEvents();
			this.observeSections();
		}

		bindEvents() {
			// Handle scroll for showing/hiding navigation
			let scrollTimeout;
			window.addEventListener('scroll', () => {
				if (scrollTimeout) {
					window.cancelAnimationFrame(scrollTimeout);
				}
				scrollTimeout = window.requestAnimationFrame(() => {
					this.handleScroll();
				});
			}, { passive: true });

			// Handle category item clicks
			this.items.forEach(item => {
				item.addEventListener('click', (e) => {
					e.preventDefault();
					const anchor = item.getAttribute('data-anchor');
					this.scrollToSection(anchor);
				});
			});
		}

		handleScroll() {
			const scrollY = window.pageYOffset || document.documentElement.scrollTop;

			// Show/hide navigation based on scroll position
			if (scrollY > this.offset) {
				this.show();
			} else {
				this.hide();
			}
		}

		show() {
			if (this.isVisible) return;
			this.nav.classList.add('is-visible');
			this.nav.style.display = 'block';
			this.isVisible = true;
		}

		hide() {
			if (!this.isVisible) return;
			this.nav.classList.remove('is-visible');
			// Small delay before hiding to allow animation
			setTimeout(() => {
				if (!this.nav.classList.contains('is-visible')) {
					this.nav.style.display = 'none';
				}
			}, 200);
			this.isVisible = false;
		}

		scrollToSection(anchor) {
			const section = document.getElementById(anchor);
			if (!section) return;

			// Calculate offset for sticky elements
			const navHeight = this.nav ? this.nav.offsetHeight : 0;
			const extraOffset = 20; // Additional padding
			const targetPosition = section.offsetTop - navHeight - extraOffset;

			window.scrollTo({
				top: targetPosition,
				behavior: 'smooth'
			});
		}

		observeSections() {
			if (!('IntersectionObserver' in window)) {
				return;
			}

			// Calculate offset for active state
			const navHeight = this.nav ? this.nav.offsetHeight : 0;
			const rootMargin = `-${navHeight + 50}px 0px -50% 0px`;

			const observer = new IntersectionObserver((entries) => {
				entries.forEach(entry => {
					if (entry.isIntersecting) {
						this.setActiveSection(entry.target.id);
					}
				});
			}, {
				rootMargin: rootMargin,
				threshold: 0
			});

			this.sections.forEach(section => {
				observer.observe(section);
			});
		}

		setActiveSection(sectionId) {
			if (this.activeSection === sectionId) return;

			this.activeSection = sectionId;

			// Remove active class from all items
			this.items.forEach(item => {
				item.classList.remove('is-active');
			});

			// Add active class to current item
			const activeItem = Array.from(this.items).find(item => {
				return item.getAttribute('data-anchor') === sectionId;
			});

			if (activeItem) {
				activeItem.classList.add('is-active');

				// Scroll active item into view in the navigation
				this.scrollItemIntoView(activeItem);
			}
		}

		scrollItemIntoView(item) {
			const nav = item.closest('.myd-sticky-nav-v2__scroll');
			if (!nav) return;

			const itemLeft = item.offsetLeft;
			const itemWidth = item.offsetWidth;
			const navWidth = nav.offsetWidth;
			const navScroll = nav.scrollLeft;

			// Calculate if item is not fully visible
			const itemRight = itemLeft + itemWidth;
			const visibleLeft = navScroll;
			const visibleRight = navScroll + navWidth;

			if (itemLeft < visibleLeft || itemRight > visibleRight) {
				// Center the item in the nav
				const scrollTo = itemLeft - (navWidth / 2) + (itemWidth / 2);
				nav.scrollTo({
					left: scrollTo,
					behavior: 'smooth'
				});
			}
		}
	}

	/**
	 * Product Search Functionality
	 */
	class ProductSearch {
		constructor() {
			this.searchInput = document.getElementById('myd-search-products-v2');
			if (!this.searchInput) return;

			this.products = document.querySelectorAll('.myd-product-card-v2');
			this.sections = document.querySelectorAll('.myd-product-section-v2');

			this.init();
		}

		init() {
			let searchTimeout;
			this.searchInput.addEventListener('input', (e) => {
				clearTimeout(searchTimeout);
				searchTimeout = setTimeout(() => {
					this.performSearch(e.target.value);
				}, 300);
			});
		}

		performSearch(query) {
			const searchTerm = query.toLowerCase().trim();

			if (searchTerm === '') {
				this.showAll();
				return;
			}

			let visibleCount = 0;

			this.products.forEach(product => {
				const title = product.querySelector('.myd-product-card-v2__title')?.textContent.toLowerCase() || '';
				const description = product.querySelector('.myd-product-card-v2__description')?.textContent.toLowerCase() || '';

				if (title.includes(searchTerm) || description.includes(searchTerm)) {
					product.style.display = '';
					visibleCount++;
				} else {
					product.style.display = 'none';
				}
			});

			// Hide sections with no visible products
			this.sections.forEach(section => {
				const visibleProducts = section.querySelectorAll('.myd-product-card-v2:not([style*="display: none"])');
				section.style.display = visibleProducts.length > 0 ? '' : 'none';
			});
		}

		showAll() {
			this.products.forEach(product => {
				product.style.display = '';
			});

			this.sections.forEach(section => {
				section.style.display = '';
			});
		}
	}

	/**
	 * Product Card Interactions
	 */
	class ProductCardInteractions {
		constructor() {
			this.cards = document.querySelectorAll('.myd-product-card-v2');
			this.init();
		}

		init() {
			this.cards.forEach(card => {
				// Image preview on click
				const imageWrapper = card.querySelector('.myd-product-card-v2__image-wrapper');
				if (imageWrapper) {
					imageWrapper.addEventListener('click', (e) => {
						// Don't trigger if clicking the add button
						if (e.target.closest('.myd-product-card-v2__add-button')) {
							return;
						}
						this.handleImageClick(imageWrapper);
					});
				}

				// Add to cart button
				const addButton = card.querySelector('.myd-product-card-v2__add-button');
				if (addButton) {
					addButton.addEventListener('click', (e) => {
						e.stopPropagation();
						this.handleAddToCart(card);
					});
				}
			});
		}

		handleImageClick(imageWrapper) {
			const imageUrl = imageWrapper.getAttribute('data-image');
			if (!imageUrl) return;

			// Trigger the existing lightbox functionality
			const imagePreview = document.getElementById('myd-image-preview-image');
			const popup = document.getElementById('myd-image-preview-popup');

			if (imagePreview && popup) {
				imagePreview.src = imageUrl;
				popup.classList.remove('myd-hide-element');
			}
		}

		handleAddToCart(card) {
			const productId = card.getAttribute('data-id');
			if (!productId) return;

			// Trigger the existing add to cart functionality
			// This will be handled by the existing MyDelivery cart system
			if (typeof window.myd_create_order !== 'undefined') {
				// Reuse existing functionality
				card.click();
			}
		}
	}

	/**
	 * Initialize everything when DOM is ready
	 */
	function init() {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', initialize);
		} else {
			initialize();
		}
	}

	function initialize() {
		new StickyNavigation();
		new ProductSearch();
		new ProductCardInteractions();
	}

	// Start the app
	init();

})();
