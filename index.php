<?php
require_once 'config/session.php';
require_once 'config/db.php';
require_once 'classes/Pengguna.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    header('Location: pages/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userClass = new Pengguna($conn);
    $result = $userClass->login(
        trim($_POST['email']    ?? ''),
        trim($_POST['password'] ?? '')
    );
    if ($result['success']) {
        header('Location: pages/dashboard.php');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CMLE — Cultural Marketing & Localization Engine</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  :root {
    --bg-dark:   #0d1117;
    --bg-card:   #161b22;
    --bg-input:  #1c2128;
    --accent:    #2f81f7;
    --accent-glow: rgba(47,129,247,0.15);
    --text-main: #e6edf3;
    --text-muted:#8b949e;
    --border:    #30363d;
    --success:   #3fb950;
    --warning:   #d29922;
    --danger:    #f85149;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    background: var(--bg-dark);
    color: var(--text-main);
    font-family: 'Segoe UI', system-ui, sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background-image: radial-gradient(ellipse at 50% 0%, rgba(47,129,247,0.08) 0%, transparent 60%);
  }
  .login-wrap {
    width: 100%;
    max-width: 440px;
    padding: 2rem 1rem;
  }
  .logo-icon {
    font-size: 2.8rem;
    color: var(--accent);
    display: block;
    text-align: center;
    margin-bottom: .5rem;
  }
  .app-title {
    text-align: center;
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: -.5px;
    margin-bottom: .25rem;
  }
  .app-sub {
    text-align: center;
    color: var(--text-muted);
    font-size: .85rem;
    margin-bottom: 2rem;
  }
  .card-login {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 2rem;
  }
  .form-label { color: var(--text-muted); font-size: .85rem; margin-bottom: .4rem; }
  .form-control {
    background: var(--bg-input);
    border: 1px solid var(--border);
    color: var(--text-main);
    border-radius: 8px;
    padding: .65rem 1rem;
    font-size: .95rem;
    transition: border-color .2s;
  }
  .form-control:focus {
    background: var(--bg-input);
    border-color: var(--accent);
    color: var(--text-main);
    box-shadow: 0 0 0 3px var(--accent-glow);
  }
  .btn-login {
    background: var(--accent);
    border: none;
    color: #fff;
    width: 100%;
    padding: .7rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: .95rem;
    margin-top: .5rem;
    cursor: pointer;
    transition: opacity .2s;
  }
  .btn-login:hover { opacity: .85; }
  .alert-err {
    background: rgba(248,81,73,.1);
    border: 1px solid rgba(248,81,73,.3);
    color: #f85149;
    border-radius: 8px;
    padding: .6rem 1rem;
    font-size: .875rem;
    margin-bottom: 1rem;
  }
  .demo-hint {
    margin-top: 1.5rem;
    background: var(--bg-input);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 1rem;
    font-size: .8rem;
    color: var(--text-muted);
  }
  .demo-hint strong { color: var(--text-main); }
  .demo-hint code {
    background: rgba(255,255,255,.06);
    border-radius: 4px;
    padding: .1rem .35rem;
    font-family: monospace;
    color: var(--accent);
  }
</style>
</head>
<body>
<div class="login-wrap">
  <i class="bi bi-globe2 logo-icon"></i>
  <div class="app-title">CMLE</div>
  <div class="app-sub">Cultural Marketing &amp; Localization Engine</div>

  <div class="card-login">
    <?php if ($error): ?>
    <div class="alert-err"><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control"
               placeholder="email@cmle.id"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="mb-4">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control"
               placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-login">
        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke CMLE
      </button>
    </form>
  </div>

  <div class="demo-hint">
    <strong>Demo Credentials:</strong><br>
    Analyst &nbsp;→ <code>rizqullah@cmle.id</code> / <code>analyst123</code><br>
    Brand Mgr → <code>brand@cmle.id</code> / <code>brand123</code><br>
    Admin &nbsp;&nbsp;&nbsp;→ <code>admin@cmle.id</code> / <code>admin123</code>
  </div>
</div>
</body>
</html>
