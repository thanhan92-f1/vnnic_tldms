# Module WHMCS W2W_VNNIC

**W2W_VNNIC** là module mở rộng dành cho hệ thống quản trị Webhosting WHMCS, nhằm tự động hóa việc kết nối dữ liệu địa danh và gửi báo cáo biến động (VNNIC-07) cũng như báo cáo tên miền duy trì (VNNIC-06) đến hệ thống của VNNIC thông qua HTTP API (Guzzle).

## Yêu Cầu Cài Đặt Khung
- WHMCS v8.x trở lên.
- **PHP 8.1** (Bắt buộc tương thích đầy đủ với thư viện Guzzle).
- Tiện ích mở rộng cURL được bật trên Máy Chủ PHP.

## Hướng Dẫn Cài Đặt Giao Diện Quản Trị

1. Tải và đặt toàn bộ folder `vnnic_tldms` vào đường dẫn gốc: `/path/to/whmcs/modules/addons/vnnic_tldms/`.
2. Đăng nhập vào khu vực **WHMCS Admin** -> **System Settings** -> **Addon Modules**.
3. Tìm kiếm **VNNIC - GTLD Management System** và nhấp vào nút **Activate**.
4. Chọn **Configure**, nhập thông tin sau:
   - **Môi trường**: Chọn `OT&E` cho Sandbox (Thử nghiệm) hoặc `Production` cho chạy chính thức trên kênh Real.
   - **Client ID**: Do hệ thống VNNIC cung cấp để sử dụng mã hóa Base64 Basic Auth.
   - **Client Secret**: Do hệ thống VNNIC cung cấp đi kèm Client ID.
5. Cấp quyền truy cập (Access Control) cho nhóm nhân viên thao tác trên Module (Ví dụ chọn Full Administrator).
6. Nhấp **Save Changes**. (Khi lưu, Script Activate sẽ tự động tạo bảng dữ liệu phụ trợ là `mod_vnnic_tldms_locations` tĩnh và `mod_vnnic_tldms_logs` cho mục đích lưu nhật ký Log).

## Cấu Hình Cronjob (Lên Lịch)

Hệ thống yêu cầu thiết lập 2 luồng Cronjob vào quản trị Server (Có thể chạy Script CLI hoặc thông qua hệ điều hành Crontab Linux/Cpanel OS).

### 1. Cron Đồng bộ Danh Mục Hành Chính (Tỉnh/Huyện/Xã)
- **Nhiệm vụ:** Để có dữ liệu cấp xuống Dropdown form tạo tài khoản đăng ký Domain của Client WHMCS.
- **Cron (CPanel/Linux):**
  ```bash
  */5 * * * * php -q /path/to/whmcs/modules/addons/vnnic_tldms/cron/sync_data.php
  ```
- Thuyết minh: Cứ mỗi 5 phút, crontab sẽ lôi script chạy kiểm tra. Tuy nhiên Module đã khóa Cooldown tự động chỉ load Data mới nhất từ API VNNIC mỗi 24 giờ 1 lần để chống Spam API.

### 2. Cron Báo Cáo Tên Miền Duy Trì (Biểu mẫu VNNIC-06)
- **Nhiệm vụ:** Gom danh sách mọi tên miền Quốc Tế `Active` của toàn mạng WHMCS và xuất Batch Array nén đẩy sang server VNNIC 1 lần định kỳ tập trung.
- **Cron (CPanel/Linux):**
  ```bash
  0 0 1 * * php -q /path/to/whmcs/modules/addons/vnnic_tldms/cron/report_vnnic06.php
  ```
- *Chạy vào lúc 00:00 của ngày mùng 1 hàng tháng.* Bạn có thể điều chỉnh theo quy định mới nhất của cục tần số VNNIC.

## Kiểm Thử - Giả lập

Sau khi setup 1-2-3-4 xong. Hãy truy cập SSH Server hoặc chạy trên URL của bạn với script sau để TEST kết nối Sandbox Ping Auth:
`php -q /path/to/whmcs/modules/addons/vnnic_tldms/test_connection.php`

Mọi báo cáo (Status 200 / Status 400 error v.v.) qua VNNIC-06 và **(VNNIC-07 qua Hooks real-time)** sẽ hiển thị ở `WHMCS Addon Dashboard > VNNIC - GTLD Log Dashboard`.
