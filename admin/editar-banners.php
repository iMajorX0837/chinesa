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

# Função para buscar os banners
function get_banners($type = 'lobby_carousel') {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT * FROM banner WHERE type = ?");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $result = $stmt->get_result();
    $banners = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $banners[] = $row;
        }
    }
    return $banners;
}

function update_banner($id, $titulo, $status, $img = null, $targetValue = null, $defaultIconUrl = null) {
    global $mysqli;

    if ($img) {
        $qry = $mysqli->prepare("UPDATE banner SET titulo = ?, status = ?, img = ?, targetValue = ?, defaultIconUrl = ? WHERE id = ?");
        $qry->bind_param("sisssi", $titulo, $status, $img, $targetValue, $defaultIconUrl, $id);
    } else {
        $qry = $mysqli->prepare("UPDATE banner SET titulo = ?, status = ?, targetValue = ?, defaultIconUrl = ? WHERE id = ?");
        $qry->bind_param("sissi", $titulo, $status, $targetValue, $defaultIconUrl, $id);
    }

    return $qry->execute();
}

function create_banner($type, $titulo, $status, $img, $targetValue = null, $defaultIconUrl = null)
{
    global $mysqli;
    $qry = $mysqli->prepare("INSERT INTO banner (type, titulo, status, img, targetValue, defaultIconUrl) VALUES (?, ?, ?, ?, ?, ?)");
    $qry->bind_param("ssisss", $type, $titulo, $status, $img, $targetValue, $defaultIconUrl);
    return $qry->execute();
}

function banner_upload_error_message($code)
{
    switch ((int) $code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'Arquivo muito grande. Limite: 20 MB.';
        case UPLOAD_ERR_PARTIAL:
            return 'Upload incompleto. Tente enviar a imagem novamente.';
        case UPLOAD_ERR_NO_FILE:
            return 'Nenhum arquivo selecionado.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Servidor sem pasta temporária para upload.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Servidor não conseguiu gravar o arquivo enviado.';
        case UPLOAD_ERR_EXTENSION:
            return 'Upload bloqueado pela configuração do servidor.';
        default:
            return 'Erro ao enviar a imagem. Tente novamente.';
    }
}

function banner_is_valid_image_file(array $file, $file_extension)
{
    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
    $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : ($file['type'] ?? '');
    if ($finfo) {
        finfo_close($finfo);
    }

    if (is_string($mime) && stripos($mime, 'image/') === 0) {
        return true;
    }

    if ($file_extension === 'svg') {
        return is_string($mime) && in_array($mime, ['image/svg+xml', 'application/xml', 'text/xml', 'text/plain'], true);
    }

    if ($file_extension === 'avif') {
        return is_string($mime) && (stripos($mime, 'image/') === 0 || $mime === 'application/octet-stream');
    }

    if ($file_extension !== 'svg') {
        return @getimagesize($file['tmp_name']) !== false;
    }

    return false;
}

