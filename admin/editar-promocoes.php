<?php include 'partials/html.php' ?>

<?php
#======================================#
ini_set('display_errors', 0);
error_reporting(E_ALL);
#======================================#
session_start();
include_once "services/database.php";
include_once 'logs/registrar_logs.php';
include_once "services/funcao.php";
include_once "services/crud.php";
include_once "services/crud-adm.php";
include_once 'services/checa_login_adm.php';
include_once "services/CSRF_Protect.php";
include_once "validar_2fa.php";
$csrf = new CSRF_Protect();
#======================================#
#expulsa user
checa_login_adm();
#======================================#
//inicio do script expulsa usuario bloqueado
if ($_SESSION['data_adm']['status'] != '1') {
    echo "<script>setTimeout(function() { window.location.href = 'bloqueado.php'; }, 0);</script>";
    exit();
}

function ensure_promocoes_link_column() {
    global $mysqli;
    $check = $mysqli->query("SHOW COLUMNS FROM promocoes LIKE 'link'");
    if ($check && $check->num_rows === 0) {
        $mysqli->query("ALTER TABLE promocoes ADD COLUMN link VARCHAR(500) NULL DEFAULT NULL AFTER status");
    }
}

function parse_promocao_link($link) {
    $link = trim((string)$link);
    if ($link === '') {
        return ['tipo' => 'detail', 'internal' => '', 'custom' => ''];
    }
    if (preg_match('#^https?://#i', $link)) {
        return ['tipo' => 'custom', 'internal' => '', 'custom' => $link];
    }
    $decoded = json_decode($link, true);
    if (is_array($decoded) && ($decoded['type'] ?? '') === 'route' && !empty($decoded['info'])) {
        return ['tipo' => 'custom', 'internal' => '', 'custom' => $decoded['info']];
    }
    if (strpos($link, '/') === 0) {
        return ['tipo' => 'custom', 'internal' => '', 'custom' => $link];
    }
    return ['tipo' => 'internal', 'internal' => $link, 'custom' => ''];
}

function promocao_link_label($link, $targetValues) {
    $parsed = parse_promocao_link($link);
    if ($parsed['tipo'] === 'detail') {
        return 'Detalhe da promoção';
    }
    if ($parsed['tipo'] === 'custom') {
        return $parsed['custom'];
    }
    foreach ($targetValues as $target) {
        if ($target['value'] === $parsed['internal']) {
            return $target['label'];
        }
    }
    return 'Página interna';
}

ensure_promocoes_link_column();

$targetValues = [
    'recharge' => ['label' => 'Recarga', 'value' => '{"type":"recharge","info":"string"}'],
    'withdraw' => ['label' => 'Retirada', 'value' => '{"type":"withdraw","info":"string"}'],
    'agency' => ['label' => 'Convite (Agency)', 'value' => '{"type":"activity","info":{"activityName":"推荐好友领彩金","activityId":263}}'],
    'vip' => ['label' => 'VIP', 'value' => '{"type":"vip","info":"string"}'],
    'promotion' => ['label' => 'Comissão (Promotion)', 'value' => '{"type":"promotion","info":"string"}'],
    'activity_list' => ['label' => 'Lista de Promoções', 'value' => '{"type":"activity_list","info":"string"}'],
    'mystery' => ['label' => 'Bônus Mistério', 'value' => '{"type":"activity","info":{"activityName":"神秘彩金活动","activityId":268}}'],
    'signin' => ['label' => 'Login Diário (Sign In)', 'value' => '{"type":"activity","info":{"activityName":"签到奖励","activityId":264}}'],
    'redeem' => ['label' => 'Código de Resgate', 'value' => '{"type":"redeem_code","info":"string"}'],
    'rebate' => ['label' => 'Rebate (Realtime)', 'value' => '{"type":"activity","info":{"activityName":"实时返水","activityId":494}}'],
    'home' => ['label' => 'Início', 'value' => '{"type":"home","info":"string"}'],
];

# Função para buscar as promoções
function get_promocoes() {
    global $mysqli;
    $qry = "SELECT * FROM promocoes";
    $result = mysqli_query($mysqli, $qry);
    $promocoes = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $promocoes[] = $row;
        }
    }
    return $promocoes;
}

# Função para atualizar a promoção
function update_promocao($id, $titulo, $status, $link, $img = null) {
    global $mysqli;

    try {
        if ($img) {
            $qry = $mysqli->prepare("UPDATE promocoes SET titulo = ?, status = ?, link = ?, img = ? WHERE id = ?");
            $qry->bind_param("sissi", $titulo, $status, $link, $img, $id);
        } else {
            $qry = $mysqli->prepare("UPDATE promocoes SET titulo = ?, status = ?, link = ? WHERE id = ?");
            $qry->bind_param("sisi", $titulo, $status, $link, $id);
        }

        return $qry->execute();
    } catch (Exception $e) {
        error_log("Erro ao atualizar promoção: " . $e->getMessage());
        return false;
    }
}

