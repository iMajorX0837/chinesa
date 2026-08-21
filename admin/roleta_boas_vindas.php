<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
include_once "services/database.php";
include_once "services/checa_login_adm.php";
checa_login_adm();

function rw_ensure_table($mysqli){
    $mysqli->query("CREATE TABLE IF NOT EXISTS welcome_roulette_settings (
        id INT PRIMARY KEY,
        min_cents INT NOT NULL DEFAULT 30,
        max_cents INT NOT NULL DEFAULT 300,
        audit_multiple DECIMAL(10,2) NOT NULL DEFAULT 1.00,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $res = $mysqli->query("SELECT COUNT(*) AS c FROM welcome_roulette_settings WHERE id=1");
    $row = $res ? $res->fetch_assoc() : ['c'=>0];
    if ((int)$row['c'] === 0){
        $mysqli->query("INSERT INTO welcome_roulette_settings (id, min_cents, max_cents, audit_multiple) VALUES (1, 30, 300, 1.00)");
    }
    $chk = $mysqli->query("SHOW COLUMNS FROM welcome_roulette_settings LIKE 'audit_multiple'");
    if (!$chk || $chk->num_rows === 0) {
        @$mysqli->query("ALTER TABLE welcome_roulette_settings ADD COLUMN audit_multiple DECIMAL(10,2) NOT NULL DEFAULT 1.00");
    }
}
function rw_get($mysqli){
    rw_ensure_table($mysqli);
    $res = $mysqli->query("SELECT * FROM welcome_roulette_settings WHERE id=1");
    return $res ? $res->fetch_assoc() : ['min_cents'=>30,'max_cents'=>300,'audit_multiple'=>1.00];
}
function rw_save($mysqli, $data){
    rw_ensure_table($mysqli);
    $min = (int)round((float)str_replace(',', '.', $data['min_cents']));
    $max = (int)round((float)str_replace(',', '.', $data['max_cents']));
    $audit = (float)str_replace(',', '.', $data['audit_multiple']);
    if ($min > $max){ $t=$min; $min=$max; $max=$t; }
    $stmt = $mysqli->prepare("UPDATE welcome_roulette_settings SET min_cents=?, max_cents=?, audit_multiple=? WHERE id=1");
    $stmt->bind_param("iid", $min, $max, $audit);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

$msg = null;
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $ok = rw_save($mysqli, [
        'min_cents' => $_POST['min_cents'] ?? 30,
        'max_cents' => $_POST['max_cents'] ?? 300,
        'audit_multiple' => $_POST['audit_multiple'] ?? 1.00,
    ]);
    $msg = $ok ? ['type'=>'success','text'=>'Configurações salvas'] : ['type'=>'danger','text'=>'Falha ao salvar'];
}
$cfg = rw_get($mysqli);
?>
<?php include 'partials/html.php' ?>
<head>
    <?php $title="Roleta Boas-Vindas"; include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
</head>
<body>
<?php include 'partials/topbar.php' ?>
<?php include 'partials/startbar.php' ?>
<div class="page-wrapper">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row justify-content-center">
                <div class="col-md-10 col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Configurações • Roleta de Boas-Vindas</h4>
                        </div>
                        <div class="card-body">
                            <?php if($msg): ?>
                                <div class="alert alert-<?= $msg['type'] ?>"><?= htmlspecialchars($msg['text']) ?></div>
                            <?php endif; ?>
                            <form method="post">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Recompensa Mínima (centavos)</label>
                                        <input type="number" step="1" class="form-control" name="min_cents" value="<?= (int)($cfg['min_cents'] ?? 30) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Recompensa Máxima (centavos)</label>
                                        <input type="number" step="1" class="form-control" name="max_cents" value="<?= (int)($cfg['max_cents'] ?? 300) ?>">
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Audit Multiple (x)</label>
                                        <input type="number" step="0.01" class="form-control" name="audit_multiple" value="<?= number_format((float)($cfg['audit_multiple'] ?? 1.00),2,'.','') ?>">
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">Salvar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'partials/endbar.php' ?>
            <?php include 'partials/footer.php' ?>
        </div>
    </div>
</div>
<?php include 'partials/vendorjs.php' ?>
<script src="assets/js/app.js"></script>
</body>
</html>
