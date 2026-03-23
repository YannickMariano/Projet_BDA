<?php
// register.php — Création de compte utilisateur (rôle = 'user' uniquement)
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_complet = trim($_POST['nom_complet'] ?? '');
    $username    = trim($_POST['username']    ?? '');
    $password    = trim($_POST['password']    ?? '');
    $confirm     = trim($_POST['confirm']     ?? '');

    // Validations
    if (!$nom_complet || !$username || !$password || !$confirm) {
        $error = "Veuillez remplir tous les champs.";
    } elseif (strlen($username) < 3) {
        $error = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = "Le nom d'utilisateur ne peut contenir que des lettres, chiffres et underscores.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        $pdo = getConnection();

        // Vérifier si le username existe déjà
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE username = :u");
        $stmt->execute([':u' => $username]);
        if ($stmt->fetch()) {
            $error = "Ce nom d'utilisateur est déjà pris. Choisissez-en un autre.";
        } else {
            // Insertion — rôle forcé à 'user', jamais 'admin'
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare(
                "INSERT INTO utilisateurs (username, password, role, nom_complet)
                 VALUES (:u, :p, 'user', :n)"
            );
            $stmt->execute([':u' => $username, ':p' => $hash, ':n' => $nom_complet]);
            $success = $username;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S'enregistrer — BanqueApp</title>
    <style>
        :root {
            --primary:   #1a3c5e;
            --secondary: #2980b9;
            --accent:    #27ae60;
            --danger:    #e74c3c;
            --border:    #dce3ed;
            --light:     #f0f4fb;
        }
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a5276 0%, #27ae60 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .wrapper {
            width: 100%;
            max-width: 440px;
            padding: 24px;
        }

        /* Brand */
        .brand { text-align:center; margin-bottom:28px; color:#fff; }
        .brand .icon { font-size:3.2rem; display:block; margin-bottom:8px; filter:drop-shadow(0 4px 12px rgba(0,0,0,.3)); }
        .brand h1 { font-size:1.6rem; font-weight:700; }
        .brand p  { font-size:.9rem; opacity:.85; margin-top:4px; }

        /* Card */
        .card {
            background: #fff;
            border-radius: 18px;
            padding: 34px 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,.22);
        }
        .card h2 {
            font-size: 1.2rem;
            color: var(--primary);
            margin-bottom: 22px;
            text-align: center;
            font-weight: 700;
        }

        /* Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: .92rem;
            margin-bottom: 18px;
            font-weight: 500;
        }
        .alert-error   { background:#fdecea; border-left:4px solid var(--danger); color:#721c24; }
        .alert-success { background:#d4edda; border-left:4px solid var(--accent); color:#155724; }

        /* Form */
        .form-group { margin-bottom: 16px; }
        label {
            display: block;
            font-size: .8rem; font-weight:700;
            color: #7f8c8d;
            text-transform: uppercase; letter-spacing:.5px;
            margin-bottom: 6px;
        }
        .input-wrap { position: relative; }
        .input-wrap .ico {
            position: absolute; left:13px; top:50%;
            transform: translateY(-50%);
            font-size: 1.05rem; pointer-events:none;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 11px 14px 11px 42px;
            border: 1.5px solid var(--border);
            border-radius: 9px;
            font-size: .96rem; color:#2c3e50;
            background: #fafcff;
            transition: border-color .2s, box-shadow .2s;
        }
        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(39,174,96,.13);
        }
        .hint { font-size:.76rem; color:var(--muted,#aaa); margin-top:4px; }

        /* Password strength */
        .strength-bar {
            height: 4px; border-radius: 4px;
            background: #eee; margin-top: 6px; overflow:hidden;
        }
        .strength-fill {
            height: 100%; border-radius:4px;
            width: 0%; transition: width .3s, background .3s;
        }
        .strength-label { font-size:.75rem; margin-top:3px; }

        /* Buttons */
        .btn-register {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #1a5276, var(--accent));
            color: #fff; border: none; border-radius: 9px;
            font-size: 1rem; font-weight: 700; cursor: pointer;
            letter-spacing: .4px;
            transition: filter .2s, transform .1s;
            margin-top: 8px;
        }
        .btn-register:hover  { filter:brightness(1.08); transform:translateY(-1px); }
        .btn-register:active { transform:translateY(0); }

        .back-link {
            display: block; text-align:center;
            margin-top: 16px; font-size:.9rem;
            color: var(--primary); text-decoration:none;
            padding: 10px;
            border-radius: 8px;
            border: 1.5px solid var(--border);
            transition: background .15s;
        }
        .back-link:hover { background: var(--light); }

        /* Success state */
        .success-box { text-align:center; padding:10px 0; }
        .success-box .check { font-size:3.5rem; display:block; margin-bottom:12px; }
        .success-box h3 { color:var(--accent); font-size:1.25rem; margin-bottom:8px; }
        .success-box p  { color:#555; font-size:.94rem; margin-bottom:20px; line-height:1.5; }
        .btn-go {
            display:inline-block; padding:12px 32px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color:#fff; border-radius:9px; text-decoration:none;
            font-weight:700; font-size:1rem;
            transition: filter .2s;
        }
        .btn-go:hover { filter:brightness(1.1); }

        /* Footer */
        .footer { text-align:center; margin-top:18px; color:rgba(255,255,255,.6); font-size:.82rem; }
    </style>
</head>
<body>

<div class="wrapper">

    <!-- Brand -->
    <div class="brand">
        <!-- <span class="icon">🏦</span> -->
        <h1>BanqueApp</h1>
        <p>Créer un nouveau compte</p>
    </div>

    <div class="card">

    <?php if ($success): ?>
        <!-- ===== SUCCÈS ===== -->
        <div class="success-box">
            <!-- <span class="check">✅</span> -->
            <h3>Compte créé avec succès !</h3>
            <p>
                Bienvenue <strong><?= htmlspecialchars($success) ?></strong> !<br>
                Votre compte a bien été enregistré.<br>
                Vous pouvez maintenant vous connecter.
            </p>
            <a href="login.php" class="btn-go">Se connecter →</a>
        </div>

    <?php else: ?>
        <!-- ===== FORMULAIRE ===== -->
        <h2>Créer un compte</h2>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Nom complet</label>
                <div class="input-wrap">
                    <!-- <span class="ico">🧑</span> -->
                    <input type="text" name="nom_complet" required
                           placeholder="Ex : Jean Dupont"
                           value="<?= htmlspecialchars($_POST['nom_complet'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Nom d'utilisateur</label>
                <div class="input-wrap">
                    <!-- <span class="ico">👤</span> -->
                    <input type="text" name="username" required
                           placeholder="Ex : jean_dupont"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                <div class="hint">Lettres, chiffres et _ uniquement (min. 3 caractères)</div>
            </div>

            <div class="form-group">
                <label>Mot de passe</label>
                <div class="input-wrap">
                    <!-- <span class="ico">🔑</span> -->
                    <input type="password" name="password" id="pwd" required
                           placeholder="Minimum 6 caractères"
                           oninput="checkStrength(this.value)">
                </div>
                <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                <div class="strength-label" id="strengthLabel"></div>
            </div>

            <div class="form-group">
                <label>Confirmer le mot de passe</label>
                <div class="input-wrap">
                    <!-- <span class="ico">🔒</span> -->
                    <input type="password" name="confirm" id="confirm" required
                           placeholder="Répétez le mot de passe"
                           oninput="checkMatch()">
                </div>
                <div class="hint" id="matchHint"></div>
            </div>

            <button type="submit" class="btn-register">Créer mon compte</button>
        </form>

        <a href="login.php" class="back-link">← Retour à la connexion</a>

    <?php endif; ?>

    </div><!-- /card -->

    <div class="footer">Système sécurisé — Rôle utilisateur attribué automatiquement</div>
</div>

<script>
function checkStrength(val) {
    const fill  = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^a-zA-Z0-9]/.test(val)) score++;

    const levels = [
        { w:'0%',   bg:'#eee',          txt:'' },
        { w:'25%',  bg:'#e74c3c',       txt:'⚠️ Très faible' },
        { w:'50%',  bg:'#f39c12',       txt:'🟡 Faible' },
        { w:'75%',  bg:'#2980b9',       txt:'🔵 Moyen' },
        { w:'90%',  bg:'#27ae60',       txt:'🟢 Fort' },
        { w:'100%', bg:'#1a8a4a',       txt:'✅ Très fort' },
    ];
    const l = levels[Math.min(score, 5)];
    fill.style.width      = l.w;
    fill.style.background = l.bg;
    label.textContent     = l.txt;
    label.style.color     = l.bg;
}

function checkMatch() {
    const pwd     = document.getElementById('pwd').value;
    const confirm = document.getElementById('confirm').value;
    const hint    = document.getElementById('matchHint');
    if (!confirm) { hint.textContent = ''; return; }
    if (pwd === confirm) {
        hint.textContent = 'Les mots de passe correspondent';
        hint.style.color = '#27ae60';
    } else {
        hint.textContent = 'Les mots de passe ne correspondent pas';
        hint.style.color = '#e74c3c';
    }
}
</script>

</body>
</html>