function banner_store_uploaded_image(array $file)
{
    $upload_dir = __DIR__ . '/../uploads/';

    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => banner_upload_error_message($file['error'] ?? UPLOAD_ERR_NO_FILE)];
    }

    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'message' => 'Arquivo inválido ou não recebido pelo servidor.'];
    }

    $original_name = basename($file['name'] ?? '');
    $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $allowed_extensions = ['png', 'jpg', 'jpeg', 'webp', 'gif', 'ico', 'avif', 'svg'];

    if (!in_array($file_extension, $allowed_extensions, true)) {
        return ['ok' => false, 'message' => 'Extensão de arquivo não permitida.'];
    }

    if (!banner_is_valid_image_file($file, $file_extension)) {
        return ['ok' => false, 'message' => 'O arquivo enviado não é uma imagem válida.'];
    }

    if (!is_dir($upload_dir) && !@mkdir($upload_dir, 0755, true)) {
        return ['ok' => false, 'message' => 'Pasta de uploads indisponível.'];
    }

    $new_img_name = uniqid('banner_', true) . '.' . $file_extension;
    $img_path = $upload_dir . $new_img_name;

    if (!move_uploaded_file($file['tmp_name'], $img_path) || !is_file($img_path)) {
        return ['ok' => false, 'message' => 'Erro ao enviar a imagem. Verifique permissões da pasta uploads.'];
    }

    return ['ok' => true, 'filename' => $new_img_name];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : 'update';
    $titulo = isset($_POST['titulo']) ? $_POST['titulo'] : '';
    $status = isset($_POST['status']) ? intval($_POST['status']) : 0;
    $targetValue = isset($_POST['targetValue']) ? $_POST['targetValue'] : null;
    $defaultIconUrl = isset($_POST['defaultIconUrl']) ? $_POST['defaultIconUrl'] : null;

    if ($action === 'create') {
        $type = isset($_POST['type']) ? $_POST['type'] : 'lobby_carousel';
        $allowed_types = ['lobby_carousel', 'lobby_sidebar_banner'];
        if (!in_array($type, $allowed_types)) {
            $type = 'lobby_carousel';
        }
        if (empty($titulo) || empty($_FILES['img']['name'])) {
            $toastType = 'error';
            $toastMessage = 'Informe título e imagem para criar um novo banner.';
        } else {
            $upload = banner_store_uploaded_image($_FILES['img']);
            if ($upload['ok']) {
                if (create_banner($type, $titulo, $status, $upload['filename'], $targetValue, $defaultIconUrl)) {
                    $toastType = 'success';
                    $toastMessage = 'Novo banner criado com sucesso.';
                } else {
                    @unlink(__DIR__ . '/../uploads/' . $upload['filename']);
                    $toastType = 'error';
                    $toastMessage = 'Erro ao salvar o novo banner.';
                }
            } else {
                $toastType = 'error';
                $toastMessage = $upload['message'];
            }
        }
    } else {
        $id = intval($_POST['id']);
        $query = "SELECT img FROM banner WHERE id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $banner = $result->fetch_assoc();
        if (!$banner) {
            $toastType = 'error';
            $toastMessage = 'Banner não encontrado.';
        } else {
            $img = $banner['img'];

            if (!empty($_FILES['img']['name'])) {
                $upload = banner_store_uploaded_image($_FILES['img']);
                if ($upload['ok']) {
                    $img = $upload['filename'];
                } else {
                    $toastType = 'error';
                    $toastMessage = $upload['message'];
                }
            }

            if (!isset($toastType) || $toastType !== 'error') {
                if (update_banner($id, $titulo, $status, $img, $targetValue, $defaultIconUrl)) {
                    $toastType = 'success';
                    $toastMessage = 'Banner atualizado com sucesso.';
                } else {
                    $toastType = 'error';
                    $toastMessage = 'Erro ao atualizar o banner. Tente novamente.';
                }
            }
        }
    }
}

# Buscar os banners atuais
$banners_carousel = get_banners('lobby_carousel');
$banners_lobby = get_banners('lobby_banner');
$banners_sidebar = get_banners('lobby_sidebar_banner');

// Definições de targetValue e defaultIconUrl
$targetValues = [
    'recharge' => ['label' => 'Recarga', 'value' => '{"type":"recharge","info":"string"}'],
    'withdraw' => ['label' => 'Retirada', 'value' => '{"type":"withdraw","info":"string"}'],
    'agency' => ['label' => 'Convite (Agency)', 'value' => '{"type":"activity","info":{"activityName":"推荐好友领彩金","activityId":263}}'],
    'vip' => ['label' => 'VIP', 'value' => '{"type":"vip","info":"string"}'],
    'promotion' => ['label' => 'Comissão (Promotion)', 'value' => '{"type":"promotion","info":"string"}'],
    'mystery' => ['label' => 'Bônus Mistério', 'value' => '{"type":"activity","info":{"activityName":"神秘彩金活动","activityId":268}}'],
    'signin' => ['label' => 'Login Diário (Sign In)', 'value' => '{"type":"activity","info":{"activityName":"签到奖励","activityId":264}}'],
    'redeem' => ['label' => 'Código de Resgate', 'value' => '{"type":"redeem_code","info":"string"}'],
    'rebate' => ['label' => 'Rebate (Realtime)', 'value' => '{"type":"activity","info":{"activityName":"实时返水","activityId":494}}']
];

$defaultIcons = [
    '/uploads/Rebate.png',
    'https://upload-sys-pics.f-1-g-h.com/recommendActivityConfig/recharge.png',
    'https://upload-sys-pics.f-1-g-h.com/recommendActivityConfig/withdraw.png',
    'https://upload-sys-pics.f-1-g-h.com/recommendActivityConfig/Agency.png',
    'https://upload-sys-pics.f-1-g-h.com/recommendActivityConfig/vip.png',
    'https://upload-sys-pics.f-1-g-h.com/recommendActivityConfig/promotion.png',
    'https://upload-sys-pics.f-1-g-h.com/recommendActivityConfig/MysteryReward.png',
    'https://upload-sys-pics.f-1-g-h.com/recommendActivityConfig/SignInVolume.png',
    'https://upload-sys-pics.f-1-g-h.com/recommendActivityConfig/redeem_code.png'
];
?>

