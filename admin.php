<?php
// admin.php — Tableau de bord Admin (lecture seule : audit)
// Inclus via index.php, session déjà démarrée

require_once 'config.php';
$pdo = getConnection();

// Filtres optionnels
$filterAction = $_GET['filter_action'] ?? '';
$filterUser   = $_GET['filter_user']   ?? '';
$filterDate   = $_GET['filter_date']   ?? '';

// Construction de la requête avec filtres
$where  = [];
$params = [];

if ($filterAction) {
    $where[]  = "type_action = :action";
    $params[':action'] = $filterAction;
}
if ($filterUser) {
    $where[]  = "utilisateur ILIKE :user";
    $params[':user'] = "%$filterUser%";
}
if ($filterDate) {
    $where[]  = "DATE(date_maj) = :date";
    $params[':date'] = $filterDate;
}

$sql = "SELECT * FROM audit_compte";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY date_maj DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$audits = $stmt->fetchAll();

// Statistiques globales
$stats = $pdo->query("
    SELECT
        COUNT(*) FILTER (WHERE type_action = 'INSERTION')    AS nb_insertions,
        COUNT(*) FILTER (WHERE type_action = 'MODIFICATION') AS nb_modifications,
        COUNT(*) FILTER (WHERE type_action = 'SUPPRESSION')  AS nb_suppressions,
        COUNT(*)                                              AS total
    FROM audit_compte
")->fetch();

// Liste des utilisateurs pour le filtre
$users = $pdo->query("SELECT DISTINCT utilisateur FROM audit_compte ORDER BY utilisateur")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🛡️ Admin — Journal d'Audit</title>
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
            background: linear-gradient(135deg, var(--primary) 0%, #234e72 100%);
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
            color:rgba(255,255,255,.85); font-size:.9rem;
            background:rgba(255,255,255,.12); padding:7px 16px;
            border-radius:20px; display:flex; align-items:center; gap:8px;
        }
        .badge-admin-nav {
            background:#e74c3c; color:#fff;
            font-size:.72rem; font-weight:700;
            padding:2px 8px; border-radius:10px; letter-spacing:.3px;
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
        .container { max-width:1300px; margin:32px auto; padding:0 24px; }

        /* ---- Page Title ---- */
        .page-title {
            margin-bottom:28px;
            display:flex; align-items:center; gap:14px;
        }
        .page-title .icon-wrap {
            width:52px; height:52px; border-radius:14px;
            background:linear-gradient(135deg,var(--primary),var(--secondary));
            display:flex; align-items:center; justify-content:center; font-size:1.6rem;
        }
        .page-title h1 { font-size:1.5rem; color:var(--primary); font-weight:700; }
        .page-title p  { font-size:.9rem; color:var(--muted); margin-top:2px; }

        /* ---- Stat Cards ---- */
        .stats-row { display:flex; gap:16px; margin-bottom:26px; flex-wrap:wrap; }
        .stat-card {
            flex:1; min-width:150px;
            background:#fff; border-radius:14px;
            padding:22px 24px; text-align:center;
            box-shadow:var(--shadow); border:1px solid var(--border);
            position:relative; overflow:hidden;
        }
        .stat-card::before {
            content:''; position:absolute; top:0; left:0; right:0; height:4px;
        }
        .stat-insert::before  { background:var(--accent);  }
        .stat-modif::before   { background:var(--secondary);}
        .stat-delete::before  { background:var(--danger);  }
        .stat-total::before   { background:var(--primary); }
        .stat-card .ico { font-size:2rem; margin-bottom:8px; }
        .stat-card .num { font-size:2.4rem; font-weight:800; line-height:1; }
        .stat-card .lbl { font-size:.78rem; color:var(--muted); margin-top:5px; text-transform:uppercase; letter-spacing:.5px; }
        .stat-insert .num  { color:var(--accent);   }
        .stat-modif  .num  { color:var(--secondary);}
        .stat-delete .num  { color:var(--danger);   }
        .stat-total  .num  { color:var(--primary);  }

        /* ---- Card ---- */
        .card { background:#fff; border-radius:14px; box-shadow:var(--shadow); border:1px solid var(--border); overflow:hidden; }
        .card-header {
            padding:18px 24px;
            background:linear-gradient(90deg,var(--primary) 0%, #234e72 100%);
            color:#fff; font-size:1.05rem; font-weight:600;
            display:flex; align-items:center; gap:10px;
        }
        .card-body { padding:24px; }

        /* ---- Filters ---- */
        .filters {
            display:flex; gap:14px; flex-wrap:wrap;
            margin-bottom:20px; align-items:flex-end;
        }
        .filter-group { display:flex; flex-direction:column; gap:5px; flex:1; min-width:140px; }
        .filter-group label {
            font-size:.78rem; font-weight:700; color:var(--muted);
            text-transform:uppercase; letter-spacing:.4px;
        }
        select, input[type="date"], input[type="text"] {
            padding:9px 12px; border:1.5px solid var(--border);
            border-radius:8px; font-size:.92rem; color:var(--text);
            background:#fafcff; transition:border-color .2s;
        }
        select:focus, input:focus { outline:none; border-color:var(--secondary); }
        .btn-filter {
            padding:9px 20px; background:var(--secondary); color:#fff;
            border:none; border-radius:8px; font-size:.92rem; font-weight:600;
            cursor:pointer; align-self:flex-end; transition:filter .15s;
        }
        .btn-filter:hover { filter:brightness(1.1); }
        .btn-reset {
            padding:9px 14px; background:#eee; color:var(--text);
            border:none; border-radius:8px; font-size:.92rem; font-weight:600;
            cursor:pointer; align-self:flex-end; text-decoration:none;
        }

        /* ---- Table ---- */
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; font-size:.92rem; }
        thead th {
            background:var(--primary); color:#fff;
            padding:13px 16px; text-align:left; font-weight:600; white-space:nowrap;
        }
        thead th:first-child { border-radius:8px 0 0 0; }
        thead th:last-child  { border-radius:0 8px 0 0; }
        tbody tr { border-bottom:1px solid var(--border); transition:background .15s; }
        tbody tr:hover { background:#edf3fb; }
        tbody td { padding:11px 16px; vertical-align:middle; }
        tbody tr:last-child { border-bottom:none; }

        /* Badges */
        .badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:.78rem; font-weight:700; letter-spacing:.3px; }
        .badge-INSERTION    { background:#d4edda; color:#155724; }
        .badge-MODIFICATION { background:#d0e8f7; color:#0c5460; }
        .badge-SUPPRESSION  { background:#f8d7da; color:#721c24; }

        /* Solde */
        .solde-pos  { color:var(--accent); font-weight:700; }
        .solde-null { color:var(--muted); font-size:.85rem; }

        /* Footer totaux */
        tfoot td { background:#f0f4fa; font-weight:700; padding:13px 16px; font-size:.9rem; }
        tfoot .total-row td { border-top:2px solid var(--primary); }

        /* Empty */
        .empty-state { text-align:center; padding:40px; color:var(--muted); }
        .empty-state .ico { font-size:2.5rem; margin-bottom:10px; }

        /* Readonly banner */
        .readonly-banner {
            background:linear-gradient(90deg,#fff3cd,#ffeaa7);
            border:1px solid #f39c12; border-radius:10px;
            padding:13px 20px; margin-bottom:22px;
            display:flex; align-items:center; gap:12px;
            font-size:.92rem; color:#856404; font-weight:500;
        }
        .readonly-banner .ico { font-size:1.4rem; }
    </style>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav>
    <div class="nav-brand">
        <span>🏦</span> BanqueApp
    </div>
    <div class="nav-right">
        <div class="nav-user">
            🛡️ <?= htmlspecialchars($_SESSION['nom_complet']) ?>
            <span class="badge-admin-nav">ADMIN</span>
        </div>
        <a href="logout.php" class="btn-logout">🚪 Déconnexion</a>
    </div>
</nav>

<div class="container">

    <!-- Page Title -->
    <div class="page-title">
        <div class="icon-wrap">🔍</div>
        <div>
            <h1>Journal d'Audit</h1>
            <p>Supervision de toutes les opérations sur les comptes clients</p>
        </div>
    </div>

    <!-- Bandeau lecture seule -->
    <div class="readonly-banner">
        <span class="ico">👁️</span>
        <span>Mode <strong>Administrateur</strong> — Consultation uniquement. Vous supervisez les actions de tous les utilisateurs.</span>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card stat-insert">
            <div class="ico">➕</div>
            <div class="num"><?= $stats['nb_insertions'] ?></div>
            <div class="lbl">Insertions</div>
        </div>
        <div class="stat-card stat-modif">
            <div class="ico">✏️</div>
            <div class="num"><?= $stats['nb_modifications'] ?></div>
            <div class="lbl">Modifications</div>
        </div>
        <div class="stat-card stat-delete">
            <div class="ico">🗑️</div>
            <div class="num"><?= $stats['nb_suppressions'] ?></div>
            <div class="lbl">Suppressions</div>
        </div>
        <div class="stat-card stat-total">
            <div class="ico">📊</div>
            <div class="num"><?= $stats['total'] ?></div>
            <div class="lbl">Total</div>
        </div>
    </div>

    <!-- Table Audit -->
    <div class="card">
        <div class="card-header">📋 Historique complet des opérations</div>
        <div class="card-body">

            <!-- Filtres -->
            <form method="GET" class="filters">
                <div class="filter-group">
                    <label>Type d'action</label>
                    <select name="filter_action">
                        <option value="">— Tous —</option>
                        <option value="INSERTION"    <?= $filterAction==='INSERTION'    ? 'selected':'' ?>>➕ Insertion</option>
                        <option value="MODIFICATION" <?= $filterAction==='MODIFICATION' ? 'selected':'' ?>>✏️ Modification</option>
                        <option value="SUPPRESSION"  <?= $filterAction==='SUPPRESSION'  ? 'selected':'' ?>>🗑️ Suppression</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Utilisateur</label>
                    <select name="filter_user">
                        <option value="">— Tous —</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?= htmlspecialchars($u['utilisateur']) ?>"
                                <?= $filterUser===$u['utilisateur'] ? 'selected':'' ?>>
                            <?= htmlspecialchars($u['utilisateur']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Date</label>
                    <input type="date" name="filter_date" value="<?= htmlspecialchars($filterDate) ?>">
                </div>
                <button type="submit" class="btn-filter">🔍 Filtrer</button>
                <a href="index.php" class="btn-reset">✖ Réinitialiser</a>
            </form>

            <!-- Table -->
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Type d'Action</th>
                            <th>Date & Heure</th>
                            <th>N° Compte</th>
                            <th>Nom Client</th>
                            <th>Solde Ancien (Ar)</th>
                            <th>Solde Nouveau (Ar)</th>
                            <th>Utilisateur</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($audits)): ?>
                        <tr><td colspan="8">
                            <div class="empty-state">
                                <div class="ico">📭</div>
                                <p>Aucune opération trouvée pour ces critères.</p>
                            </div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($audits as $a): ?>
                        <tr>
                            <td><strong><?= $a['id'] ?></strong></td>
                            <td>
                                <span class="badge badge-<?= $a['type_action'] ?>">
                                    <?php
                                    $icons = ['INSERTION'=>'➕','MODIFICATION'=>'✏️','SUPPRESSION'=>'🗑️'];
                                    echo $icons[$a['type_action']] . ' ' . $a['type_action'];
                                    ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i:s', strtotime($a['date_maj'])) ?></td>
                            <td><?= $a['num_compte'] ? '#'.$a['num_compte'] : '—' ?></td>
                            <td><?= htmlspecialchars($a['nom_client'] ?? '—') ?></td>
                            <td class="<?= $a['solde_ancien'] !== null ? 'solde-pos' : '' ?>">
                                <?= $a['solde_ancien'] !== null
                                    ? number_format($a['solde_ancien'], 2, ',', ' ') . ' Ar'
                                    : '<span class="solde-null">—</span>' ?>
                            </td>
                            <td class="<?= $a['solde_nouv'] !== null ? 'solde-pos' : '' ?>">
                                <?= $a['solde_nouv'] !== null
                                    ? number_format($a['solde_nouv'], 2, ',', ' ') . ' Ar'
                                    : '<span class="solde-null">—</span>' ?>
                            </td>
                            <td>
                                <span style="background:#edf3fb;padding:3px 10px;border-radius:6px;font-size:.85rem;font-weight:600;color:var(--primary)">
                                    👤 <?= htmlspecialchars($a['utilisateur'] ?? '—') ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>

                    <!-- PIED : Totaux -->
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="2" style="text-align:right">📊 TOTAUX :</td>
                            <td colspan="6">
                                <span style="color:var(--accent);margin-right:22px">
                                    ➕ Insertions : <strong><?= $stats['nb_insertions'] ?></strong>
                                </span>
                                <span style="color:var(--secondary);margin-right:22px">
                                    ✏️ Modifications : <strong><?= $stats['nb_modifications'] ?></strong>
                                </span>
                                <span style="color:var(--danger);margin-right:22px">
                                    🗑️ Suppressions : <strong><?= $stats['nb_suppressions'] ?></strong>
                                </span>
                                <span style="color:var(--primary)">
                                    📈 Total : <strong><?= $stats['total'] ?></strong>
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>
    </div>

</div>

<footer style="text-align:center;padding:24px;color:var(--muted);font-size:.82rem;margin-top:20px">
    🏦 BanqueApp — Interface Admin · Accès restreint
</footer>

</body>
</html>
