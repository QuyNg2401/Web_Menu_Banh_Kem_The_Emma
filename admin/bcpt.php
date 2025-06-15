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
                                <span>Người dùng</span>
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

            <div class="col-md-10 col-lg-10 p-4">
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
                    <div class="d-flex">
                        <button class="btn btn-primary me-2" id="exportExcel">
                            <i class="fas fa-file-excel me-2"></i>Xuất Excel
                        </button>
                        <button class="btn btn-secondary" id="printReport">
                            <i class="fas fa-print me-2"></i>In báo cáo
                        </button>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex">
                                <button class="btn btn-primary time-filter me-2 active" data-period="today">Hôm nay</button>
                                <button class="btn btn-primary time-filter me-2" data-period="yesterday">Hôm qua</button>
                                <button class="btn btn-primary time-filter me-2" data-period="week">7 ngày qua</button>
                                <button class="btn btn-primary time-filter me-2" data-period="month">Tháng này</button>
                                <button class="btn btn-primary time-filter me-2" data-period="custom">Tùy chỉnh</button>
                            </div>
                            <div class="date-range-container" style="display: none;">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="dateRangePicker" placeholder="Chọn khoảng thời gian">
                                    <button class="btn bg-primary" id="applyDateRange"><i class="fas fa-check"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
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
                                    <h6 class="text-white mb-1">Tổng kho</h6>
                                    <h3 class="mb-0 text-white" id="inventoryCount">0</h3>
                                    <small class="trend-up" id="inventoryTrend"><i class="fas fa-arrow-up me-1"></i>0% so với kỳ trước</small>
                                </div>
                                <div class="metric-icon">
                                    <i class="fas fa-boxes"></i>
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
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-primary active chart-type" data-type="daily">Theo ngày</button>
                                    <button class="btn btn-primary chart-type" data-type="weekly">Theo tuần</button>
                                    <button class="btn btn-primary chart-type" data-type="monthly">Theo tháng</button>
                                </div>
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
                                <form id="expenseForm">
                                    <div class="mb-3">
                                        <label for="expenseType" class="form-label">Loại chi phí</label>
                                        <select class="form-select" id="expenseType" name="expenseType">
                                            <option value="">Chọn loại chi phí</option>
                                            <option value="electric">Điện</option>
                                            <option value="water">Nước</option>
                                            <option value="internet">Internet</option>
                                            <option value="rent">Thuê mặt bằng</option>
                                            <option value="other">Khác</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="expenseAmount" class="form-label">Số tiền (VNĐ)</label>
                                        <input type="number" class="form-control" id="expenseAmount" name="expenseAmount" min="0" placeholder="Nhập số tiền">
                                    </div>
                                    <div class="mb-3">
                                        <label for="expenseNote" class="form-label">Ghi chú</label>
                                        <input type="text" class="form-control" id="expenseNote" name="expenseNote" placeholder="Ghi chú thêm (nếu có)">
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Lưu chi phí</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewOrderModal" tabindex="-1" aria-labelledby="viewOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewOrderModalLabel">Chi tiết đơn hàng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>Thông tin khách hàng</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Khách hàng:</strong> <span id="orderCustomerName"></span></p>
                                    <p><strong>Số điện thoại:</strong> <span id="orderCustomerPhone"></span></p>
                                    <p><strong>Email:</strong> <span id="orderCustomerEmail"></span></p>
                                    <p><strong>Địa chỉ:</strong> <span id="orderCustomerAddress"></span></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Thông tin đơn hàng</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Mã đơn hàng:</strong> <span id="orderId"></span></p>
                                    <p><strong>Ngày đặt hàng:</strong> <span id="orderDate"></span></p>
                                    <p><strong>Phương thức thanh toán:</strong> <span id="orderPaymentMethod"></span></p>
                                    <p><strong>Trạng thái:</strong> <span id="orderStatus"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Chi tiết sản phẩm</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0" id="orderItemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th>Đơn giá</th>
                                            <th>Số lượng</th>
                                            <th>Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody id="orderItemsBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn bg-secondary" data-bs-dismiss="modal">Đóng</button>
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
    </script>
</body>
</html>