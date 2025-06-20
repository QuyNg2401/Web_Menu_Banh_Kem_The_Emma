<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
checkAuth();

// Lấy ngày bắt đầu tuần từ GET
$startOfWeek = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('monday this week'));
$weekDates = [];
for ($i = 0; $i < 7; $i++) {
    $weekDates[] = date('Y-m-d', strtotime("$startOfWeek +$i day"));
}
$weekdays = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'];

// Lấy danh sách nhân viên
$users = $db->select("SELECT id, name FROM users WHERE role = 'user'");

// Xác định nhân viên được chọn
$selected_user_id = isset($_POST['user_id']) ? $_POST['user_id'] : (isset($_GET['user_id']) ? $_GET['user_id'] : '');

// Xử lý lưu lịch làm
$success = false;
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['schedule']) &&
    $selected_user_id &&
    isset($_POST['save_schedule'])
) {
    foreach ($_POST['schedule'] as $date => $times) {
        if (isset($times['active'])) {
            if (!isset($times['from']) || !isset($times['to'])) continue;
            $from = $times['from'];
            $to = $times['to'];
            if ($from && $to) {
                $weekday = date('N', strtotime($date));
                $exists = $db->selectOne("SELECT id FROM attendance_details WHERE user_id = ? AND date = ?", [$selected_user_id, $date]);
                if ($exists) {
                    $db->update('attendance_details', [
                        'time_from' => $from,
                        'time_to' => $to,
                        'weekday' => $weekday
                    ], ['id' => $exists['id']]);
                } else {
                    $db->insert('attendance_details', [
                        'user_id' => $selected_user_id,
                        'date' => $date,
                        'weekday' => $weekday,
                        'time_from' => $from,
                        'time_to' => $to
                    ]);
                }
            }
        } elseif (isset($times['exists'])) {
            // Nếu có exists mà không có active, tức là bỏ chọn => xóa
            $db->delete('attendance_details', 'user_id = ? AND date = ?', [
                $selected_user_id,
                $date
            ]);
        }
    }
    $success = true;
}
// Lấy dữ liệu lịch đã có để fill vào form
$details = [];
$detailsMap = [];
if ($selected_user_id) {
    $details = $db->select(
        "SELECT user_id, date, time_from, time_to FROM attendance_details WHERE user_id = ? AND date BETWEEN ? AND ?",
        [$selected_user_id, $weekDates[0], $weekDates[6]]
    );
    foreach ($details as $row) {
        $detailsMap[$row['date']] = [$row['time_from'], $row['time_to']];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tạo lịch làm tuần</title>
    <link rel="stylesheet" href="../Assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            --primary-color: #E5BC6F;
        }
        body {
            background: #f6f8fa;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .admin-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .main-content {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08), 0 1.5px 4px rgba(229,188,111,0.08);
            padding: 36px 36px 28px 36px;
            width: 100%;
            max-width: 700px;
        }
        .main-content h2 {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0;
        }
        .main-content h2 span {
            color: #555;
            font-size: 1rem;
        }
        .schedule-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fafbfc;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(229,188,111,0.07);
        }
        
        .schedule-table th {
            background: #f8f3e7;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.05rem;
            border-bottom: 2px solid #f3e3c2;
        }
        .schedule-table td {
            background: #fff;
            border-bottom: 1px solid #f0f0f0;
        }
        .schedule-table tr:last-child td {
            border-bottom: none;
        }
        .schedule-table input.timepicker {
            width: 90px;
            padding: 5px 8px;
            border: 1px solid #f3e3c2;
            border-radius: 5px;
            font-size: 1rem;
            background: #fdf8ef;
            transition: border 0.2s;
        }
        .schedule-table input.timepicker:focus {
            border: 1.5px solid var(--primary-color);
            outline: none;
        }
        .btn-save {
            background: linear-gradient(90deg, var(--primary-color) 60%, #d1a74b 100%);
            color: #fff;
            border: none;
            padding: 10px 28px;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(229,188,111,0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .btn-save:hover {
            background: linear-gradient(90deg, #d1a74b 60%, var(--primary-color) 100%);
            box-shadow: 0 4px 16px rgba(229,188,111,0.13);
        }
        .alert-success {
            color: #fff;
            background: var(--primary-color);
            border-radius: 6px;
            padding: 10px 18px;
            margin-bottom: 18px;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(229,188,111,0.08);
        }
        .back-link { margin-left:12px; white-space:nowrap; display:inline-flex; align-items:center; text-decoration:none; color:var(--primary-color); transition:color 0.2s; }
        .back-link .fa-arrow-left { transition: transform 0.2s; color: var(--primary-color); }
        .back-link:hover { color:var(--primary-color); }
        .back-link:hover span { text-decoration: underline; }
        .back-link:hover .fa-arrow-left { transform: translateX(-6px); }
        select, input[type="text"] {
            font-size: 1rem;
            padding: 6px 10px;
            border-radius: 5px;
            border: 1px solid #f3e3c2;
            background: #fdf8ef;
            margin-right: 6px;
            transition: border 0.2s;
        }
        select:focus, input[type="text"]:focus {
            border: 1.5px solid var(--primary-color);
            outline: none;
        }
        label {
            font-weight: 500;
            color: #333;
        }
        #quick_text {
            width: 220px;
        }
        button[type="button"] {
            background: #f8f3e7;
            color: var(--primary-color);
            border: 1px solid #f3e3c2;
            border-radius: 6px;
            padding: 5px 16px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
        }
        button[type="button"]:hover {
            background: #d1a74b;
            color: #fff;
        }
        @media (max-width: 800px) {
            .main-content { padding: 18px 4vw; max-width: 98vw; }
            .main-content h2 { font-size: 1.2rem; }
            .main-content h2 span { font-size: 0.95rem; }
            .btn-save { font-size: 1rem; padding: 8px 16px; }
            .alert-success { font-size: 0.98rem; padding: 8px 10px; }
        }
        @media (max-width: 600px) {
            .main-content { padding: 8px 1vw; max-width: 100vw; }
            .main-content h2 { font-size: 1rem; }
            .main-content h2 span { font-size: 0.9rem; }
            .btn-save { font-size: 0.95rem; padding: 7px 10px; }
            .alert-success { font-size: 0.95rem; padding: 7px 6px; }
            #quick_text { width: 120px; }
        }
        @media (max-width: 500px) {
            .main-content { padding: 2px 0; }
            .main-content h2 { font-size: 0.95rem; }
            .main-content h2 span { font-size: 0.85rem; }
            .btn-save { font-size: 0.9rem; padding: 6px 6px; }
            .alert-success { font-size: 0.9rem; padding: 6px 2px; }
            .back-link { font-size: 0.95rem; }
        }
        /* Responsive cho bảng */
        .responsive-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }
        .schedule-table {
            min-width: 420px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <main class="main-content" style="margin:32px auto;max-width:1100px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <h2 style="margin:0;">Tạo lịch làm việc tuần<br><span style="font-size:16px;font-weight:normal;">(<?php echo date('d/m/Y', strtotime($weekDates[0])); ?> - <?php echo date('d/m/Y', strtotime($weekDates[6])); ?>)</span></h2>
                <a href="attendance.php" class="back-link"><i class="fas fa-arrow-left" style="margin-right:6px;"></i><span>Quay lại</span></a>
            </div>
            <?php if ($success): ?>
                <div class="alert-success"><i class="fas fa-check-circle"></i> Đã lưu lịch làm tuần thành công!</div>
            <?php endif; ?>
            <form method="POST" id="scheduleForm">
                <div style="margin-bottom:18px;">
                    <label for="user_id"><b>Chọn nhân viên:</b></label>
                    <select name="user_id" id="user_id" required onchange="document.getElementById('scheduleForm').submit();">
                        <option value="">-- Chọn nhân viên --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php if($selected_user_id==$u['id']) echo 'selected'; ?>><?php echo htmlspecialchars($u['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($selected_user_id): ?>
                <!-- Dòng nhập nhanh -->
                <div style="margin-bottom:12px;">
                    <b>Nhập nhanh:</b>
                    <input type="text" id="quick_text" style="width:220px" >
                    <button type="button" onclick="applyQuickText()" style="margin-left:8px;padding:4px 12px;">Áp dụng</button>
                    <span style="color:#888;font-size:13px;margin-left:8px;">(Ví dụ: T2-T7, 9h-17h hoặc T2,T4,T6, 8h-12h)</span>
                </div>
                <div class="responsive-table-wrapper">
                <table class="attendance-table schedule-table">
                    <thead>
                        <tr>
                            <th>Thứ/ngày</th>
                            <th>Chọn & Thời gian làm việc</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($weekDates as $i => $date): ?>
                            <tr>
                                <td><?php echo $weekdays[$i] . '<br>' . date('d/m', strtotime($date)); ?></td>
                                <td>
                                    <input type="checkbox" name="schedule[<?php echo $date; ?>][active]" id="active_<?php echo $i; ?>" onchange="toggleTimeInputs(<?php echo $i; ?>)" <?php echo isset($detailsMap[$date]) ? 'checked' : ''; ?>>
                                    <input type="hidden" name="schedule[<?php echo $date; ?>][exists]" value="1">
                                    <label for="active_<?php echo $i; ?>">Chọn</label>
                                    <input type="text" class="timepicker" id="from_<?php echo $i; ?>" name="schedule[<?php echo $date; ?>][from]" value="<?php echo isset($detailsMap[$date]) ? $detailsMap[$date][0] : ''; ?>" <?php echo isset($detailsMap[$date]) ? '' : 'disabled'; ?>> -
                                    <input type="text" class="timepicker" id="to_<?php echo $i; ?>" name="schedule[<?php echo $date; ?>][to]" value="<?php echo isset($detailsMap[$date]) ? $detailsMap[$date][1] : ''; ?>" <?php echo isset($detailsMap[$date]) ? '' : 'disabled'; ?>>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <div style="margin-top:18px;text-align:right;">
                    <button type="submit" class="btn-save" name="save_schedule" value="1"><i class="fas fa-save"></i> Lưu lịch tuần</button>
                </div>
                <?php endif; ?>
            </form>
            <script>
                function toggleTimeInputs(i) {
                    var cb = document.getElementById('active_' + i);
                    document.getElementById('from_' + i).disabled = !cb.checked;
                    document.getElementById('to_' + i).disabled = !cb.checked;
                }
                // Khởi tạo trạng thái input khi load lại trang
                window.onload = function() {
                    <?php foreach ($weekDates as $i => $date): ?>
                        toggleTimeInputs(<?php echo $i; ?>);
                    <?php endforeach; ?>
                }
                // Hàm áp dụng nhập nhanh bằng text
                window.applyQuickText = function() {
                    var text = document.getElementById('quick_text').value.trim();
                    if (!text) return;
                    // Tách phần ngày và phần giờ: phần cuối là giờ, các phần trước là ngày
                    var parts = text.split(',');
                    if (parts.length < 2) { alert('Vui lòng nhập đúng định dạng!'); return; }
                    var timePart = parts[parts.length - 1].replace(/\s/g, '');
                    var dayPart = parts.slice(0, parts.length - 1).join(',').replace(/\s/g, '');
                    // Xử lý ngày
                    var dayIndexes = [];
                    if (dayPart.includes('-')) {
                        var range = dayPart.split('-');
                        var start = parseDay(range[0]);
                        var end = parseDay(range[1]);
                        if (start === null || end === null) { alert('Sai định dạng ngày!'); return; }
                        for (var i = start; i <= end; i++) dayIndexes.push(i);
                    } else {
                        var days = dayPart.split(/,|;/);
                        days.forEach(function(d) {
                            var idx = parseDay(d);
                            if (idx !== null) dayIndexes.push(idx);
                        });
                    }
                    // Nhận các định dạng giờ phổ biến
                    var timeMatch = timePart.match(/([0-9]{1,2})(?:h|:)?([0-9]{0,2})?-([0-9]{1,2})(?:h|:)?([0-9]{0,2})?/i);
                    if (!timeMatch) { alert('Sai định dạng giờ!'); return; }
                    var from = parseTime(timeMatch[1], timeMatch[2]);
                    var to = parseTime(timeMatch[3], timeMatch[4]);
                    // Áp dụng cho các ngày
                    dayIndexes.forEach(function(i) {
                        var cb = document.getElementById('active_' + i);
                        var fromInput = document.getElementById('from_' + i);
                        var toInput = document.getElementById('to_' + i);
                        if (cb && fromInput && toInput) {
                            cb.checked = true;
                            fromInput.disabled = false;
                            toInput.disabled = false;
                            fromInput.value = from;
                            toInput.value = to;
                            // Gọi flatpickr để cập nhật lại giao diện nếu cần
                            if (fromInput._flatpickr) fromInput._flatpickr.setDate(from, true, 'H:i');
                            if (toInput._flatpickr) toInput._flatpickr.setDate(to, true, 'H:i');
                        }
                    });
                }
                // Chuyển T2 -> 0, T3 -> 1, ..., T7 -> 5, CN -> 6
                function parseDay(str) {
                    str = str.toUpperCase();
                    if (str === 'CN' || str === 'T8') return 6;
                    var m = str.match(/^T(\d)$/);
                    if (m) {
                        var n = parseInt(m[1]);
                        if (n >= 2 && n <= 7) return n - 2;
                    }
                    return null;
                }
                // Chuyển đổi giờ: 7, 7h, 7:00, 07h, 07:00, 7h00 => 07:00
                function parseTime(h, m) {
                    h = (h || '0').padStart(2, '0');
                    m = (m || '00').padEnd(2, '0');
                    return h + ':' + m;
                }
            </script>
            <!-- Flatpickr JS -->
            <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                flatpickr('.timepicker', {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    time_24hr: true
                });
            });
            </script>
        </main>
    </div>
</body>
</html> 