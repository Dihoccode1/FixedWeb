

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `admin_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(80) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(191) NOT NULL,
  `role` varchar(80) NOT NULL DEFAULT 'admin',
  `status` enum('active','locked') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `name`, `role`, `status`, `created_at`) VALUES
(1, 'quanly1', '221b37fcdb52d0f7c39bbd0be211db0e1c00ca5fbecd5788780463026c6b964b', 'Quản trị viên', 'admin', 'active', '2026-04-01 22:09:39');



CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` text NOT NULL,
  `status` enum('active','hidden') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `status`, `created_at`) VALUES
(1, 'Sáp vuốt tóc', 'sap-vuot-toc', '', 'active', '2026-04-01 22:09:39'),
(2, 'Gôm xịt tóc', 'gom-xit-toc', '', 'active', '2026-04-01 22:09:39'),
(3, 'Dầu xả', 'dau-xa', '', 'active', '2026-04-01 22:09:39'),
(4, 'Bột tạo phồng', 'bt-to-phng', '', 'active', '2026-04-01 22:53:31');


CREATE TABLE `import_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `receipt_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `import_items` (`id`, `receipt_id`, `product_id`, `quantity`, `unit_cost`, `created_at`) VALUES
(10, 10, 7, 20, 15000.00, '2026-04-03 11:48:40'),
(38, 13, 21, 22, 100000.00, '2026-04-03 12:02:41'),
(39, 14, 21, 25, 200000.00, '2026-04-03 12:04:50'),
(40, 15, 21, 45, 100000.00, '2026-04-03 12:06:32'),
(41, 16, 7, 12, 30000.00, '2026-04-03 12:13:57');


CREATE TABLE `import_receipts` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(80) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `supplier` varchar(255) NOT NULL,
  `status` enum('draft','completed') NOT NULL DEFAULT 'draft',
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `import_receipts` (`id`, `code`, `created_at`, `supplier`, `status`, `note`) VALUES
(6, 'PN-20260402-522', '2026-04-02 00:00:00', 'Davines', 'completed', ''),
(7, 'PN-20260402-521', '2026-04-02 00:00:00', '', 'completed', ''),
(8, 'PN-20260402-001', '2026-04-02 00:00:00', 'Davines', 'completed', ''),
(9, 'PN-20260402-263', '2026-04-02 00:00:00', 'Davines', 'completed', ''),
(10, 'PN-20260403-096', '2026-04-03 00:00:00', '', 'completed', ''),
(13, 'PN-20260403-528', '2026-04-03 00:00:00', '', 'completed', ''),
(14, 'PN-20260403-622', '2026-04-03 00:00:00', '', 'completed', ''),
(15, 'PN-20260403-898', '2026-04-03 00:00:00', '', 'completed', ''),
(16, 'PN-20260403-680', '2026-04-03 00:00:00', '', 'completed', '');



CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_number` varchar(80) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(191) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(191) NOT NULL,
  `shipping_address` text NOT NULL,
  `address_type` varchar(80) NOT NULL DEFAULT 'home',
  `payment_method` enum('cash','bank','online') NOT NULL DEFAULT 'cash',
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('new','confirmed','shipped','cancelled') NOT NULL DEFAULT 'new',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `orders` (`id`, `order_number`, `user_id`, `full_name`, `phone`, `email`, `shipping_address`, `address_type`, `payment_method`, `total_amount`, `status`, `created_at`) VALUES
(12, 'ORD20260403071659962', 5, 'bùi thành lộc', '0338286525', 'thanhloc29052006@gmail.com', '140B To 3 Ap Xom Chua, tân lân, cần đước, thanhloc29052006@gmail.com', 'home', 'cash', 406000.00, 'new', '2026-04-03 12:16:59');


CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(14,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`, `total_price`) VALUES
(14, 12, 23, 1, 406000.00, 406000.00);



CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `sku` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `description` text NOT NULL,
  `unit` varchar(64) NOT NULL DEFAULT 'cái',
  `quantity` int(11) NOT NULL DEFAULT 0,
  `cost_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `profit_margin` decimal(5,2) NOT NULL DEFAULT 0.00,
  `sale_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `supplier` varchar(255) NOT NULL DEFAULT '',
  `status` enum('selling','hidden') NOT NULL DEFAULT 'selling',
  `image` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `products` (`id`, `sku`, `name`, `category_id`, `description`, `unit`, `quantity`, `cost_price`, `profit_margin`, `sale_price`, `supplier`, `status`, `image`, `created_at`, `updated_at`) VALUES
(7, 'SP001', 'Tinh dầu dưỡng tóc Byrd Hydrating Hair Oil', 3, 'Tinh dầu dưỡng ẩm sâu dành cho tóc với chiết xuất 100% tự nhiên (All Natural). Giúp phục hồi tóc khô xơ, mang lại mái tóc bóng mượt, mềm mại mà không gây bết dính. Rất thích hợp để dưỡng tóc hàng ngày, cấp ẩm hoặc dùng trước khi sấy tạo kiểu để bảo vệ tóc khỏi nhiệt độ cao.', 'Chai', 53, 23120.46, 13.00, 26126.12, 'Byrd', 'selling', 'assets/images/product/prod_69ce32404ba3c.webp', '2026-04-02 16:09:20', '2026-04-03 12:14:00'),
(8, 'SP002', 'Tinh chất kích thích mọc tóc Davines Energizing Superactive', 3, 'Tinh chất (Serum) đặc trị cao cấp thuộc dòng Naturaltech của Davines, chuyên dành cho da đầu yếu, tóc thưa và dễ gãy rụng. Sản phẩm giúp kích thích tuần hoàn máu dưới da đầu, giảm rụng tóc do nội tiết tố và kích thích nang tóc phát triển khỏe mạnh. Thiết kế dạng ống hút (dropper) giúp dễ dàng tra trực tiếp lên da đầu.', 'Chai', 32, 25000.00, 6.00, 26500.00, 'Davines', 'selling', 'assets/images/product/prod_69ce32a980e50.webp', '2026-04-02 16:11:05', '2026-04-03 12:00:47'),
(9, 'SP003', 'Tinh dầu dưỡng tóc bóng mềm Davines OI Oil', 3, 'Tinh dầu dưỡng tóc đa năng, biểu tượng của Davines. Giúp chống rối, chống xù, tăng độ bóng sáng và làm mềm mượt tóc ngay lập tức. Sản phẩm tạo lớp màng mỏng bảo vệ cấu trúc tóc khỏi tác hại của tia UV và nhiệt độ máy sấy. Mang lại hương thơm sang trọng, quyến rũ lưu lại lâu trên tóc.', 'Chai', 153, 370849.67, 6.00, 393100.65, 'Davines', 'selling', 'assets/images/product/prod_69ce32fb37673.webp', '2026-04-02 16:12:27', '2026-04-03 12:00:47'),
(10, 'SP004', 'Gel trị gàu làm sạch da đầu Davines Purifying Anti-Dandruff', 3, 'Gel đặc trị thuộc dòng Naturaltech của Davines, giải pháp hoàn hảo cho các vấn đề về gàu (cả gàu khô và gàu ướt) và ngứa da đầu. Chứa các thành phần kháng khuẩn và chống nấm tự nhiên, giúp thanh lọc da đầu, duy trì môi trường chân tóc sạch sẽ, khỏe mạnh, ngăn ngừa gàu quay trở lại. Dùng thoa trực tiếp lên da đầu.', 'Tuýp', 32, 25000.00, 5.00, 26250.00, 'Davines', 'selling', 'assets/images/product/prod_69ce334a1d132.webp', '2026-04-02 16:13:46', '2026-04-03 12:00:47'),
(11, 'SP005', 'Gôm xịt tóc 2VEE Hair Spray', 2, 'Dòng gôm xịt tóc cao cấp đến từ thương hiệu 2VEE Barros của Hàn Quốc. Sản phẩm mang lại khả năng giữ nếp cực tốt nhưng vẫn giữ được độ tự nhiên, bồng bềnh cho mái tóc, không gây bóng bết hay cứng đơ. Điểm cộng lớn là hương thơm nam tính, dễ chịu và rất dễ gội rửa sau khi sử dụng.', 'Chai', 50, 270000.00, 8.00, 291600.00, '2VEE Barros', 'selling', 'assets/images/product/prod_69ce33f5cc962.webp', '2026-04-02 16:16:37', '2026-04-03 12:00:47'),
(12, 'SP006', 'Gôm xịt tóc TIGI Bed Head Hard Head', 2, 'Sản phẩm gôm xịt giữ nếp \"hạng nặng\" (Extreme Hold) nổi tiếng toàn cầu của TIGI. Cung cấp khả năng khóa form tóc ngay lập tức, bất chấp gió mạnh hay mũ bảo hiểm. Tốc độ khô cực nhanh, tạo hiệu ứng mờ tự nhiên và không để lại vảy trắng trên tóc. Mùi hương dịu nhẹ, sang trọng chuẩn salon.', 'Chai', 28, 1739000.00, 7.00, 1860730.00, 'TIGI', 'selling', 'assets/images/product/prod_69ce343e68481.webp', '2026-04-02 16:17:50', '2026-04-03 12:00:47'),
(13, 'SP007', 'Gôm xịt tóc Butterfly Shadow Hard Hold', 2, 'Dòng gôm xịt tóc quốc dân với mức giá vô cùng hợp lý và dung tích siêu lớn. Cung cấp độ giữ nếp cứng (Hard Hold), giúp cố định form tóc chắc chắn suốt cả ngày dài. Tích hợp chiết xuất trái cây tự nhiên mang lại mùi hương thơm mát. Rất được ưa chuộng tại các barbershop và salon.', 'Chai', 35, 140000.00, 8.00, 151200.00, 'Butterfly Shadow', 'selling', 'assets/images/product/prod_69ce347f1b8d8.webp', '2026-04-02 16:18:55', '2026-04-03 12:00:44'),
(14, 'SP008', 'Gôm xịt tóc Davines Extra Strong Hairspray', 2, 'Gôm xịt tóc tạo kiểu cao cấp của Ý với khả năng giữ nếp cực mạnh (Extra Strong). Sản phẩm giúp tóc chống lại độ ẩm môi trường, khóa chặt các nếp tóc phức tạp nhất mà không gây bết dính hay để lại cặn trắng. Dễ dàng dùng lược chải lại và gội sạch bằng nước. Thiết kế bao bì tối giản, bảo vệ môi trường.', 'Chai', 36, 550000.00, 7.00, 588500.00, 'Davines', 'selling', 'assets/images/product/prod_69ce34c802373.webp', '2026-04-02 16:20:08', '2026-04-03 12:00:44'),
(15, 'SP009', 'Sáp vuốt tóc Apestomen Nitro Wax', 1, 'Sáp vuốt tóc Apestomen Nitro Wax mang lại khả năng giữ nếp mạnh mẽ (Strong Hold) cùng khả năng tạo kiểu sáng tạo vô hạn. Phù hợp cho những mái tóc cần độ phồng (volume) cao và hiệu ứng lọn tóc (texture) rõ nét. Hoàn thiện mờ tự nhiên không bóng bết, dễ dàng gội rửa sau khi dùng.', 'Hộp', 50, 279000.00, 6.00, 295740.00, 'Apestomen', 'selling', 'assets/images/product/prod_69ce3549c90ad.webp', '2026-04-02 16:22:17', '2026-04-03 12:00:44'),
(16, 'SP010', 'Sáp vuốt tóc By Vilain Gold Digger', 1, 'Biểu tượng sáp vuốt tóc cao cấp đến từ Đan Mạch. By Vilain Gold Digger cung cấp độ giữ nếp cực cao (Extreme Hold) và hoàn thiện mờ hoàn hảo (Matte Finish). Chất sáp đặc biệt giúp làm dày tóc, tạo độ phồng tối đa và cực kỳ dễ dàng vuốt lại (restyle) lấy lại form dáng sau khi đội mũ bảo hiểm.', 'Hộp', 48, 150000.00, 16.00, 174000.00, 'By Vilain', 'selling', 'assets/images/product/prod_69ce357a6621b.webp', '2026-04-02 16:23:06', '2026-04-03 12:01:00'),
(17, 'SP011', 'Sáp vuốt tóc Dapper Dan Matt Paste', 1, 'Sản phẩm chủ lực mang tính biểu tượng của Dapper Dan với khả năng giữ nếp cao (High Hold) và độ bóng thấp (Low Shine). Chất sáp dạng paste mềm mịn, cực kỳ dễ đánh tan và ăn vào tóc. Thích hợp cho nhiều phong cách từ cổ điển gọn gàng đến hiện đại rối tự nhiên. Mùi hương Vintage Cologne nam tính cực kỳ cuốn hút.', 'Hộp', 26, 450000.00, 8.00, 486000.00, 'Dapper Dan', 'selling', 'assets/images/product/prod_69ce35a812bab.webp', '2026-04-02 16:23:52', '2026-04-03 12:00:44'),
(18, 'SP012', 'Sáp vuốt tóc Dapper Dan Super Hold Clay', 1, 'Dòng sáp đất sét (Clay) với độ giữ nếp siêu hạng (Super Hold) và hoàn thiện mờ tuyệt đối (Ultra Matte). Đây là giải pháp hoàn hảo cho những mái tóc rễ tre, dày, cứng đầu và khó vào nếp nhất. Cung cấp kết cấu lọn tóc sắc nét và giữ form bền bỉ bất chấp thời tiết.', 'Hộp', 43, 100000.00, 6.00, 106000.00, 'Dapper Dan', 'selling', 'assets/images/product/prod_69ce36057dd41.webp', '2026-04-02 16:25:25', '2026-04-03 12:00:44'),
(19, 'SP013', 'Sáp vuốt tóc Davines Strong Dry Wax', 1, 'Sáp tạo kiểu khô cao cấp của thương hiệu Davines Ý. Cung cấp độ giữ nếp mạnh mẽ, tạo kết cấu mờ (Mat textures) sắc nét mà không làm bết dính hay nặng tóc. Chất sáp khô ráo, có khả năng hút dầu nhẹ giúp da đầu luôn thoáng mát. Thiết kế bao bì bằng giấy gói thủ công, thân thiện và bảo vệ môi trường.', 'Hộp', 38, 25000.00, 7.00, 26750.00, 'Davines', 'selling', 'assets/images/product/prod_69ce3634090fe.webp', '2026-04-02 16:26:12', '2026-04-03 12:00:44'),
(20, 'SP014', 'Bột tạo phồng tóc Patricks HP1 Ultra Matte Hair Powder', 4, 'Dòng bột tạo phồng cao cấp đến từ thương hiệu xa xỉ Patricks (Úc). HP1 mang lại hiệu ứng mờ tuyệt đối (Ultra Matte) và khả năng làm dày tóc (Thickening) đáng kinh ngạc. Sản phẩm không trọng lượng, không gây bết dính, giúp chân tóc đứng vững cả ngày. Đặc biệt tích hợp công nghệ phục hồi tóc độc quyền của hãng.', 'Lọ', 21, 50000.00, 5.00, 52500.00, 'Patricks', 'selling', 'assets/images/product/prod_69ce3723bc766.png', '2026-04-02 16:30:11', '2026-04-03 12:00:44'),
(21, 'SP015', 'Bột tạo phồng Dapper Dan Ultra Matte Texture Dust', 4, 'Sản phẩm tạo phồng siêu việt của Dapper Dan với khả năng giữ nếp chắc chắn (Firm Hold). Bột hòa tan tức thì vào tóc, hút sạch dầu thừa và tạo ra độ phồng rộp cùng kết cấu (texture) cực kỳ rõ nét. Rất thích hợp cho những mái tóc mỏng xẹp cần độ bồng bềnh tự nhiên, mờ hoàn toàn.', 'Lọ', 127, 92153.54, 6.00, 97682.76, 'Dapper Dan', 'selling', 'assets/images/product/prod_69ce3756985d0.webp', '2026-04-02 16:31:02', '2026-04-03 12:06:36'),
(22, 'SP016', 'Bột tạo phồng Reuzel Matte Texture Powder', 4, 'Vũ khí bí mật từ những gã thợ cạo Hà Lan - Reuzel. Sản phẩm là dạng bột mịn không trọng lượng, giúp làm tăng độ phồng tối đa và độ dày cho mọi loại tóc. Giữ nếp nhẹ nhàng linh hoạt, không gây cảm giác nặng đầu. Thích hợp dùng hàng ngày để tạo các kiểu tóc messy rủ tự nhiên.', 'Lọ', 22, 494000.00, 7.00, 528580.00, 'Reuzel', 'selling', 'assets/images/product/prod_69ce37864508e.webp', '2026-04-02 16:31:50', '2026-04-03 12:00:44'),
(23, 'SP017', 'Bột tạo phồng Roug Men''s Grooming Texture Dust', 4, 'Giải pháp tạo phồng tóc chuyên nghiệp từ Roug Men''s Grooming. Bột Texture Dust giúp nhấc bổng chân tóc, tạo cấu trúc lọn đan xen đẹp mắt chỉ với vài lần rắc. Khả năng hút ẩm tốt giúp mái tóc luôn khô ráo, duy trì form tóc bền bỉ ngay cả trong thời tiết nóng bức.', 'Lọ', 26, 350000.00, 16.00, 406000.00, 'Roug Men''s Grooming', 'selling', 'assets/images/product/prod_69ce37b1afa8b.webp', '2026-04-02 16:32:33', '2026-04-03 12:15:28'),
(24, 'SP018', 'Bột tạo phồng Apestomen Volcanic Ash Styling Powder', 4, 'Bột tạo phồng độc đáo với thành phần chứa chiết xuất tro núi lửa, mang lại khả năng hút dầu thừa cực mạnh (siêu trị mồ hôi dầu). Cung cấp độ phồng tức thì (Instant Volume) và kết cấu tóc sắc nét chỉ trong vài giây. Dễ dàng apply, không rít tóc, dễ dàng vuốt lại sau khi đội mũ bảo hiểm.', 'Lọ', 35, 300000.00, 4.00, 312000.00, 'Apestomen', 'selling', 'assets/images/product/prod_69ce37e6085da.webp', '2026-04-02 16:33:26', '2026-04-03 12:00:44');