# Se o formulário for enviado, atualizar os dados
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $titulo = $_POST['titulo'];
    $status = intval($_POST['status']);
    $linkTipo = $_POST['link_tipo'] ?? 'detail';
    if ($linkTipo === 'custom') {
        $custom = trim($_POST['link_custom'] ?? '');
        if (preg_match('#^https?://#i', $custom)) {
            $link = $custom;
        } elseif (strpos($custom, '/') === 0) {
            $link = json_encode(['type' => 'route', 'info' => $custom], JSON_UNESCAPED_SLASHES);
        } else {
            $link = $custom;
        }
    } elseif ($linkTipo === 'internal') {
        $link = trim($_POST['link_internal'] ?? '');
    } else {
        $link = '';
    }

    # Buscar a imagem atual no banco de dados
    $query = "SELECT img FROM promocoes WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $promocao = $result->fetch_assoc();
    $img = $promocao['img'];

    if (!empty($_FILES['img']['name'])) {
        $upload_dir = "../uploads/";
        $original_name = basename($_FILES['img']['name']);
        $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['png','jpg','jpeg','webp','gif','ico','avif','svg'];
        if (in_array($file_extension, $allowed_extensions, true)) {
            $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
            $mime = $finfo ? finfo_file($finfo, $_FILES['img']['tmp_name']) : ($_FILES['img']['type'] ?? '');
            if ($finfo) { finfo_close($finfo); }
            $is_image = stripos((string)$mime, 'image/') === 0;
            if ($is_image) {
                $new_img_name = time() . '_' . $original_name;
                $img_path = $upload_dir . $new_img_name;
                if (move_uploaded_file($_FILES["img"]["tmp_name"], $img_path)) {
                    $img = $new_img_name;
                } else {
                    $toastType = 'error';
                    $toastMessage = 'Erro ao enviar a imagem. Tente novamente.';
                    error_log("Erro ao mover o arquivo da imagem para $img_path");
                }
            } else {
                $toastType = 'error';
                $toastMessage = 'O arquivo enviado não é uma imagem válida.';
            }
        } else {
            $toastType = 'error';
            $toastMessage = 'Extensão de arquivo não permitida.';
        }
    }

    # Atualizar a promoção no banco de dados
    if (update_promocao($id, $titulo, $status, $link, $img)) {
        $toastType = 'success';
        $toastMessage = 'Promoção atualizada com sucesso!';
    } else {
        $toastType = 'error';
        $toastMessage = 'Erro ao atualizar a promoção. Tente novamente.';
        error_log("Erro ao atualizar a promoção com ID $id");
    }
}

# Buscar as promoções atuais
$promocoes = get_promocoes();
?>

<head>
    <?php $title = "Gerenciamento de Promoções";
    include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
</head>

