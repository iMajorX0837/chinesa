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

# Função para buscar os dados atuais da tabela config
function get_afiliados_config()
{
    global $mysqli;
    ensure_facebookads_pixels_column();
    $qry = "SELECT * FROM config WHERE id=1";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result);
}

# Função para atualizar os dados da tabela config
function update_config($data)
{
    global $mysqli;

    $qry = $mysqli->prepare("UPDATE config SET googleAnalytics = ? WHERE id = 1");
    $qry->bind_param("s", $data['googleads']);
    if (!$qry->execute()) {
        return false;
    }

    return save_facebook_pixels_to_config($data['facebook_pixels']);
}

# Se o formulário for enviado, atualizar os dados
$toastType = null;
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $facebookPixels = isset($_POST['facebookads_pixels']) && is_array($_POST['facebookads_pixels'])
        ? $_POST['facebookads_pixels']
        : [];

    $data = [
        'googleads' => $_POST['googleads'],
        'facebook_pixels' => $facebookPixels,
    ];

    if (update_config($data)) {
        $toastType = 'success';
        $toastMessage = admin_t('toast_config_updated');
    } else {
        $toastType = 'error';
        $toastMessage = admin_t('toast_config_error');
    }
}

# Buscar os dados atuais
$config = get_afiliados_config();
$fbPixels = get_facebook_pixels_from_config($config);
if (empty($fbPixels)) {
    $fbPixels = [''];
}
?>

<head>
    <?php $title = admin_t('page_images_title');
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
                                <h4 class="card-title"><?= admin_t('page_images_title') ?></h4>
                            </div>

                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="iconoir-user"></i> Google ADS
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        Coloque apenas o ID do trackeamento GoogleADS.
                                                    </p>
                                                    <input type="text" name="googleads" class="form-control"
                                                        value="<?= htmlspecialchars($config['googleAnalytics'] ?? '', ENT_QUOTES) ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <div>
                                                            <h5 class="card-title mb-1">
                                                                <i class="iconoir-group"></i> Facebook ADS (Pixels)
                                                            </h5>
                                                            <p class="card-subtitle text-muted mb-0">
                                                                Adicione quantos pixels do Facebook quiser. Informe apenas o ID de cada pixel.
                                                            </p>
                                                        </div>
                                                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn-add-facebook-pixel">
                                                            + Adicionar pixel
                                                        </button>
                                                    </div>

                                                    <div id="facebook-pixels-list">
                                                        <?php foreach ($fbPixels as $index => $pixelId): ?>
                                                            <div class="input-group mb-2 facebook-pixel-row">
                                                                <span class="input-group-text pixel-index">#<?= $index + 1 ?></span>
                                                                <input type="text"
                                                                    name="facebookads_pixels[]"
                                                                    class="form-control"
                                                                    value="<?= htmlspecialchars($pixelId, ENT_QUOTES) ?>"
                                                                    placeholder="Ex: 123456789012345">
                                                                <button type="button" class="btn btn-outline-danger btn-remove-pixel" title="Remover pixel">
                                                                    &times;
                                                                </button>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-success"><?= admin_t('button_save_settings') ?></button>
                                    </div>
                                </form>
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

    <!-- Javascript -->
    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>

    <script>
        function renumberFacebookPixels() {
            document.querySelectorAll('#facebook-pixels-list .facebook-pixel-row').forEach(function (row, index) {
                var label = row.querySelector('.pixel-index');
                if (label) {
                    label.textContent = '#' + (index + 1);
                }
            });
        }

        function bindRemovePixelButtons() {
            document.querySelectorAll('.btn-remove-pixel').forEach(function (button) {
                button.onclick = function () {
                    var rows = document.querySelectorAll('#facebook-pixels-list .facebook-pixel-row');
                    if (rows.length <= 1) {
                        rows[0].querySelector('input').value = '';
                        return;
                    }
                    button.closest('.facebook-pixel-row').remove();
                    renumberFacebookPixels();
                };
            });
        }

        document.getElementById('btn-add-facebook-pixel').addEventListener('click', function () {
            var list = document.getElementById('facebook-pixels-list');
            var row = document.createElement('div');
            row.className = 'input-group mb-2 facebook-pixel-row';
            row.innerHTML = `
                <span class="input-group-text pixel-index"></span>
                <input type="text" name="facebookads_pixels[]" class="form-control" placeholder="Ex: 123456789012345">
                <button type="button" class="btn btn-outline-danger btn-remove-pixel" title="Remover pixel">&times;</button>
            `;
            list.appendChild(row);
            renumberFacebookPixels();
            bindRemovePixelButtons();
        });

        bindRemovePixelButtons();

        function showToast(type, message) {
            var toastPlacement = document.getElementById('toastPlacement');
            var toast = document.createElement('div');
            toast.className = `toast align-items-center bg-light border-0 fade show`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
                <div class="toast-header">
                    <h5 class="me-auto my-0">Atualização</h5>
                    <small>Agora</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">${message}</div>
            `;
            toastPlacement.appendChild(toast);

            var bootstrapToast = new bootstrap.Toast(toast);
            bootstrapToast.show();

            setTimeout(function () {
                bootstrapToast.hide();
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }
    </script>

    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>
</html>
