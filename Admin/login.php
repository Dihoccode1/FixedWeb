<?php
require_once __DIR__ . '/../includes/common.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['adm_user'] ?? '');
    $password = $_POST['adm_pass'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Tên đăng nhập và mật khẩu không được để trống.';
    } else {
        $admin = db_fetch_one_prepared('SELECT * FROM admin_users WHERE username = ? LIMIT 1', 's', [$username]);
        if (!$admin) {
            $error = 'Sai tài khoản hoặc mật khẩu quản trị.';
        } elseif ($admin['status'] !== 'active') {
            $error = 'Tài khoản quản trị đang bị khóa.';
        } else {
            $hash = $admin['password_hash'];
            $checked = false;
            if (password_verify($password, $hash)) {
                $checked = true;
            } elseif (hash('sha256', $password) === $hash) {
                $checked = true;
            }
            if (!$checked) {
                $error = 'Sai tài khoản hoặc mật khẩu quản trị.';
            } else {
                $_SESSION['admin'] = [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'name' => $admin['name'],
                    'role' => $admin['role'],
                ];
                redirect(app_url('Admin/index.php'));
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Đăng nhập quản trị</title>
    <style>
      :root {
        --bg: #0f172a;
        --card: #111827;
        --text: #e5e7eb;
        --err: #ef4444;
      }
      * {
        box-sizing: border-box;
      }
      body {
        margin: 0;
        min-height: 100dvh;
        display: grid;
        place-items: center;
        background: radial-gradient(
          60% 60% at 50% 40%,
          #1f2937 0%,
          var(--bg) 60%
        );
        font: 14px/1.5 system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
        color: var(--text);
      }
      .card {
        width: min(420px, 92vw);
        background: linear-gradient(#0007, #0003), var(--card);
        border: 1px solid #1f2937;
        border-radius: 20px;
        padding: 28px;
        box-shadow: 0 10px 30px #0007;
        backdrop-filter: saturate(130%) blur(6px);
      }
      h1 {
        margin: 0 0 4px;
        font-size: 24px;
      }
      p.sub {
        margin: 0 0 18px;
        color: #9ca3af;
      }
      label {
        display: block;
        margin: 14px 0 6px;
        color: #cbd5e1;
        font-weight: 600;
      }
      input {
        width: 100%;
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid #27344a;
        background: #0b1220;
        color: #f8fafc;
        outline: none;
      }
      input:focus {
        border-color: #334155;
        box-shadow: 0 0 0 3px #22c55e33;
      }
      .row {
        display: flex;
        gap: 12px;
        align-items: center;
        justify-content: space-between;
        margin-top: 14px;
      }
      .btn {
        appearance: none;
        border: 0;
        border-radius: 12px;
        padding: 12px 16px;
        font-weight: 700;
        cursor: pointer;
        background: linear-gradient(180deg, #22c55e, #16a34a);
        color: #06250f;
        width: 100%;
      }
      .btn:hover {
        filter: brightness(1.02);
      }
      .err {
        color: var(--err);
        font-weight: 600;
        min-height: 1.25em;
        margin-top: 8px;
      }
      .brand {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
      }
      .brand span {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: inline-grid;
        place-items: center;
        font-weight: 900;
        background: #0ea5e9;
        color: #001018;
      }
    </style>
  </head>
  <body>
    <main class="card">
      <div class="brand">
        <span>AD</span>
        <div>
          <h1>Đăng nhập quản trị</h1>
          <p class="sub">Khu vực dành riêng cho quản lý cửa hàng</p>
        </div>
      </div>

      <form method="post" autocomplete="off" spellcheck="false" novalidate>
        <label for="username">Tài khoản</label>
        <input id="username" name="adm_user" type="text" autocomplete="off" required />

        <label for="password">Mật khẩu</label>
        <input id="password" name="adm_pass" type="password" autocomplete="new-password" required />

        <div class="row">
          <button class="btn" type="submit">Đăng nhập</button>
        </div>
        <div class="err"><?php echo h($error); ?></div>
      </form>
    </main>
  </body>
</html>

