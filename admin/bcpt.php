<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
$user = getCurrentUser();
// session_start();
// require_once __DIR__ . '/../includes/config.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê & Báo cáo - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="../Assets/css/admin.css">
    <link rel="stylesheet" href="../Assets/css/bcpt.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="container-fluid">
        <div class="row">
            <aside class="sidebar">
                <div class="sidebar-header">
                    <img src="../Assets/images/logo.png" alt="Logo" class="sidebar-logo" style="height:48px;width:auto;display:inline-block;vertical-align:middle;margin-right:12px;">
                    <div style="display:inline-block;vertical-align:middle;">
                        <h1 style="margin:0;">The Emma</h1>
                        <p style="margin:0;">Admin Panel</p>
                    </div>
                </div>
                
                <nav class="sidebar-nav">
                    <ul>
                        <li>
                            <a href="index.php">
                                <i class="fas fa-home"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="products.php">
                                <i class="fas fa-box"></i>
                                <span>Sản phẩm</span>
                            </a>
                        </li>
                        <li>
                            <a href="orders.php">
                                <i class="fas fa-shopping-cart"></i>
                                <span>Đơn hàng</span>
                            </a>
                        </li>
                        <li>
                            <a href="users.php">
                                <i class="fas fa-users"></i>
                                <span>Nhân viên</span>
                            </a>
                        </li>
                        <li>
                            <a href="categories.php">
                                <i class="fas fa-tags"></i>
                                <span>Danh mục</span>
                            </a>
                        </li>
                        <li>
                            <a href="inventory.php">
                                <i class="fas fa-warehouse"></i>
                                <span>Quản lý kho</span>
                            </a>
                        </li>
                        <li>
                            <a href="customers.php">
                                <i class="fas fa-user"></i>
                                <span>Khách hàng</span>
                            </a>
                        </li>
                        <li class="active">
                            <a href="bcpt.php">
                                <i class="fas fa-chart-bar"></i>
                                <span>Thống kê & Báo cáo</span>
                            </a>
                        </li>
                        <li>
                            <a href="settings.php">
                                <i class="fas fa-cog"></i>
                                <span>Cài đặt</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                
                <div class="sidebar-footer">
                    <a href="../api/auth/index.php?action=logout" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Đăng xuất</span>
                    </a>
                </div>
            </aside>

            <div class="col-md-10 col-lg-10 p-4 main-content">
                <header class="main-header">
                    <div class="header-left">
                        <button class="menu-toggle">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h2><i class="fas fa-chart-line me-2"></i>Thống kê & Báo cáo</h2>
                    </div>
                    <div class="header-right">
                        <div class="user-menu">
                            <span><?php echo $user['name']; ?></span>
                        </div>
                    </div>
                </header>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-primary me-2" id="exportExcel">
                            <i class="fas fa-file-excel me-2"></i>Xuất Excel
                        </button>
                        <button class="btn btn-secondary me-2" id="printReport">
                            <i class="fas fa-print me-2"></i>In báo cáo
                        </button>
                    </div>
                    <select id="monthSelect" class="form-select" style="width: 200px; display: inline-block;">
                        <option value="1">Tháng 1</option>
                        <option value="2">Tháng 2</option>
                        <option value="3">Tháng 3</option>
                        <option value="4">Tháng 4</option>
                        <option value="5">Tháng 5</option>
                        <option value="6">Tháng 6</option>
                        <option value="7">Tháng 7</option>
                        <option value="8">Tháng 8</option>
                        <option value="9">Tháng 9</option>
                        <option value="10">Tháng 10</option>
                        <option value="11">Tháng 11</option>
                        <option value="12">Tháng 12</option>
                    </select>
                </div>

                <div class="dashboard-cards">
                    <div class="col-md-3">
                        <div class="card card-metric card-revenue">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white mb-1">Tổng doanh thu</h6>
                                    <h3 class="mb-0 text-white" id="totalRevenue">0đ</h3>
                                    <small class="trend-up" id="revenueTrend"><i class="fas fa-arrow-up me-1"></i>0% so với kỳ trước</small>
                                </div>
                                <div class="metric-icon">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-metric card-orders">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white mb-1">Số đơn hàng</h6>
                                    <h3 class="mb-0 text-white" id="orderCount">0</h3>
                                    <small class="trend-up" id="orderTrend"><i class="fas fa-arrow-up me-1"></i>0% so với kỳ trước</small>
                                </div>
                                <div class="metric-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-metric card-products">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white mb-1">Sản phẩm đã bán</h6>
                                    <h3 class="mb-0 text-white" id="productCount">0</h3>
                                    <small class="trend-up" id="productTrend"><i class="fas fa-arrow-up me-1"></i>0% so với kỳ trước</small>
                                </div>
                                <div class="metric-icon">
                                    <i class="fas fa-birthday-cake"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-metric card-inventory">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white mb-1">Tổng chi phí</h6>
                                    <h3 class="mb-0 text-white" id="inventoryCount">0đ</h3>
                                    <small class="trend-up" id="inventoryTrend"><i class="fas fa-arrow-up me-1"></i>0% so với kỳ trước</small>
                                </div>
                                <div class="metric-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 text-white">Biểu đồ doanh thu</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header ">
                                <h5 class="mb-0 text-white">Chi phí</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="position: relative; height:300px;">
                                    <canvas id="expenseChart"></canvas>
                                </div>
                                <div class="expense-legend mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span><i class="fas fa-circle text-info"></i> Tiền nguyên liệu</span>
                                        <span class="expense-amount">0đ</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-circle" style="color: #6f42c1;"></i> Tiền vật phẩm đóng gói</span>
                                        <span class="expense-amount">0đ</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    menuToggle && menuToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        sidebar.classList.toggle('active');
    });
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 1080 && sidebar.classList.contains('active')) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });

    // Hàm format số tiền
    function formatCurrency(amount) {
        return Number(amount).toLocaleString('vi-VN') + 'đ';
    }

    // Hàm tính phần trăm thay đổi
    function calculateChange(current, previous) {
        if (previous === 0) return 0;
        return ((current - previous) / previous * 100).toFixed(1);
    }

    // Hàm cập nhật xu hướng
    function updateTrend(element, change) {
        const trendElement = element.nextElementSibling;
        if (change > 0) {
            trendElement.innerHTML = `<i class="fas fa-arrow-up me-1"></i>${change}% so với kỳ trước`;
            trendElement.className = 'trend-up';
        } else if (change < 0) {
            trendElement.innerHTML = `<i class="fas fa-arrow-down me-1"></i>${Math.abs(change)}% so với kỳ trước`;
            trendElement.className = 'trend-down';
        } else {
            trendElement.innerHTML = `<i class="fas fa-minus me-1"></i>0% so với kỳ trước`;
            trendElement.className = 'trend-neutral';
        }
    }

    // Hàm lấy dữ liệu tổng quan
    function loadSummary(period, value) {
        fetch(`../api/report/index.php?action=summary&period=${period}&value=${value}`)
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    const data = res.data;
                    const prevData = res.previous_data;

                    // Cập nhật doanh thu
                    document.getElementById('totalRevenue').textContent = formatCurrency(data.total_revenue);
                    updateTrend(
                        document.getElementById('totalRevenue'),
                        calculateChange(data.total_revenue, prevData.total_revenue)
                    );

                    // Cập nhật số đơn hàng
                    document.getElementById('orderCount').textContent = data.order_count;
                    updateTrend(
                        document.getElementById('orderCount'),
                        calculateChange(data.order_count, prevData.order_count)
                    );

                    // Cập nhật số sản phẩm
                    document.getElementById('productCount').textContent = data.product_count;
                    updateTrend(
                        document.getElementById('productCount'),
                        calculateChange(data.product_count, prevData.product_count)
                    );

                    // Cập nhật chi phí
                    document.getElementById('inventoryCount').textContent = formatCurrency(data.total_cost);
                    updateTrend(
                        document.getElementById('inventoryCount'),
                        calculateChange(data.total_cost, prevData.total_cost)
                    );
                }
            })
            .catch(error => console.error('Error loading summary:', error));
    }

    let revenueChartInstance = null;
    let expenseChartInstance = null;

    function loadRevenueChart(value) {
        fetch(`../api/report/index.php?action=revenue_chart&value=${value}`)
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    const ctx = document.getElementById('revenueChart').getContext('2d');
                    // Xóa biểu đồ cũ nếu có
                    if (revenueChartInstance) {
                        revenueChartInstance.destroy();
                    }
                    revenueChartInstance = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: res.data.labels,
                            datasets: [{
                                label: 'Doanh thu',
                                data: res.data.values,
                                borderColor: '#4CAF50',
                                backgroundColor: 'rgba(76, 175, 80, 0.15)',
                                tension: 0.1,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                }
            })
            .catch(error => console.error('Error loading revenue chart:', error));
    }

    function loadExpenseChart(value) {
        fetch(`../api/report/index.php?action=expense_chart&value=${value}`)
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    const ctx = document.getElementById('expenseChart').getContext('2d');
                    // Xóa biểu đồ cũ nếu có
                    if (expenseChartInstance) {
                        expenseChartInstance.destroy();
                    }
                    expenseChartInstance = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Tiền nguyên liệu', 'Tiền vật phẩm đóng gói'],
                            datasets: [{
                                data: [res.data.ingredients_cost, res.data.packaging_cost],
                                backgroundColor: ['#17a2b8', '#6f42c1']
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });

                    // Cập nhật legend
                    document.querySelectorAll('.expense-amount')[0].textContent = formatCurrency(res.data.ingredients_cost);
                    document.querySelectorAll('.expense-amount')[1].textContent = formatCurrency(res.data.packaging_cost);
                }
            })
            .catch(error => console.error('Error loading expense chart:', error));
    }

    document.addEventListener('DOMContentLoaded', function() {
        const monthSelect = document.getElementById('monthSelect');
        if (monthSelect) {
            // Đặt giá trị mặc định là tháng hiện tại
            const now = new Date();
            const currentMonth = now.getMonth() + 1;
            monthSelect.value = currentMonth;

            // Load dữ liệu ban đầu
            loadSummary('month', currentMonth);
            loadRevenueChart(currentMonth);
            loadExpenseChart(currentMonth);

            // Xử lý sự kiện thay đổi tháng
            monthSelect.addEventListener('change', function() {
                const selectedMonth = this.value;
                loadSummary('month', selectedMonth);
                loadRevenueChart(selectedMonth);
                loadExpenseChart(selectedMonth);
            });
        }
    });

    document.getElementById('exportExcel').addEventListener('click', function() {
        const month = document.getElementById('monthSelect').value;
        window.open(`../api/report/export_excel.php?month=${month}`, '_blank');
    });

    document.getElementById('printReport').addEventListener('click', function() {
        window.print();
    });
    </script>
</body>
</html>