<body>

    <!-- Top Bar Start -->
    <?php include 'partials/topbar.php' ?>
    <!-- Top Bar End -->
    <!-- leftbar-tab-menu -->
    <?php include 'partials/startbar.php' ?>
    <!-- end leftbar-tab-menu-->

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Gerenciamento de Promoções</h4>
                            </div>

                            <div class="card-body">
                                <table class="table table-centered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Título</th>
                                            <th>Imagem</th>
                                            <th>Redirecionamento</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($promocoes as $promocao): ?>
                                            <?php $linkParsed = parse_promocao_link($promocao['link'] ?? ''); ?>
                                            <tr>
                                                <td><?= $promocao['id']; ?></td>
                                                <td><?= $promocao['titulo']; ?></td>
                                                <td><img src="<?= (strpos($promocao['img'], '/uploads/') === 0 ? '' : '/uploads/') . $promocao['img']; ?>?v=<?= time(); ?>" alt="Promoção" width="100"></td>
                                                <td><small><?= htmlspecialchars(promocao_link_label($promocao['link'] ?? '', $targetValues)); ?></small></td>
                                                <td><?= $promocao['status'] == 1 ? 'Ativo' : 'Inativo'; ?></td>
                                                <td>
                                                    <button class="btn btn-primary" data-bs-toggle="modal"
                                                            data-bs-target="#editPromocaoModal<?= $promocao['id']; ?>">Editar</button>
                                                </td>
                                            </tr>

                                            <!-- Modal de Edição -->
                                            <div class="modal fade" id="editPromocaoModal<?= $promocao['id']; ?>" tabindex="-1" aria-labelledby="editPromocaoLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editPromocaoLabel">Editar Promoção</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form method="POST" enctype="multipart/form-data">
                                                                <input type="hidden" name="id" value="<?= $promocao['id']; ?>">
                                                                <div class="mb-3">
                                                                    <label for="titulo" class="form-label">Título</label>
                                                                    <input type="text" class="form-control" name="titulo" value="<?= $promocao['titulo']; ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="img" class="form-label">Imagem</label>
                                                                    <input type="file" class="form-control" name="img">
                                                                    <small class="text-muted">Deixe em branco se não quiser alterar a imagem.</small>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="link_tipo_<?= $promocao['id']; ?>" class="form-label">Redirecionamento ao clicar</label>
                                                                    <select class="form-select promo-link-tipo" name="link_tipo" id="link_tipo_<?= $promocao['id']; ?>" data-promo-id="<?= $promocao['id']; ?>">
                                                                        <option value="detail" <?= $linkParsed['tipo'] === 'detail' ? 'selected' : ''; ?>>Abrir detalhe da promoção</option>
                                                                        <option value="internal" <?= $linkParsed['tipo'] === 'internal' ? 'selected' : ''; ?>>Página interna do site</option>
                                                                        <option value="custom" <?= $linkParsed['tipo'] === 'custom' ? 'selected' : ''; ?>>Link / rota personalizada</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3 promo-link-internal" id="link_internal_wrap_<?= $promocao['id']; ?>" style="<?= $linkParsed['tipo'] === 'internal' ? '' : 'display:none;'; ?>">
                                                                    <label for="link_internal_<?= $promocao['id']; ?>" class="form-label">Página interna</label>
                                                                    <select class="form-select" name="link_internal" id="link_internal_<?= $promocao['id']; ?>">
                                                                        <?php foreach ($targetValues as $target): ?>
                                                                            <option value='<?= htmlspecialchars($target['value'], ENT_QUOTES); ?>' <?= $linkParsed['internal'] === $target['value'] ? 'selected' : ''; ?>><?= htmlspecialchars($target['label']); ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3 promo-link-custom" id="link_custom_wrap_<?= $promocao['id']; ?>" style="<?= $linkParsed['tipo'] === 'custom' ? '' : 'display:none;'; ?>">
                                                                    <label for="link_custom_<?= $promocao['id']; ?>" class="form-label">Link de redirecionamento</label>
                                                                    <input type="text" class="form-control" name="link_custom" id="link_custom_<?= $promocao['id']; ?>" value="<?= htmlspecialchars($linkParsed['custom']); ?>" placeholder="/activity/SignIn/1887@style_2">
                                                                    <small class="text-muted">Rota interna (ex: /activity/SignIn/1887@style_2) navega dentro do site. URL externa (https://...) abre em nova aba.</small>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="status" class="form-label">Status</label>
                                                                    <select class="form-select" name="status">
                                                                        <option value="1" <?= $promocao['status'] == 1 ? 'selected' : ''; ?>>Ativo</option>
                                                                        <option value="0" <?= $promocao['status'] == 0 ? 'selected' : ''; ?>>Inativo</option>
                                                                    </select>
                                                                </div>
                                                                <div class="text-center">
                                                                    <button type="submit" class="btn btn-success">Salvar Alterações</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div><!-- end row -->
            </div><!-- container -->
                                        <?php include 'partials/endbar.php' ?>
    <?php include 'partials/footer.php' ?>
        </div><!-- page content -->
    </div><!-- page-wrapper -->

    <!-- Toast container -->
    <div id="toastPlacement" class="toast-container position-fixed bottom-0 end-0 p-3"></div>
    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>

    <script>
        function showToast(type, message){window.showToast(type,message);}

        function togglePromoLinkFields(selectEl) {
            const promoId = selectEl.dataset.promoId;
            const tipo = selectEl.value;
            const internalWrap = document.getElementById('link_internal_wrap_' + promoId);
            const customWrap = document.getElementById('link_custom_wrap_' + promoId);
            if (internalWrap) internalWrap.style.display = tipo === 'internal' ? '' : 'none';
            if (customWrap) customWrap.style.display = tipo === 'custom' ? '' : 'none';
        }

        document.querySelectorAll('.promo-link-tipo').forEach(function(selectEl) {
            selectEl.addEventListener('change', function() {
                togglePromoLinkFields(selectEl);
            });
        });

        // Mostrar Toast com base no resultado do PHP
        <?php if (isset($toastType) && isset($toastMessage)): ?>
            showToast("<?= $toastType; ?>", "<?= $toastMessage; ?>");
        <?php endif; ?>
    </script>
</body>
</html>