<head>
    <?php $title = "Gerenciamento de Banners";
    include 'partials/title-meta.php' ?>
    <style>
        .select2-container .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
    </style>
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
                                <h4 class="card-title">Gerenciamento de Banners</h4>
                            </div>

                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">Carrossel Principal</h5>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createBannerCarouselModal">
                                        <i class="ti ti-plus me-1"></i>Novo Banner
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-compact align-middle text-nowrap mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Título</th>
                                                <th>Imagem</th>
                                                <th>Status</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($banners_carousel as $banner): ?>
                                                <tr>
                                                    <td><?= $banner['id']; ?></td>
                                                    <td><?= $banner['titulo']; ?></td>
                                                    <td><img src="/uploads/<?= $banner['img']; ?>?v=<?= time(); ?>" alt="Banner" width="90"></td>
                                                    <td><?= $banner['status'] == 1 ? 'Ativo' : 'Inativo'; ?></td>
                                                    <td>
                                                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                                                data-bs-target="#editBannerModal<?= $banner['id']; ?>">Editar</button>
                                                    </td>
                                                </tr>
                                                <div class="modal fade" id="editBannerModal<?= $banner['id']; ?>" tabindex="-1" aria-labelledby="editBannerLabel" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="editBannerLabel">Editar Banner</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form method="POST" enctype="multipart/form-data">
                                                                    <input type="hidden" name="action" value="update">
                                                                    <input type="hidden" name="id" value="<?= $banner['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label for="titulo" class="form-label">Título</label>
                                                                        <input type="text" class="form-control" name="titulo" value="<?= $banner['titulo']; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="img" class="form-label">Imagem</label>
                                                                        <input type="file" class="form-control" name="img">
                                                                        <small class="text-muted">Deixe em branco se não quiser alterar a imagem.</small>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="status" class="form-label">Status</label>
                                                                        <select class="form-select" name="status">
                                                                            <option value="1" <?= $banner['status'] == 1 ? 'selected' : ''; ?>>Ativo</option>
                                                                            <option value="0" <?= $banner['status'] == 0 ? 'selected' : ''; ?>>Inativo</option>
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

                                <h5 class="mt-5 mb-3">Banners do Lobby</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm table-compact align-middle text-nowrap mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Título</th>
                                                <th>Imagem</th>
                                                <th>Status</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($banners_lobby as $banner): ?>
                                                <tr>
                                                    <td><?= $banner['id']; ?></td>
                                                    <td><?= $banner['titulo']; ?></td>
                                                    <td><img src="/uploads/<?= $banner['img']; ?>?v=<?= time(); ?>" alt="Banner" width="90"></td>
                                                    <td><?= $banner['status'] == 1 ? 'Ativo' : 'Inativo'; ?></td>
                                                    <td>
                                                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                                                data-bs-target="#editBannerModal<?= $banner['id']; ?>">Editar</button>
                                                    </td>
                                                </tr>
                                                <div class="modal fade" id="editBannerModal<?= $banner['id']; ?>" tabindex="-1" aria-labelledby="editBannerLabel" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="editBannerLabel">Editar Banner</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form method="POST" enctype="multipart/form-data">
                                                                    <input type="hidden" name="action" value="update">
                                                                    <input type="hidden" name="id" value="<?= $banner['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label for="titulo" class="form-label">Título</label>
                                                                        <input type="text" class="form-control" name="titulo" value="<?= $banner['titulo']; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="img" class="form-label">Imagem</label>
                                                                        <input type="file" class="form-control" name="img">
                                                                        <small class="text-muted">Deixe em branco se não quiser alterar a imagem.</small>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="status" class="form-label">Status</label>
                                                                        <select class="form-select" name="status">
                                                                            <option value="1" <?= $banner['status'] == 1 ? 'selected' : ''; ?>>Ativo</option>
                                                                            <option value="0" <?= $banner['status'] == 0 ? 'selected' : ''; ?>>Inativo</option>
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

                                <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
                                    <h5 class="mb-0">Banners Lateral (Sidebar)</h5>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createBannerSidebarModal">
                                        <i class="ti ti-plus me-1"></i>Novo Banner
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-compact align-middle text-nowrap mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Título</th>
                                                <th>Imagem</th>
                                                <th>Status</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($banners_sidebar as $banner): ?>
                                                <tr>
                                                    <td><?= $banner['id']; ?></td>
                                                    <td><?= $banner['titulo']; ?></td>
                                                    <td><img src="/uploads/<?= $banner['img']; ?>?v=<?= time(); ?>" alt="Banner" width="90"></td>
                                                    <td><?= $banner['status'] == 1 ? 'Ativo' : 'Inativo'; ?></td>
                                                    <td>
                                                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                                                data-bs-target="#editBannerModal<?= $banner['id']; ?>">Editar</button>
                                                    </td>
                                                </tr>
                                                <div class="modal fade" id="editBannerModal<?= $banner['id']; ?>" tabindex="-1" aria-labelledby="editBannerLabel" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="editBannerLabel">Editar Banner</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form method="POST" enctype="multipart/form-data">
                                                                    <input type="hidden" name="action" value="update">
                                                                    <input type="hidden" name="id" value="<?= $banner['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label for="titulo" class="form-label">Título</label>
                                                                        <input type="text" class="form-control" name="titulo" value="<?= $banner['titulo']; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="img" class="form-label">Imagem</label>
                                                                        <input type="file" class="form-control" name="img">
                                                                        <small class="text-muted">Deixe em branco se não quiser alterar a imagem.</small>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="status" class="form-label">Status</label>
                                                                        <select class="form-select" name="status">
                                                                            <option value="1" <?= $banner['status'] == 1 ? 'selected' : ''; ?>>Ativo</option>
                                                                            <option value="0" <?= $banner['status'] == 0 ? 'selected' : ''; ?>>Inativo</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="targetValue" class="form-label">Ação do Banner (Target)</label>
                                                                        <select class="form-select" name="targetValue">
                                                                            <option value="">Selecione...</option>
                                                                            <?php foreach ($targetValues as $key => $target): ?>
                                                                                <option value='<?= $target['value']; ?>' <?= (isset($banner['targetValue']) && $banner['targetValue'] == $target['value']) ? 'selected' : ''; ?>><?= $target['label']; ?></option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="defaultIconUrl" class="form-label">Ícone Padrão</label>
                                                                        <select class="form-select select2-icons" name="defaultIconUrl" style="width: 100%;">
                                                                            <option value="">Selecione...</option>
                                                                            <?php foreach ($defaultIcons as $icon): ?>
                                                                                <option value="<?= $icon; ?>" data-image="<?= $icon; ?>" <?= (isset($banner['defaultIconUrl']) && $banner['defaultIconUrl'] == $icon) ? 'selected' : ''; ?>><?= basename($icon); ?></option>
                                                                            <?php endforeach; ?>
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
                    </div>
                </div><!-- end row -->
            </div><!-- container -->
            
                            <?php include 'partials/endbar.php' ?>
    <?php include 'partials/footer.php' ?>
        </div><!-- page content -->
    </div><!-- page-wrapper -->

    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            function formatState (state) {
                if (!state.id) {
                    return state.text;
                }
                var baseUrl = state.element.getAttribute('data-image');
                if(!baseUrl) return state.text;
                
                var $state = $(
                    '<span><img src="' + baseUrl + '" class="img-flag" style="width: 20px; height: 20px; margin-right: 10px;" /> ' + state.text + '</span>'
                );
                return $state;
            };

            $('.select2-icons').select2({
                templateResult: formatState,
                templateSelection: formatState,
                dropdownParent: $('body') // Garante que o dropdown funcione dentro de modais
            });
            
            // Re-inicializar select2 quando o modal for aberto para garantir renderização correta
            $('.modal').on('shown.bs.modal', function () {
                $(this).find('.select2-icons').select2({
                    templateResult: formatState,
                    templateSelection: formatState,
                    dropdownParent: $(this)
                });
            });
        });
    </script>

    <div class="modal fade" id="createBannerCarouselModal" tabindex="-1" aria-labelledby="createBannerCarouselLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createBannerCarouselLabel">Novo Banner Carrossel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="type" value="lobby_carousel">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" name="titulo" required>
                        </div>
                        <div class="mb-3">
                            <label for="img" class="form-label">Imagem</label>
                            <input type="file" class="form-control" name="img" required>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </select>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-success">Criar Banner</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createBannerSidebarModal" tabindex="-1" aria-labelledby="createBannerSidebarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createBannerSidebarLabel">Novo Banner Sidebar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="type" value="lobby_sidebar_banner">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" name="titulo" required>
                        </div>
                        <div class="mb-3">
                            <label for="img" class="form-label">Imagem</label>
                            <input type="file" class="form-control" name="img" required>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="targetValue" class="form-label">Ação do Banner (Target)</label>
                            <select class="form-select" name="targetValue">
                                <option value="">Selecione...</option>
                                <?php foreach ($targetValues as $key => $target): ?>
                                    <option value='<?= $target['value']; ?>'><?= $target['label']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="defaultIconUrl" class="form-label">Ícone Padrão</label>
                            <select class="form-select select2-icons" name="defaultIconUrl" style="width: 100%;">
                                <option value="">Selecione...</option>
                                <?php foreach ($defaultIcons as $icon): ?>
                                    <option value="<?= $icon; ?>" data-image="<?= $icon; ?>"><?= basename($icon); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-success">Criar Banner</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($toastType) && isset($toastMessage)): ?>
        <script>
            window.showToast("<?= $toastType; ?>", "<?= $toastMessage; ?>");
        </script>
    <?php endif; ?>
</body>
</html>
