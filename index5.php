<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="/sd.png">
    <title>DNS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="twoj_css.css?v=2">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php require_once 'header.php'; ?>

<main class="container">
    <section class="sekcja1">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Badanie adresów IP i DNS</h4>
            </div>
            <div class="card-body p-4">
                <ul class="list-group list-group-flush mb-4">
                    <li class="list-group-item"><strong>IP serwera pbs.edu.pl:</strong> <span class="text-primary"><?php echo gethostbyname('pbs.edu.pl'); ?></span></li>
                    <li class="list-group-item"><strong>Twoje IP (gościa):</strong> <span class="text-primary"><?php echo $_SERVER["REMOTE_ADDR"]; ?></span></li>
                    <li class="list-group-item"><strong>Host dla 8.8.8.8:</strong> <span class="text-primary"><?php echo gethostbyaddr("8.8.8.8"); ?></span></li>
                    <li class="list-group-item"><strong>Host routera gościa:</strong> <span class="text-primary"><?php echo gethostbyaddr($_SERVER['REMOTE_ADDR']); ?></span></li>
                </ul>
                
                <h5 class="text-muted mb-3">Rekordy DNS dla pbs.edu.pl:</h5>
                <div class="p-3 rounded border" style="font-family: monospace; overflow-x: auto; font-size: 0.9em;">
                    <pre class="mb-0"><?php print_r(dns_get_record("pbs.edu.pl")); ?></pre>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once 'footer.php'; ?>

</body>
</html>