// Import utilities
import { validation, storage, dateFormat, api } from './utils.js';

// Admin authentication
const adminAuth = {
    isAuthenticated: () => {
        const token = storage.get('adminToken');
        return !!token;
    },

    login: async (username, password) => {
        try {
            const response = await api.post('/admin/login', { username, password });
            storage.set('adminToken', response.token);
            return true;
        } catch (error) {
            console.error('Login failed:', error);
            return false;
        }
    },

    logout: () => {
        storage.remove('adminToken');
        window.location.href = '/admin/login.html';
    }
};

// Product management
const productManagement = {
    async getProducts() {
        try {
            return await api.get('/admin/products');
        } catch (error) {
            console.error('Failed to fetch products:', error);
            return [];
        }
    },

    async addProduct(productData) {
        try {
            return await api.post('/admin/products', productData);
        } catch (error) {
            console.error('Failed to add product:', error);
            throw error;
        }
    },

    async updateProduct(id, productData) {
        try {
            return await api.put(`/admin/products/${id}`, productData);
        } catch (error) {
            console.error('Failed to update product:', error);
            throw error;
        }
    },

    async deleteProduct(id) {
        try {
            return await api.delete(`/admin/products/${id}`);
        } catch (error) {
            console.error('Failed to delete product:', error);
            throw error;
        }
    }
};

// Order management
const orderManagement = {
    async getOrders() {
        try {
            return await api.get('/admin/orders');
        } catch (error) {
            console.error('Failed to fetch orders:', error);
            return [];
        }
    },

    async updateOrderStatus(orderId, status) {
        try {
            return await api.put(`/admin/orders/${orderId}`, { status });
        } catch (error) {
            console.error('Failed to update order status:', error);
            throw error;
        }
    }
};

// User management
const userManagement = {
    async getUsers() {
        try {
            return await api.get('/admin/users');
        } catch (error) {
            console.error('Failed to fetch users:', error);
            return [];
        }
    },

    async updateUser(id, userData) {
        try {
            return await api.put(`/admin/users/${id}`, userData);
        } catch (error) {
            console.error('Failed to update user:', error);
            throw error;
        }
    }
};

// Dashboard statistics
const dashboardStats = {
    async getStats() {
        try {
            return await api.get('/admin/stats');
        } catch (error) {
            console.error('Failed to fetch stats:', error);
            return {
                totalOrders: 0,
                totalRevenue: 0,
                totalUsers: 0,
                recentOrders: []
            };
        }
    }
};

// Sidebar Toggle
const menuToggle = document.querySelector('.menu-toggle');
const sidebar = document.querySelector('.sidebar');
const mainContent = document.querySelector('.main-content');

if (menuToggle) {
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', (e) => {
    if (window.innerWidth <= 768) {
        if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
            sidebar.classList.remove('active');
        }
    }
});

// Handle window resize
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        sidebar.classList.remove('active');
    }
});

// API Functions
const API_URL = '/api';

// Format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
};

// Format date
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Show loading
const showLoading = () => {
    const loading = document.createElement('div');
    loading.className = 'loading';
    loading.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    document.body.appendChild(loading);
};

// Hide loading
const hideLoading = () => {
    const loading = document.querySelector('.loading');
    if (loading) {
        loading.remove();
    }
};

// Show notification
const showNotification = (message, type = 'success') => {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
};

// Handle API errors
const handleError = (error) => {
    console.error('API Error:', error);
    showNotification(error.message || 'Có lỗi xảy ra', 'error');
};

// API request function
const apiRequest = async (endpoint, method = 'GET', data = null) => {
    try {
        showLoading();
        
        const token = localStorage.getItem('token');
        const headers = {
            'Content-Type': 'application/json',
            ...(token && { 'Authorization': `Bearer ${token}` }),
            ...(data && { 'X-API-Key': 'your-api-key-here' })
        };
        
        const response = await fetch(`${API_URL}/${endpoint}`, {
            method,
            headers,
            ...(data && { body: JSON.stringify(data) })
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.message || 'Có lỗi xảy ra');
        }
        
        return result;
    } catch (error) {
        handleError(error);
        throw error;
    } finally {
        hideLoading();
    }
};

// Check authentication
const checkAuth = async () => {
    try {
        const response = await apiRequest('auth/');
        if (!response.user) {
            window.location.href = '/admin/login.php';
        }
    } catch (error) {
        localStorage.removeItem('token');
        window.location.href = '/admin/login.php';
    }
};

// Initialize admin panel
const initAdmin = async () => {
    try {
        const user = await checkAuth();
        
        // Update user info in header
        const userMenu = document.querySelector('.user-menu');
        if (userMenu) {
            const avatar = userMenu.querySelector('img');
            const name = userMenu.querySelector('span');
            
            if (avatar) {
                avatar.src = user.avatar || '/Assets/images/default-avatar.png';
                avatar.alt = user.name;
            }
            
            if (name) {
                name.textContent = user.name;
            }
        }
        
        // Add event listeners
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', handleDelete);
        });
        
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', handleStatusChange);
        });
        
    } catch (error) {
        console.error('Initialization error:', error);
    }
};

// Handle delete
const handleDelete = async (e) => {
    e.preventDefault();
    
    if (!confirm('Bạn có chắc chắn muốn xóa?')) {
        return;
    }
    
    const id = e.target.dataset.id;
    const type = e.target.dataset.type;
    
    try {
        await apiRequest(`${type}/${id}`, 'DELETE');
        
        showNotification('Xóa thành công');
        e.target.closest('tr').remove();
    } catch (error) {
        console.error('Delete error:', error);
    }
};

// Handle status change
const handleStatusChange = async (e) => {
    const id = e.target.dataset.id;
    const type = e.target.dataset.type;
    const originalStatus = e.target.dataset.originalStatus;
    const newStatus = e.target.value;
    
    try {
        await apiRequest(`${type}/${id}`, 'PUT', { status: newStatus });
        
        showNotification('Cập nhật trạng thái thành công');
        e.target.dataset.originalStatus = newStatus;
    } catch (error) {
        e.target.value = originalStatus;
        console.error('Status change error:', error);
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initAdmin);