CREATE TABLE `stock_movements` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `movement_type` enum('import','export','adjust') NOT NULL,
  `quantity` int(11) NOT NULL,
  `occurred_at` datetime NOT NULL DEFAULT current_timestamp(),
  `source_type` varchar(32) NOT NULL DEFAULT '',
  `source_item_id` int(10) UNSIGNED DEFAULT NULL,
  `ref_code` varchar(80) NOT NULL DEFAULT '',
  `note` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `stock_movements` (`id`, `product_id`, `movement_type`, `quantity`, `occurred_at`, `source_type`, `source_item_id`, `ref_code`, `note`, `created_at`) VALUES
(3, 7, 'import', 10, '2026-04-02 00:00:00', 'import_item', 3, 'PN-20260402-335', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-02 16:35:54'),
(4, 7, 'import', 10, '2026-04-02 00:00:00', 'import_item', 4, 'PN-20260402-966', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-02 16:36:39'),
(5, 9, 'import', 10, '2026-04-02 00:00:00', 'import_item', 5, 'PN-20260402-303', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-02 17:17:23'),
(6, 9, 'import', 3, '2026-04-02 00:00:00', 'import_item', 6, 'PN-20260402-522', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-02 17:19:13'),
(7, 16, 'import', 8, '2026-04-02 00:00:00', 'import_item', 7, 'PN-20260402-521', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-02 17:27:04'),
(8, 21, 'import', 7, '2026-04-02 00:00:00', 'import_item', 8, 'PN-20260402-001', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-02 17:43:00'),
(9, 7, 'import', 6, '2026-04-02 00:00:00', 'import_item', 9, 'PN-20260402-263', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-02 22:15:35'),
(10, 7, 'export', 5, '2026-04-02 23:04:47', 'order_item', 1, 'ORD20260402180432254', 'Xuat kho khi don hang da giao', '2026-04-02 23:04:47'),
(11, 21, 'export', 5, '2026-04-02 23:04:47', 'order_item', 2, 'ORD20260402180432254', 'Xuat kho khi don hang da giao', '2026-04-02 23:04:47'),
(12, 21, 'export', 2, '2026-04-03 00:36:23', 'order_item', 9, 'ORD20260402193353198', 'Xuat kho khi don hang da giao', '2026-04-03 00:36:23'),
(13, 13, 'import', 35, '2026-04-03 00:00:00', 'import_item', 26, 'PN-20260403-094', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:00:44'),
(14, 14, 'import', 36, '2026-04-03 00:00:00', 'import_item', 27, 'PN-20260403-094', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:00:44'),
(15, 15, 'import', 50, '2026-04-03 00:00:00', 'import_item', 28, 'PN-20260403-094', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:00:44'),
(16, 16, 'import', 40, '2026-04-03 00:00:00', 'import_item', 29, 'PN-20260403-094', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:00:44'),
(17, 17, 'import', 26, '2026-04-03 00:00:00', 'import_item', 30, 'PN-20260403-094', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:00:44'),
(18, 18, 'import', 43, '2026-04-03 00:00:00', 'import_item', 31, 'PN-20260403-094', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:00:44'),
(19, 19, 'import', 38, '2026-04-03 00:00:00', 'import_item', 32, 'PN-20260403-094', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:00:44'),
(20, 20, 'import', 21, '2026-04-03 00:00:00', 'import_item', 33, 'PN-20260403-094', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:00:44'),
(21, 21, 'import', 35, '2026-04-03 00:00:00', 'import_item', 34, 'PN-20260403-094', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:00:44'),
(22, 22, 'import', 22, '2026-04-03 00:00:00', 'import_item', 35, 'PN-20260403-094', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:00:44'),
(23, 23, 'import', 27, '2026-04-03 00:00:00', 'import_item', 36, 'PN-20260403-094', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:00:44'),
(24, 24, 'import', 35, '2026-04-03 00:00:00', 'import_item', 37, 'PN-20260403-094', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:00:44'),
(25, 8, 'import', 32, '2026-04-03 00:00:00', 'import_item', 21, 'PN-20260403-891', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:00:47'),
(26, 9, 'import', 140, '2026-04-03 00:00:00', 'import_item', 22, 'PN-20260403-891', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:00:47'),
(27, 10, 'import', 32, '2026-04-03 00:00:00', 'import_item', 23, 'PN-20260403-891', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:00:47'),
(28, 11, 'import', 50, '2026-04-03 00:00:00', 'import_item', 24, 'PN-20260403-891', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:00:47'),
(29, 12, 'import', 28, '2026-04-03 00:00:00', 'import_item', 25, 'PN-20260403-891', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:00:47'),
(30, 7, 'import', 20, '2026-04-03 00:00:00', 'import_item', 10, 'PN-20260403-096', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:00:50'),
(31, 21, 'import', 22, '2026-04-03 00:00:00', 'import_item', 38, 'PN-20260403-528', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:02:44'),
(32, 21, 'import', 25, '2026-04-03 00:00:00', 'import_item', 39, 'PN-20260403-622', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:04:53'),
(33, 21, 'import', 45, '2026-04-03 00:00:00', 'import_item', 40, 'PN-20260403-898', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:06:36'),
(34, 7, 'import', 12, '2026-04-03 00:00:00', 'import_item', 41, 'PN-20260403-680', 'Nhập kho từ phiếu nhập hoàn thành', '2026-04-03 12:14:00'),
(35, 23, 'export', 1, '2026-04-03 12:15:28', 'order_item', 13, 'ORD20260403071507309', 'Xuat kho khi don hang da giao', '2026-04-03 12:15:28');



CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `street_address` varchar(255) NOT NULL DEFAULT '',
  `ward` varchar(120) NOT NULL DEFAULT '',
  `district` varchar(120) NOT NULL DEFAULT '',
  `city_province` varchar(120) NOT NULL DEFAULT '',
  `address` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','locked') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `phone`, `street_address`, `ward`, `district`, `city_province`, `address`, `created_at`, `status`) VALUES
(5, 'bùi thành lộc', 'thanhloc29052006@gmail.com', '$2y$10$dU3.uOHQtClJgCVjr7VlreiorYADrZlQmkaJBIfHjmQAgcGoD233e', '0338286525', '140B To 3 Ap Xom Chua', 'tân lân', 'cần đước', 'thanhloc29052006@gmail.com', '140B To 3 Ap Xom Chua, tân lân, cần đước, thanhloc29052006@gmail.com', '2026-04-03 12:03:53', 'active');



CREATE TABLE `user_shipping_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `slot_number` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `full_name` varchar(191) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(191) NOT NULL DEFAULT '',
  `shipping_address` text NOT NULL,
  `note` varchar(255) NOT NULL DEFAULT '',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `user_shipping_profiles` (`id`, `user_id`, `slot_number`, `full_name`, `phone`, `email`, `shipping_address`, `note`, `is_default`, `created_at`, `updated_at`) VALUES
(4, 5, 1, 'thiên phú', '0338286525', 'thanhloc29052006@gmail.com', 'an giang ông hổ', '', 1, '2026-04-03 12:14:56', '2026-04-04 22:22:22');



ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Chỉ mục cho bảng `import_items`
--
ALTER TABLE `import_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_import_product` (`product_id`),
  ADD KEY `fk_import_receipt` (`receipt_id`);

--
-- Chỉ mục cho bảng `import_receipts`
--
ALTER TABLE `import_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `fk_order_user` (`user_id`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_product` (`product_id`),
  ADD KEY `fk_order_order` (`order_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `fk_product_category` (`category_id`);

--
-- Chỉ mục cho bảng `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_stock_source` (`source_type`,`source_item_id`),
  ADD KEY `idx_stock_product_time` (`product_id`,`occurred_at`),
  ADD KEY `idx_stock_type_time` (`movement_type`,`occurred_at`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `user_shipping_profiles`
--
ALTER TABLE `user_shipping_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shipping_profiles_user` (`user_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `import_items`
--
ALTER TABLE `import_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT cho bảng `import_receipts`
--
ALTER TABLE `import_receipts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT cho bảng `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `user_shipping_profiles`
--
ALTER TABLE `user_shipping_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `import_items`
--
ALTER TABLE `import_items`
  ADD CONSTRAINT `fk_import_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_import_receipt` FOREIGN KEY (`receipt_id`) REFERENCES `import_receipts` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Các ràng buộc cho bảng `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `fk_stock_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Các ràng buộc cho bảng `user_shipping_profiles`
--
ALTER TABLE `user_shipping_profiles`
  ADD CONSTRAINT `fk_shipping_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;
