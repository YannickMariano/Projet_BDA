<?php
// user.php — Tableau de bord Utilisateur (CRUD sur ses propres comptes)
// Inclus via index.php, session déjà démarrée

require_once 'config.php';
$pdo      = getConnection();
$userId   = $_SESSION['user_id'];
$username = $_SESSION['username'];

$message     = '';
$messageType = '';

// ============================================================
// TRAITEMENT DES ACTIONS CRUD
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- AJOUTER ---
    if ($_POST['action'] === 'ajouter') {
        $nom   = trim($_POST['nom_client']);
        $solde = floatval($_POST['solde']);
        if ($nom !== '') {
            $stmt = $pdo->prepare(
                "INSERT INTO compte (nom_client, solde, user_id) VALUES (:nom, :solde, :uid)"
            );
            $stmt->execute([':nom' => $nom, ':solde' => $solde, ':uid' => $userId]);
            $message     = "Compte du client <strong>$nom</strong> créé avec succès.";
            $messageType = 'success';
        } else {
            $message     = "Le nom du client est obligatoire.";
            $messageType = 'warning';
        }
    }

    // --- MODIFIER (vérifie que le compte appartient bien à cet user) ---
    elseif ($_POST['action'] === 'modifier') {
        $num   = intval($_POST['num_compte']);
        $nom   = trim($_POST['nom_client']);
        $solde = floatval($_POST['solde']);
        $stmt  = $pdo->prepare(
            "UPDATE compte SET nom_client = :nom, solde = :solde
             WHERE num_compte = :num AND user_id = :uid"
        );
        $stmt->execute([':nom'=>$nom,':solde'=>$solde,':num'=>$num,':uid'=>$userId]);
        if ($stmt->rowCount() > 0) {
            $message     = "Compte N°<strong>$num</strong> modifié.";
            $messageType = 'info';
        } else {
            $message     = "Opération non autorisée.";
            $messageType = 'danger';
        }
    }

    // --- SUPPRIMER (vérifie ownership) ---
    elseif ($_POST['action'] === 'supprimer') {
        $num  = intval($_POST['num_compte']);
        $stmt = $pdo->prepare(
            "DELETE FROM compte WHERE num_compte = :num AND user_id = :uid"
        );
        $stmt->execute([':num'=>$num,':uid'=>$userId]);
        if ($stmt->rowCount() > 0) {
            $message     = "Compte N°<strong>$num</strong> supprimé.";
            $messageType = 'danger';
        } else {
            $message     = "Opération non autorisée.";
            $messageType = 'danger';
        }
    }
}

// ============================================================
// LECTURE : comptes de CET utilisateur uniquement
// ============================================================
$stmt = $pdo->prepare("SELECT * FROM compte WHERE user_id = :uid ORDER BY num_compte");
$stmt->execute([':uid' => $userId]);
$comptes = $stmt->fetchAll();

// Solde total
$stmtTotal = $pdo->prepare("SELECT COALESCE(SUM(solde),0) AS total FROM compte WHERE user_id = :uid");
$stmtTotal->execute([':uid' => $userId]);
$soldeTotal = $stmtTotal->fetchColumn();

