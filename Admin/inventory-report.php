<?php
require_once __DIR__ . '/../includes/common.php';
require_admin();

$pageTitle = 'Báo Cáo Tồn Kho';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle); ?></title>
    <link rel="stylesheet" href="../bootstrap-4.6.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../fontawesome-free-6.7.2-web/css/all.min.css">
    <style>
        body { background: #f5f5f5; }
        .section-title { margin-top: 30px; margin-bottom: 20px; font-size: 1.3rem; font-weight: bold; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .risk-critical { color: #c0392b; font-weight: bold; }
        .risk-warning { color: #f39c12; font-weight: bold; }
        .badge-import { background: #27ae60; }
        .badge-export { background: #e74c3c; }
        .alert-threshold { background: #fff3cd; border-left: 4px solid #f39c12; }
        table th { background: #f8f9fa; }
        .loader { display: none; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-12">
                <h1 class="mb-3">📦 Báo Cáo Tồn Kho</h1>
            </div>
        </div>

        <!-- TAB NAVIGATION -->
        <div class="row mb-3">
            <div class="col-md-12">
                <ul class="nav nav-tabs" id="tabControl">
                    <li class="nav-item">
                        <a class="nav-link active" href="#tab-moment" data-toggle="tab">🕐 Tra Cứu Tồn Tại Thời Điểm</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-warning" data-toggle="tab">⚠️ Cảnh Báo Hết Hàng</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-history" data-toggle="tab">📊 Báo Cáo Nhập/Xuất</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- TAB CONTENT -->
        <div class="tab-content">
            <!-- TAB 1: TRA CỨU TỒN TẠI THỜI ĐIỂM -->
            <div id="tab-moment" class="tab-pane fade show active">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <strong>🕐 Tra Cứu Số Lượng Tồn Tại Một Thời Điểm</strong>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="momentProduct" class="form-label">Chọn Sản Phẩm</label>
                                <select id="momentProduct" class="form-control">
                                    <option value="">-- Chọn sản phẩm --</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="momentDatetime" class="form-label">Thời Điểm</label>
                                <input type="datetime-local" id="momentDatetime" class="form-control">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button id="btnCheckMoment" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Tra Cứu
                                </button>
                            </div>
                        </div>

                        <div id="momentLoader" class="loader text-center mb-2">
                            <div class="spinner-border" role="status"></div>
                        </div>

                        <div id="momentResult" style="display: none;">
                            <div class="alert alert-info">
                                <strong>Sản phẩm:</strong> <span id="momentSKU"></span> - <span id="momentName"></span><br>
                                <strong>Tại thời điểm:</strong> <span id="momentTime"></span><br>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="text-muted small">Tồn Hiện Tại</div>
                                            <div style="font-size: 1.8rem; font-weight: bold;" id="momentCurrent">-</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="text-muted small">Nhập Sau Thời Điểm</div>
                                            <div style="font-size: 1.8rem; font-weight: bold; color: #27ae60;" id="momentImported">-</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="text-muted small">Xuất Sau Thời Điểm</div>
                                            <div style="font-size: 1.8rem; font-weight: bold; color: #e74c3c;" id="momentExported">-</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card" style="background: #e8f5e9; border: 2px solid #4caf50;">
                                        <div class="card-body">
                                            <div class="text-muted small">Tồn Tại Thời Điểm Đó</div>
                                            <div style="font-size: 1.8rem; font-weight: bold; color: #2e7d32;" id="momentAtThatTime">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-secondary mt-3">
                                <strong>Công thức:</strong> Tồn tại thời điểm = Tồn hiện tại - Xuất sau + Nhập sau
                            </div>
                        </div>

                        <div id="momentEmpty" style="text-align: center; color: #999; padding: 40px;">
                            Chọn sản phẩm, thời điểm rồi bấm "Tra Cứu"
                        </div>
                    </div>
                </div>
            </div>
            <div id="tab-warning" class="tab-pane fade show active">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <strong>Sản Phẩm Sắp Hết Hàng</strong>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="warningThreshold" class="form-label">Ngưỡng Cảnh Báo (số lượng)</label>
                                <input type="number" id="warningThreshold" class="form-control" value="5" min="0" max="1000">
                            </div>
                            <div class="col-md-3">
                                <label for="warningSearch" class="form-label">Tìm Kiếm (SKU/Tên)</label>
                                <input type="text" id="warningSearch" class="form-control" placeholder="">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button id="btnLoadWarning" class="btn btn-danger w-100">
                                    <i class="fas fa-sync"></i> Tải Dữ Liệu
                                </button>
                            </div>
                        </div>

                        <div id="warningLoader" class="loader text-center mb-2">
                            <div class="spinner-border" role="status"></div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="warningTable">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Tên Sản Phẩm</th>
                                        <th>Danh Mục</th>
                                        <th style="text-align: right;">Tồn Kho</th>
                                        <th>Mức Cảnh Báo</th>
                                        <th>Nhà Cung Cấp</th>
                                        <th>Giá Bán</th>
                                    </tr>
                                </thead>
                                <tbody id="warningBody">
                                    <tr><td colspan="7" style="text-align: center; color: #999;">Chọn ngưỡng và bấm "Tải Dữ Liệu"</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: BÁOCÁO NHẬP/XUẤT -->
            <div id="tab-history" class="tab-pane fade">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <strong>Báo Cáo Nhập Hàng / Xuất Hàng</strong>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="historyProduct" class="form-label">Chọn Sản Phẩm</label>
                                <select id="historyProduct" class="form-control">
                                    <option value="">-- Chọn sản phẩm --</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="historyFrom" class="form-label">Từ Ngày</label>
                                <input type="date" id="historyFrom" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label for="historyTo" class="form-label">Đến Ngày</label>
                                <input type="date" id="historyTo" class="form-control">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button id="btnLoadHistory" class="btn btn-info w-100">
                                    <i class="fas fa-search"></i> Tìm
                                </button>
                            </div>
                        </div>

                        <div id="historyLoader" class="loader text-center mb-2">
                            <div class="spinner-border" role="status"></div>
                        </div>

                        <div id="historyResult" style="display: none;">
                            <!-- Summary -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="text-muted">Tồn Kho Hiện Tại</div>
                                            <div style="font-size: 1.8rem; font-weight: bold;" id="histSummaryCurrentQty">-</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="text-muted">Tổng Nhập</div>
                                            <div style="font-size: 1.8rem; font-weight: bold; color: #27ae60;" id="histSummaryImport">-</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="text-muted">Tổng Xuất</div>
                                            <div style="font-size: 1.8rem; font-weight: bold; color: #e74c3c;" id="histSummaryExport">-</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="text-muted">Net (Nhập - Xuất)</div>
                                            <div style="font-size: 1.8rem; font-weight: bold; color: #2980b9;" id="histSummaryNet">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Transaction List -->
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>📥 Nhập Hàng</h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered" id="importTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Ngày</th>
                                                    <th style="text-align: right;">Số Lượng</th>
                                                    <th>Phiếu Nhập</th>
                                                    <th>Nhà Cung Cấp</th>
                                                </tr>
                                            </thead>
                                            <tbody id="importBody">
                                                <tr><td colspan="4" style="text-align: center; color: #999;">Không có dữ liệu</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h5>📤 Xuất Hàng</h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered" id="exportTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Ngày</th>
                                                    <th style="text-align: right;">Số Lượng</th>
                                                    <th>Đơn Hàng</th>
                                                    <th>Khách</th>
                                                </tr>
                                            </thead>
                                            <tbody id="exportBody">
                                                <tr><td colspan="4" style="text-align: center; color: #999;">Không có dữ liệu</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="historyEmpty" style="text-align: center; color: #999; padding: 40px;">
                            Chọn sản phẩm và bấm "Tìm" để xem báo cáo nhập/xuất
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../bootstrap-4.6.2-dist/js/jquery-3.6.0.min.js"></script>
    <script src="../bootstrap-4.6.2-dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const API_URL = './api/inventory.php';

        // ========== TAB 0: MOMENT QUERY ==========
        $('#btnCheckMoment').click(async function() {
            console.log('btnCheckMoment clicked');
            const productId = $('#momentProduct').val();
            const datetime = $('#momentDatetime').val();

            console.log('productId:', productId, 'datetime:', datetime);

            if (!productId) {
                alert('Vui lòng chọn sản phẩm');
                return;
            }
            if (!datetime) {
                alert('Vui lòng chọn thời điểm');
                return;
            }

            $('#momentLoader').show();
            $('#momentResult').hide();
            $('#momentEmpty').show();

            try {
                const params = new URLSearchParams({
                    action: 'at-moment',
                    product_id: productId,
                    at_moment: datetime.replace('T', ' ')
                });

                console.log('Fetching:', API_URL + '?' + params.toString());

                const res = await fetch(`${API_URL}?${params}`, { credentials: 'same-origin' });
                const body = await res.json();

                console.log('Response:', body);

                if (!res.ok || !body.success) {
                    alert(body.message || 'Lỗi tải dữ liệu');
                    return;
                }

                const data = body;
                $('#momentSKU').text(escapeHtml(data.product.sku));
                $('#momentName').text(escapeHtml(data.product.name));
                $('#momentTime').text(formatDate(data.at_moment));
                $('#momentCurrent').text(data.current_qty);
                $('#momentImported').text(data.imported_after);
                $('#momentExported').text(data.exported_after);
                $('#momentAtThatTime').text(data.stock_at_moment);

                $('#momentResult').show();
                $('#momentEmpty').hide();
            } catch (e) {
                console.error('Error:', e);
                alert('Lỗi: ' + e.message);
            } finally {
                $('#momentLoader').hide();
            }
        });

        // ========== TAB 1: WARNING PRODUCTS ==========
        $('#btnLoadWarning').click(async function() {
            const threshold = parseInt($('#warningThreshold').val()) || 5;
            const search = $('#warningSearch').val();

            $('#warningLoader').show();
            $('#warningTable').hide();

            try {
                const params = new URLSearchParams({
                    action: 'list',
                    threshold: threshold,
                    q: search
                });

                const res = await fetch(`${API_URL}?${params}`, { credentials: 'same-origin' });
                const body = await res.json();

                if (!res.ok || !body.success) {
                    alert(body.message || 'Lỗi tải dữ liệu');
                    return;
                }

                const rows = body.products.map(p => {
                    const riskCss = p.quantity === 0 ? 'risk-critical' : 'risk-warning';
                    const riskText = p.quantity === 0 ? '🔴 HẾT HÀNG' : '🟡 SẮP HẾT';
                    return `
                        <tr>
                            <td><code>${escapeHtml(p.sku)}</code></td>
                            <td>${escapeHtml(p.name)}</td>
                            <td>${escapeHtml(p.category)}</td>
                            <td style="text-align: right; font-weight: bold;">${p.quantity}</td>
                            <td><span class="${riskCss}">${riskText}</span></td>
                            <td>${escapeHtml(p.supplier)}</td>
                            <td style="text-align: right;">${formatMoney(p.sale_price)}</td>
                        </tr>
                    `;
                });

                $('#warningBody').html(rows.length > 0 ? rows.join('') : '<tr><td colspan="7" style="text-align: center;">Không có sản phẩm nào</td></tr>');
            } catch (e) {
                alert('Lỗi: ' + e.message);
            } finally {
                $('#warningLoader').hide();
                $('#warningTable').show();
            }
        });

        // ========== TAB 2: HISTORY ==========
        // Load sản phẩm vào dropdown
        async function loadAllProducts() {
            console.log('loadAllProducts() called');
            try {
                const params = new URLSearchParams({
                    action: 'products'
                });

                console.log('Fetching products from:', API_URL + '?' + params.toString());

                const res = await fetch(`${API_URL}?${params}`, { credentials: 'same-origin' });
                const body = await res.json();

                console.log('Products response:', body);

                if (!res.ok || !body.success) {
                    console.error('API error:', body.message);
                    alert('Lỗi API: ' + (body.message || 'Unknown error'));
                    return;
                }

                const html = body.products.map(p => 
                    `<option value="${p.id}">${escapeHtml(p.sku)} - ${escapeHtml(p.name)}</option>`
                ).join('');

                console.log('Generated options HTML:', html);

                $('#momentProduct').append(html);
                $('#historyProduct').append(html);

                console.log('Products loaded successfully. Count:', body.products.length);
                alert('Đã load ' + body.products.length + ' sản phẩm');
            } catch(e) {
                console.error('loadAllProducts error:', e);
                alert('Lỗi load sản phẩm: ' + e.message);
            }
        }

        // Tải lịch sử cho sản phẩm đã chọn
        $('#btnLoadHistory').click(async function() {
            const productId = $('#historyProduct').val();
            if (!productId) {
                alert('Vui lòng chọn sản phẩm');
                return;
            }

            const from = $('#historyFrom').val();
            const to = $('#historyTo').val();

            $('#historyLoader').show();
            $('#historyResult').hide();
            $('#historyEmpty').show();

            try {
                const params = new URLSearchParams({
                    action: 'history',
                    product_id: productId,
                    from: from,
                    to: to
                });

                const res = await fetch(`${API_URL}?${params}`, { credentials: 'same-origin' });
                const body = await res.json();

                if (!res.ok || !body.success) {
                    alert(body.message || 'Lỗi tải dữ liệu');
                    return;
                }

                const data = body.data;

                // Update summary
                $('#histSummaryCurrentQty').text(data.product.current_quantity);
                $('#histSummaryImport').text(data.summary.total_import);
                $('#histSummaryExport').text(data.summary.total_export);
                $('#histSummaryNet').text(data.summary.net);

                // Imports
                const importRows = data.imports.map(i => `
                    <tr>
                        <td>${formatDate(i.created_at)}</td>
                        <td style="text-align: right; font-weight: bold; color: #27ae60;">+${i.quantity}</td>
                        <td><code>${escapeHtml(i.ref_code)}</code></td>
                        <td>${escapeHtml(i.supplier)}</td>
                    </tr>
                `).join('');
                $('#importBody').html(importRows || '<tr><td colspan="4" style="text-align: center;">Không có</td></tr>');

                // Exports
                const exportRows = data.exports.map(e => `
                    <tr>
                        <td>${formatDate(e.created_at)}</td>
                        <td style="text-align: right; font-weight: bold; color: #e74c3c;">-${e.quantity}</td>
                        <td><code>${escapeHtml(e.ref_code)}</code></td>
                        <td>${escapeHtml(e.customer)}</td>
                    </tr>
                `).join('');
                $('#exportBody').html(exportRows || '<tr><td colspan="4" style="text-align: center;">Không có</td></tr>');

                $('#historyResult').show();
                $('#historyEmpty').hide();
            } catch (e) {
                alert('Lỗi: ' + e.message);
            } finally {
                $('#historyLoader').hide();
            }
        });

        // Helpers
        function escapeHtml(s) {
            const div = document.createElement('div');
            div.textContent = s;
            return div.innerHTML;
        }

        function formatMoney(n) {
            return (Number(n) || 0).toLocaleString('vi-VN') + '₫';
        }

        function formatDate(str) {
            if (!str) return '-';
            const d = new Date(str + ' UTC');
            return d.toLocaleDateString('vi-VN') + ' ' + d.toLocaleTimeString('vi-VN');
        }

        // Init
        $(document).ready(function() {
            console.log('Document ready. Calling loadAllProducts()');
            loadAllProducts();
        });
    </script>
</body>
</html>
