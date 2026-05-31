<?php
// login.php - Login Page
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            log_activity($conn, $user['id'], 'Login', 'User logged in successfully.');
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — ClearanceMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@700;800&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        /* ── Credential chips ── */
        .cred-group-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #94a3b8;
            margin: 10px 0 5px;
        }
        .cred-chips { display: flex; flex-wrap: wrap; gap: 6px; }
        .cred-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-radius: 8px;
            border: 1.5px solid transparent;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all .15s ease;
            user-select: none;
        }
        .cred-chip:hover { transform: translateY(-1px); box-shadow: 0 3px 10px rgba(0,0,0,.12); }
        .cred-chip:active { transform: translateY(0); }
        .cred-chip.chip-admin {
            background: #fef2f2; border-color: #fca5a5; color: #dc2626;
        }
        .cred-chip.chip-admin:hover { background: #dc2626; color: #fff; border-color: #dc2626; }
        .cred-chip.chip-student {
            background: #f0fdf4; border-color: #86efac; color: #16a34a;
        }
        .cred-chip.chip-student:hover { background: #16a34a; color: #fff; border-color: #16a34a; }
        .cred-chip.chip-officer {
            background: #fffbeb; border-color: #fcd34d; color: #d97706;
        }
        .cred-chip.chip-officer:hover { background: #d97706; color: #fff; border-color: #d97706; }
        .cred-chip i { font-size: 13px; }
        .chip-filled { animation: chipFill .2s ease forwards; }
        .demo-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 14px 14px;
            margin-top: 14px;
        }
        .demo-box-title {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        /* Flash animation on autofill */
        @keyframes inputFlash {
            0%   { background: #dbeafe; }
            100% { background: #fff; }
        }
        .input-filled { animation: inputFlash .4s ease; }
    </style>
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <!-- Logo -->
        <div class="login-logo">
            <div class="logo-icon"><i class="bi bi-mortarboard-fill"></i></div>
            <h1>ClearanceMS</h1>
            <p>Student Clearance Processing System</p>
        </div>

        <!-- Error alert -->
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Login form -->
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-person text-muted"></i>
                    </span>
                    <input type="text" name="username" id="usernameInput"
                           class="form-control border-start-0"
                           placeholder="Enter username or email"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-lock text-muted"></i>
                    </span>
                    <input type="password" name="password" id="passwordInput"
                           class="form-control border-start-0 border-end-0"
                           placeholder="Enter password" required>
                    <button type="button" class="input-group-text bg-light border-start-0" onclick="togglePwd()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-600">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <!-- ── DEMO CREDENTIALS ── -->
        <div class="demo-box">
            <div class="demo-box-title">
                <i class="bi bi-lightning-charge-fill text-warning"></i>
                Quick Login — click any account to auto-fill
            </div>

            <div class="cred-group-label">Administrator</div>
            <div class="cred-chips">
                <div class="cred-chip chip-admin" onclick="fillCreds('admin','Admin@2025')">
                    <i class="bi bi-shield-lock-fill"></i> admin
                </div>
            </div>

            <div class="cred-group-label">Students</div>
            <div class="cred-chips">
                <div class="cred-chip chip-student" onclick="fillCreds('john_dela_cruz','Student@2025')">
                    <i class="bi bi-person-fill"></i> john_dela_cruz
                </div>
                <div class="cred-chip chip-student" onclick="fillCreds('maria_santos','Student@2025')">
                    <i class="bi bi-person-fill"></i> maria_santos
                </div>
                <div class="cred-chip chip-student" onclick="fillCreds('carlos_garcia','Student@2025')">
                    <i class="bi bi-person-fill"></i> carlos_garcia
                </div>
                <div class="cred-chip chip-student" onclick="fillCreds('anna_reyes','Student@2025')">
                    <i class="bi bi-person-fill"></i> anna_reyes
                </div>
                <div class="cred-chip chip-student" onclick="fillCreds('pedro_bautista','Student@2025')">
                    <i class="bi bi-person-fill"></i> pedro_bautista
                </div>
                <div class="cred-chip chip-student" onclick="fillCreds('rosa_mendoza','Student@2025')">
                    <i class="bi bi-person-fill"></i> rosa_mendoza
                </div>
                <div class="cred-chip chip-student" onclick="fillCreds('miguel_torres','Student@2025')">
                    <i class="bi bi-person-fill"></i> miguel_torres
                </div>
                <div class="cred-chip chip-student" onclick="fillCreds('elena_villanueva','Student@2025')">
                    <i class="bi bi-person-fill"></i> elena_villanueva
                </div>
            </div>

            <div class="cred-group-label">Officers</div>
            <div class="cred-chips">
                <div class="cred-chip chip-officer" onclick="fillCreds('library_officer','Officer@2025')">
                    <i class="bi bi-book-fill"></i> library_officer
                </div>
                <div class="cred-chip chip-officer" onclick="fillCreds('registrar_officer','Officer@2025')">
                    <i class="bi bi-file-earmark-person-fill"></i> registrar_officer
                </div>
                <div class="cred-chip chip-officer" onclick="fillCreds('finance_officer','Officer@2025')">
                    <i class="bi bi-cash-coin"></i> finance_officer
                </div>
                <div class="cred-chip chip-officer" onclick="fillCreds('guidance_officer','Officer@2025')">
                    <i class="bi bi-heart-pulse-fill"></i> guidance_officer
                </div>
                <div class="cred-chip chip-officer" onclick="fillCreds('it_officer','Officer@2025')">
                    <i class="bi bi-pc-display-horizontal"></i> it_officer
                </div>
                <div class="cred-chip chip-officer" onclick="fillCreds('clinic_officer','Officer@2025')">
                    <i class="bi bi-hospital-fill"></i> clinic_officer
                </div>
                <div class="cred-chip chip-officer" onclick="fillCreds('sao_officer','Officer@2025')">
                    <i class="bi bi-person-badge-fill"></i> sao_officer
                </div>
            </div>

            <div class="mt-2" style="font-size:10.5px;color:#94a3b8;border-top:1px solid #e2e8f0;padding-top:8px;">
                <i class="bi bi-info-circle me-1"></i>
                Passwords: <code>Admin@2025</code> &nbsp;·&nbsp; <code>Officer@2025</code> &nbsp;·&nbsp; <code>Student@2025</code>
            </div>
        </div>

        <div class="login-footer-credits">
            <i class="bi bi-code-slash me-1"></i>
            Developed by <strong>Gideon Agtas</strong>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function fillCreds(username, password) {
    const uInp = document.getElementById('usernameInput');
    const pInp = document.getElementById('passwordInput');

    uInp.value = username;
    pInp.value = password;

    // Flash the inputs so user sees them fill
    uInp.classList.remove('input-filled');
    pInp.classList.remove('input-filled');
    void uInp.offsetWidth; // reflow
    uInp.classList.add('input-filled');
    pInp.classList.add('input-filled');
    setTimeout(() => {
        uInp.classList.remove('input-filled');
        pInp.classList.remove('input-filled');
    }, 500);

    // Auto submit after a short delay so user sees what was filled
    setTimeout(() => {
        uInp.closest('form').submit();
    }, 600);
}

function togglePwd() {
    const inp = document.getElementById('passwordInput');
    const icon = document.getElementById('eyeIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        inp.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

// Auto-dismiss alerts
setTimeout(() => {
    document.querySelectorAll('.alert-dismissible').forEach(a => {
        bootstrap.Alert.getOrCreateInstance(a)?.close();
    });
}, 3500);
</script>
</body>
</html>