// Pré-remplissage formulaire modification
$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM compte WHERE num_compte = :num AND user_id = :uid");
    $stmt->execute([':num' => intval($_GET['edit']), ':uid' => $userId]);
    $editData = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Comptes</title>
    <style>
        :root {
            --primary:   #1a3c5e;
            --secondary: #2980b9;
            --accent:    #27ae60;
            --danger:    #e74c3c;
            --warning:   #f39c12;
            --light:     #f0f4fb;
            --border:    #dce3ed;
            --text:      #2c3e50;
            --muted:     #7f8c8d;
            --shadow:    0 4px 20px rgba(26,60,94,0.10);
        }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Segoe UI',Arial,sans-serif; background:var(--light); color:var(--text); min-height:100vh; }

        /* ---- Navbar ---- */
        nav {
            background: linear-gradient(135deg, #1a5276 0%, var(--secondary) 100%);
            padding: 0 32px;
            display: flex; align-items:center; justify-content:space-between;
            height: 64px;
            box-shadow: 0 3px 14px rgba(26,60,94,.25);
            position: sticky; top:0; z-index:100;
        }
        .nav-brand { color:#fff; font-size:1.15rem; font-weight:700; display:flex; align-items:center; gap:10px; }
        .nav-brand span { font-size:1.5rem; }
        .nav-right { display:flex; align-items:center; gap:16px; }
        .nav-user {
            color:rgba(255,255,255,.9); font-size:.9rem;
            background:rgba(255,255,255,.15); padding:7px 16px;
            border-radius:20px; display:flex; align-items:center; gap:8px;
        }
        .badge-user-nav {
            background:#27ae60; color:#fff;
            font-size:.72rem; font-weight:700;
            padding:2px 8px; border-radius:10px;
        }
        .btn-logout {
            background:rgba(231,76,60,.85); color:#fff;
            border:none; padding:8px 16px; border-radius:8px;
            font-size:.88rem; font-weight:600; cursor:pointer;
            text-decoration:none; display:flex; align-items:center; gap:6px;
            transition:background .2s;
        }
        .btn-logout:hover { background:#c0392b; }

        /* ---- Container ---- */
        .container { max-width:1200px; margin:32px auto; padding:0 24px; }

        /* ---- Welcome Banner ---- */
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 16px;
            padding: 28px 32px;
            color: #fff;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: var(--shadow);
        }
        .welcome-banner h2 { font-size:1.4rem; font-weight:700; margin-bottom:4px; }
        .welcome-banner p  { opacity:.85; font-size:.92rem; }
        .solde-total-block {
            background:rgba(255,255,255,.15);
            border-radius:12px; padding:16px 28px;
            text-align:center;
        }
        .solde-total-block .lbl { font-size:.78rem; opacity:.8; text-transform:uppercase; letter-spacing:.5px; }
        .solde-total-block .val { font-size:1.9rem; font-weight:800; margin-top:2px; }

        /* ---- Grid ---- */
        .grid-2 { display:grid; grid-template-columns:380px 1fr; gap:26px; align-items:start; }

        /* ---- Cards ---- */
        .card { background:#fff; border-radius:14px; box-shadow:var(--shadow); border:1px solid var(--border); overflow:hidden; }
        .card-header {
            padding:17px 24px;
            background:linear-gradient(90deg,var(--primary) 0%, #234e72 100%);
            color:#fff; font-size:1rem; font-weight:600;
            display:flex; align-items:center; gap:10px;
        }
        .card-body { padding:24px; }

        /* ---- Alerts ---- */
        .alert { padding:13px 18px; border-radius:9px; margin-bottom:22px; font-weight:500; }
        .alert-success { background:#d4edda; border-left:4px solid var(--accent);   color:#155724; }
        .alert-info    { background:#d0e8f7; border-left:4px solid var(--secondary);color:#0c5460; }
        .alert-warning { background:#fff3cd; border-left:4px solid var(--warning);  color:#856404; }
        .alert-danger  { background:#f8d7da; border-left:4px solid var(--danger);   color:#721c24; }

        /* ---- Form ---- */
        .form-group { margin-bottom:17px; }
        label {
            display:block; font-size:.8rem; font-weight:700;
            color:var(--muted); text-transform:uppercase;
            letter-spacing:.5px; margin-bottom:6px;
        }
        input[type="text"], input[type="number"] {
            width:100%; padding:10px 14px;
            border:1.5px solid var(--border); border-radius:8px;
            font-size:.96rem; color:var(--text); background:#fafcff;
            transition:border-color .2s, box-shadow .2s;
        }
        input:focus { outline:none; border-color:var(--secondary); box-shadow:0 0 0 3px rgba(41,128,185,.12); }
        .btn {
            display:inline-flex; align-items:center; gap:7px;
            padding:10px 20px; border:none; border-radius:8px;
            font-size:.93rem; font-weight:600; cursor:pointer;
            transition:filter .2s, transform .1s;
        }
        .btn:hover  { filter:brightness(1.08); transform:translateY(-1px); }
        .btn:active { transform:translateY(0); }
        .btn-primary { background:var(--secondary); color:#fff; }
        .btn-success { background:var(--accent);    color:#fff; }
        .btn-danger  { background:var(--danger);    color:#fff; }
        .btn-warning { background:var(--warning);   color:#fff; }
        .btn-sm      { padding:6px 12px; font-size:.82rem; }
        .btn-group   { display:flex; gap:8px; flex-wrap:wrap; margin-top:20px; }

        /* ---- Table ---- */
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; font-size:.92rem; }
        thead th {
            background:var(--primary); color:#fff;
            padding:12px 16px; text-align:left; font-weight:600; white-space:nowrap;
        }
        thead th:first-child { border-radius:8px 0 0 0; }
        thead th:last-child  { border-radius:0 8px 0 0; }
        tbody tr { border-bottom:1px solid var(--border); transition:background .15s; }
        tbody tr:hover { background:#edf3fb; }
        tbody td { padding:11px 16px; vertical-align:middle; }
        tbody tr:last-child { border-bottom:none; }
        .solde-pos  { color:var(--accent);   font-weight:700; }
        .solde-neg  { color:var(--danger);   font-weight:700; }
        .solde-null { color:var(--muted);    font-size:.85rem; }
        tfoot td { background:#f0f4fa; font-weight:700; padding:12px 16px; }
        tfoot .total-row td { border-top:2px solid var(--primary); }

        /* Empty */
        .empty-state { text-align:center; padding:36px; color:var(--muted); }
        .empty-state .ico { font-size:2.5rem; margin-bottom:10px; }

        /* Responsive */
        @media(max-width:900px) {
            .grid-2 { grid-template-columns:1fr; }
            .welcome-banner { flex-direction:column; align-items:flex-start; }
        }
    </style>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav>
    <div class="nav-brand"><span></span> BanqueApp</div>
    <div class="nav-right">
        <div class="nav-user">
            👤 <?= htmlspecialchars($_SESSION['nom_complet']) ?>
            <span class="badge-user-nav">CAISSIER</span>
        </div>
        <a href="logout.php" class="btn-logout">Déconnexion</a>
    </div>
</nav>

<div class="container">

    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div>
            <h2>Bienvenue, <?= htmlspecialchars($_SESSION['nom_complet']) ?> !</h2>
            <p>Espace Caissier · <?= count($comptes) ?> client<?= count($comptes)>1?'s':'' ?> enregistré<?= count($comptes)>1?'s':'' ?></p>
        </div>
        <div class="solde-total-block">
            <div class="lbl">Solde Total Clients</div>
            <div class="val"><?= number_format($soldeTotal, 2, ',', ' ') ?> Ar</div>
        </div>
    </div>

    <!-- Alert -->
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
    <?php endif; ?>

    <!-- Grid -->
    <div class="grid-2">

        <!-- ---- Formulaire ---- -->
        <div class="card">
            <div class="card-header">
                <?= $editData ? 'Modifier le compte' : 'Nouveau compte' ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action"
                           value="<?= $editData ? 'modifier' : 'ajouter' ?>">
                    <?php if ($editData): ?>
                    <input type="hidden" name="num_compte" value="<?= $editData['num_compte'] ?>">
                    <?php endif; ?>

                    <?php if ($editData): ?>
                    <div class="form-group">
                        <label>N° Compte</label>
                        <input type="text" value="#<?= $editData['num_compte'] ?>" disabled>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Nom du client</label>
                        <input type="text" name="nom_client" required
                               placeholder="Ex : Rakoto"
                               value="<?= htmlspecialchars($editData['nom_client'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Solde (Ar)</label>
                        <input type="number" name="solde" step="0.01" min="0" required
                               placeholder="0.00"
                               value="<?= htmlspecialchars($editData['solde'] ?? '') ?>">
                    </div>

                    <div class="btn-group">
                        <?php if ($editData): ?>
                            <button type="submit" class="btn btn-warning">Enregistrer</button>
                            <a href="index.php" class="btn btn-primary">Annuler</a>
                        <?php else: ?>
                            <button type="submit" class="btn btn-success">Enregistrer</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- ---- Table des comptes ---- -->
        <div class="card">
            <div class="card-header">Comptes des Clients</div>
            <div class="card-body" style="padding:0">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>N° Compte</th>
                                <th>Nom du Client</th>
                                <th>Solde (Ar)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($comptes)): ?>
                            <tr><td colspan="4">
                                <div class="empty-state">
                                    <!-- <div class="ico">💳</div> -->
                                    <p>Aucun client enregistré.<br>Ajoutez-en un avec le formulaire.</p>
                                </div>
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($comptes as $c): ?>
                            <tr>
                                <td><strong>#<?= $c['num_compte'] ?></strong></td>
                                <td><?= htmlspecialchars($c['nom_client']) ?></td>
                                <td class="<?= $c['solde'] > 0 ? 'solde-pos' : ($c['solde'] < 0 ? 'solde-neg' : 'solde-null') ?>">
                                    <?= number_format($c['solde'], 2, ',', ' ') ?> Ar
                                </td>
                                <td>
                                    <a href="?edit=<?= $c['num_compte'] ?>"
                                       class="btn btn-warning btn-sm">Modifier</a>
                                    <form method="POST" style="display:inline"
                                          onsubmit="return confirm('Supprimer ce compte ?')">
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="num_compte" value="<?= $c['num_compte'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                        <?php if (!empty($comptes)): ?>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="2" style="text-align:right">Solde Total des clients :</td>
                                <td colspan="2" class="solde-pos">
                                    <?= number_format($soldeTotal, 2, ',', ' ') ?> Ar
                                </td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- /grid-2 -->

</div><!-- /container -->

<footer style="text-align:center;padding:24px;color:var(--muted);font-size:.82rem;margin-top:24px">
    BanqueApp — Espace Caissier · <?= htmlspecialchars($_SESSION['nom_complet']) ?>
</footer>

</body>
</html>
