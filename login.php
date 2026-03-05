<?php
// login.php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $pdo  = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE username = :u");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['role']       = $user['role'];
            $_SESSION['nom_complet']= $user['nom_complet'];
            header('Location: index.php');
            exit;
        } else {
            $error = "Nom d'utilisateur ou mot de passe incorrect.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏦 Connexion — Banque</title>
    <style>
        :root {
            --primary:  #1a3c5e;
            --secondary:#2980b9;
            --accent:   #27ae60;
            --danger:   #e74c3c;
            --border:   #dce3ed;
            --light:    #f0f4fb;
        }
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a3c5e 0%, #2980b9 60%, #1abc9c 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 24px;
        }

        /* Logo / Brand */
        .brand {
            text-align: center;
            margin-bottom: 32px;
            color: #fff;
        }
        .brand .icon {
            font-size: 3.5rem;
            display: block;
            margin-bottom: 10px;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,.3));
        }
        .brand h1 { font-size: 1.7rem; font-weight: 700; letter-spacing: .5px; }
        .brand p  { font-size: .92rem; opacity: .85; margin-top: 4px; }

        /* Card */
        .card {
            background: #fff;
            border-radius: 18px;
            padding: 36px 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
        }
        .card h2 {
            font-size: 1.25rem;
            color: var(--primary);
            margin-bottom: 24px;
            text-align: center;
            font-weight: 700;
        }

        /* Form */
        .form-group { margin-bottom: 18px; }
        label {
            display: block;
            font-size: .82rem;
            font-weight: 700;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 6px;
        }
        .input-wrap { position: relative; }
        .input-wrap .ico {
            position: absolute;
            left: 13px; top: 50%;
            transform: translateY(-50%);
            font-size: 1.1rem;
            pointer-events: none;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 11px 14px 11px 42px;
            border: 1.5px solid var(--border);
            border-radius: 9px;
            font-size: .97rem;
            background: #fafcff;
            color: #2c3e50;
            transition: border-color .2s, box-shadow .2s;
        }
        input:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(41,128,185,.13);
        }

        /* Error */
        .alert-error {
            background: #fdecea;
            border-left: 4px solid var(--danger);
            color: #721c24;
            padding: 11px 14px;
            border-radius: 8px;
            font-size: .92rem;
            margin-bottom: 18px;
        }

        /* Submit */
        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            border: none;
            border-radius: 9px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: .5px;
            transition: filter .2s, transform .1s;
            margin-top: 6px;
        }
        .btn-login:hover  { filter: brightness(1.1); transform: translateY(-1px); }
        .btn-login:active { transform: translateY(0); }

        /* Comptes démo */
        .demo-accounts {
            margin-top: 24px;
            background: var(--light);
            border-radius: 10px;
            padding: 14px 18px;
            font-size: .84rem;
            color: #555;
        }
        .demo-accounts strong {
            display: block;
            color: var(--primary);
            margin-bottom: 8px;
            font-size: .85rem;
        }
        .demo-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px dashed #ddd;
        }
        .demo-row:last-child { border-bottom: none; }
        .badge-role {
            font-size: .72rem;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 700;
        }
        .badge-admin { background: #fdecea; color: #c0392b; }
        .badge-user  { background: #d4edda; color: #155724; }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: rgba(255,255,255,.65);
            font-size: .82rem;
        }
    </style>
</head>
<body>

<div class="login-wrapper">

    <!-- Brand -->
    <div class="brand">
        <span class="icon">🏦</span>
        <h1>BanqueApp</h1>
        <p>Gestion & Audit des Comptes Bancaires</p>
    </div>

    <!-- Card -->
    <div class="card">
        <h2>🔐 Connexion</h2>

        <?php if ($error): ?>
        <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nom d'utilisateur</label>
                <div class="input-wrap">
                    <span class="ico">👤</span>
                    <input type="text" name="username" required
                           placeholder="Entrez votre identifiant"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Mot de passe</label>
                <div class="input-wrap">
                    <span class="ico">🔑</span>
                    <input type="password" name="password" required
                           placeholder="Entrez votre mot de passe">
                </div>
            </div>

            <button type="submit" class="btn-login">Se connecter →</button>

            <div style="margin-top:14px; text-align:center;">
                <span style="color:#7f8c8d; font-size:.88rem;">Pas encore de compte ?</span>
                <a href="register.php" style="
                    display:block; margin-top:10px; width:100%; padding:12px;
                    background:#fff; color:var(--accent);
                    border:2px solid var(--accent);
                    border-radius:9px; font-size:1rem; font-weight:700;
                    text-decoration:none; text-align:center; letter-spacing:.4px;
                    transition:background .2s, color .2s;
                    box-sizing:border-box;
                "
                onmouseover="this.style.background='#27ae60';this.style.color='#fff'"
                onmouseout="this.style.background='#fff';this.style.color='#27ae60'"
                >✏️ S'enregistrer</a>
            </div>
        </form>

        <!-- Comptes de démonstration
        <div class="demo-accounts">
            <strong>🧪 Comptes de test (mot de passe : password)</strong>
            <div class="demo-row">
                <span>👤 <strong>admin</strong></span>
                <span class="badge-role badge-admin">ADMIN</span>
            </div>
            <div class="demo-row">
                <span>👤 <strong>alice</strong></span>
                <span class="badge-role badge-user">USER</span>
            </div>
            <div class="demo-row">
                <span>👤 <strong>bob</strong></span>
                <span class="badge-role badge-user">USER</span>
            </div>
            <div class="demo-row">
                <span>👤 <strong>clara</strong></span>
                <span class="badge-role badge-user">USER</span>
            </div>
        </div>
    </div> -->

    <div class="login-footer">
        Système sécurisé — Accès restreint aux utilisateurs autorisés
    </div>
</div>

</body>
</html>
