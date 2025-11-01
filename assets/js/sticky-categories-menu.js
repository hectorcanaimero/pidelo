/**
 * Sticky Categories Menu
 *
 * JavaScript for sticky category navigation functionality
 * @since 2.3.6
 */

(function() {
	'use strict';

	// Esperar a que el DOM esté listo
	document.addEventListener('DOMContentLoaded', function() {
		const categoriesMenu = document.querySelector('.myd-content-filter');

		if (!categoriesMenu) {
			return;
		}

		// Crear placeholder para evitar saltos de contenido
		const placeholder = document.createElement('div');
		placeholder.className = 'myd-content-filter-placeholder';
		categoriesMenu.parentNode.insertBefore(placeholder, categoriesMenu);

		// Obtener la posición inicial del menú
		const menuOffsetTop = categoriesMenu.offsetTop;
		let isSticky = false;

		// Función para actualizar el estado sticky
		function updateStickyState() {
			const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

			if (scrollTop >= menuOffsetTop && !isSticky) {
				// Activar sticky
				isSticky = true;
				categoriesMenu.classList.add('is-sticky');
				placeholder.classList.add('active');
				placeholder.style.height = categoriesMenu.offsetHeight + 'px';
			} else if (scrollTop < menuOffsetTop && isSticky) {
				// Desactivar sticky
				isSticky = false;
				categoriesMenu.classList.remove('is-sticky');
				placeholder.classList.remove('active');
				placeholder.style.height = '0';
			}
		}

		// Función para resaltar la categoría activa durante el scroll
		function highlightActiveCategory() {
			const categoryTags = document.querySelectorAll('.myd-content-filter__tag');
			const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

			categoryTags.forEach(function(tag) {
				const anchor = tag.getAttribute('data-anchor');
				const targetSection = document.getElementById('fdm-' + anchor);

				if (targetSection) {
					const sectionTop = targetSection.offsetTop - 150; // Offset para el menú sticky
					const sectionBottom = sectionTop + targetSection.offsetHeight;

					if (scrollTop >= sectionTop && scrollTop < sectionBottom) {
						tag.classList.add('active');
					} else {
						tag.classList.remove('active');
					}
				}
			});
		}

		// Throttle function para mejorar el rendimiento
		function throttle(func, delay) {
			let lastCall = 0;
			return function() {
				const now = new Date().getTime();
				if (now - lastCall < delay) {
					return;
				}
				lastCall = now;
				return func.apply(this, arguments);
			};
		}

		// Event listeners
		window.addEventListener('scroll', throttle(function() {
			updateStickyState();
			highlightActiveCategory();
		}, 100));

		// Actualizar en resize
		window.addEventListener('resize', throttle(function() {
			if (isSticky) {
				placeholder.style.height = categoriesMenu.offsetHeight + 'px';
			}
		}, 200));

		// Mejorar el comportamiento de clic en categorías
		const categoryTags = document.querySelectorAll('.myd-content-filter__tag');
		categoryTags.forEach(function(tag) {
			tag.addEventListener('click', function() {
				// Pequeño delay para que el scroll suave funcione correctamente con sticky
				setTimeout(function() {
					const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
					const adjustment = isSticky ? categoriesMenu.offsetHeight : 0;
					window.scrollBy(0, -adjustment);
				}, 10);
			});
		});
	});
})();
