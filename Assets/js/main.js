// Utility functions
const utils = {
    showLoading: () => {
        const loading = document.createElement('div');
        loading.className = 'loading';
        loading.innerHTML = '<div class="loading-spinner"></div>';
        document.body.appendChild(loading);
    },

    hideLoading: () => {
        const loading = document.querySelector('.loading');
        if (loading) {
            loading.remove();
        }
    },

    showNotification: (message, type = 'success') => {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    },

    formatPrice: (price) => {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(price);
    }
};

// Form handling
const formHandler = {
    init: () => {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', formHandler.handleSubmit);
        });
    },

    handleSubmit: async (e) => {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        try {
            utils.showLoading();
            // Thay thế bằng API call thực tế
            await new Promise(resolve => setTimeout(resolve, 1000));
            utils.showNotification('Gửi thành công!');
            form.reset();
        } catch (error) {
            utils.showNotification('Có lỗi xảy ra!', 'error');
        } finally {
            utils.hideLoading();
        }
    }
};

// Product handling
const productHandler = {
    init: () => {
        const addToCartButtons = document.querySelectorAll('.add-to-cart');
        addToCartButtons.forEach(button => {
            button.addEventListener('click', productHandler.handleAddToCart);
        });
    },

    handleAddToCart: (e) => {
        const button = e.target;
        const productId = button.dataset.productId;
        const productName = button.dataset.productName;
        const productPrice = button.dataset.productPrice;

        // Thêm vào giỏ hàng
        const cart = JSON.parse(localStorage.getItem('cart') || '[]');
        cart.push({ id: productId, name: productName, price: productPrice });
        localStorage.setItem('cart', JSON.stringify(cart));

        utils.showNotification('Đã thêm vào giỏ hàng!');
    }
};

// Navigation handling
const navigationHandler = {
    init: () => {
        const mobileMenuButton = document.querySelector('.mobile-menu-button');
        const navLinks = document.querySelector('.nav-links');

        if (mobileMenuButton && navLinks) {
            mobileMenuButton.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
        }

        // Handle smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }
};

// Back to top button
const backToTopHandler = {
    init: () => {
        const button = document.getElementById('backToTopButton');
        if (button) {
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    button.style.display = 'flex';
                    button.classList.add('back-to-top-animated');
                } else {
                    button.style.display = 'none';
                    button.classList.remove('back-to-top-animated');
                }
            });

            button.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            button.style.display = 'none';
        }
    }
};

// Initialize all handlers when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    formHandler.init();
    productHandler.init();
    navigationHandler.init();
    backToTopHandler.init();
});
