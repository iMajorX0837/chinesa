<?php include 'partials/html.php' ?>

<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
include_once "services/database.php";
include_once 'logs/registrar_logs.php';
include_once "services/funcao.php";
include_once "services/crud.php";
include_once "services/crud-adm.php";
include_once 'services/checa_login_adm.php';
include_once "validar_2fa.php";
include_once "services/CSRF_Protect.php";
$csrf = new CSRF_Protect();

checa_login_adm();

// Funções iGameWIN
function get_igamewin_config()
{
    global $mysqli;
    $qry = "SELECT * FROM igamewin WHERE id = 1";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result);
}

function update_igamewin_config($data)
{
    global $mysqli;
    $qry = $mysqli->prepare("UPDATE igamewin SET 
        url = ?, 
        agent_code = ?, 
        agent_token = ?, 
        ativo = ?
        WHERE id = 1");

    $qry->bind_param(
        "sssi",
        $data['url'],
        $data['agent_code'],
        $data['agent_token'],
        $data['ativo']
    );
    return $qry->execute();
}

$toastType = null;
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_igamewin'])) {
        $data = [
            'url' => $_POST['url_igamewin'],
            'agent_code' => $_POST['agent_code_igamewin'],
            'agent_token' => $_POST['agent_token_igamewin'],
            'ativo' => intval($_POST['ativo_igamewin']),
        ];

        if (update_igamewin_config($data)) {
            $toastType = 'success';
            $toastMessage = 'Credenciais do iGameWIN atualizadas com sucesso!';
        } else {
            $toastType = 'error';
            $toastMessage = 'Erro ao atualizar as credenciais do iGameWIN. Tente novamente.';
        }
    }
}

$igamewin_config = get_igamewin_config();
?>

<head>
    <?php $title = "Configurações de APIs (Clones)"; ?>
    <?php include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
</head>

<body>

    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                
                <!-- iGameWIN Configuration -->
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Gerenciamento de Credenciais (iGameWIN)</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="update_igamewin">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">URL</h5>
                                                    <input type="text" name="url_igamewin" class="form-control" value="<?= $igamewin_config['url'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">Agent Code</h5>
                                                    <div class="input-group">
                                                        <input type="password" id="agent_code_igamewin" name="agent_code_igamewin" class="form-control" value="<?= $igamewin_config['agent_code'] ?>" required>
                                                        <span class="input-group-text" onclick="togglePassword('agent_code_igamewin', this)">
                                                            <i class="ti ti-eye"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">Agent Token</h5>
                                                    <div class="input-group">
                                                        <input type="password" id="agent_token_igamewin" name="agent_token_igamewin" class="form-control" value="<?= $igamewin_config['agent_token'] ?>" required>
                                                        <span class="input-group-text" onclick="togglePassword('agent_token_igamewin', this)">
                                                            <i class="ti ti-eye"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title"><i class="iconoir-check-circle"></i> Ativo</h5>
                                                    <select name="ativo_igamewin" class="form-select" required>
                                                        <option value="1" <?= $igamewin_config['ativo'] == 1 ? 'selected' : '' ?>>Sim</option>
                                                        <option value="0" <?= $igamewin_config['ativo'] == 0 ? 'selected' : '' ?>>Não</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </div>
                                    <div class="text-center">
                                        <button type="submit" class="btn btn-success">Salvar Configurações iGameWIN</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            
            <?php include 'partials/endbar.php' ?>
            <?php include 'partials/footer.php' ?>
            
        </div>
    </div>

    <div id="toastPlacement" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>

    <script>
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
    
    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            const iconElement = icon.querySelector('i');

            if (input.type === "password") {
                input.type = "text";
                iconElement.classList.remove('ti-eye');
                iconElement.classList.add('ti-eye-off');
            } else {
                input.type = "password";
                iconElement.classList.remove('ti-eye-off');
                iconElement.classList.add('ti-eye');
            }
        }
    </script>

    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>
</html>
