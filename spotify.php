<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$username_session = mysqli_real_escape_string($link, $_SESSION['username']);
$user_query = mysqli_query($link, "SELECT id FROM users WHERE username = '$username_session'");
if ($user_query && mysqli_num_rows($user_query) > 0) {
    $user_row = mysqli_fetch_assoc($user_query);
    $user_id = (int)$user_row['id'];
} else {
    $user_id = 0;
}

// 1. WGRYWANIE PIOSENKI
if (isset($_POST['upload_song']) && isset($_FILES['audio_file'])) {
    $title = mysqli_real_escape_string($link, $_POST['title']);
    $musician = mysqli_real_escape_string($link, $_POST['musician']);
    $lyrics = mysqli_real_escape_string($link, $_POST['lyrics']);
    $idmt = (int)$_POST['idmt'];
    
    $file_name = uniqid() . '_' . basename($_FILES["audio_file"]["name"]);
    $target_file = "songs/" . $file_name;

    if (move_uploaded_file($_FILES["audio_file"]["tmp_name"], $target_file)) {
        $query = "INSERT INTO song (title, musician, user_id, filename, lyrics, idmt) VALUES ('$title', '$musician', $user_id, '$file_name', '$lyrics', $idmt)";
        mysqli_query($link, $query);
    }
    header("Location: spotify.php");
    exit;
}

// 2. TWORZENIE PLAYLISTY
if (isset($_POST['create_playlist'])) {
    $pl_name = mysqli_real_escape_string($link, $_POST['pl_name']);
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    $query = "INSERT INTO playlistname (user_id, name, public) VALUES ($user_id, '$pl_name', $is_public)";
    mysqli_query($link, $query);
    header("Location: spotify.php");
    exit;
}

// 3. DODAWANIE DO PLAYLISTY
if (isset($_POST['add_to_playlist'])) {
    $idpl = (int)$_POST['idpl'];
    $ids = (int)$_POST['ids'];
    
    $check = mysqli_query($link, "SELECT * FROM playlistdatabase WHERE idpl = $idpl AND ids = $ids");
    if($check && mysqli_num_rows($check) == 0) {
        $query = "INSERT INTO playlistdatabase (idpl, ids) VALUES ($idpl, $ids)";
        mysqli_query($link, $query);
    }
    header("Location: spotify.php");
    exit;
}

// 4. USUWANIE WŁASNEJ PLAYLISTY
if (isset($_POST['delete_playlist'])) {
    $idpl = (int)$_POST['idpl'];
    $check = mysqli_query($link, "SELECT idpl FROM playlistname WHERE idpl = $idpl AND user_id = $user_id");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($link, "DELETE FROM playlistdatabase WHERE idpl = $idpl");
        mysqli_query($link, "DELETE FROM playlistname WHERE idpl = $idpl");
    }
    header("Location: spotify.php");
    exit;
}

// 5. USUWANIE WŁASNEJ PIOSENKI
if (isset($_POST['delete_song'])) {
    $ids = (int)$_POST['ids'];
    $check = mysqli_query($link, "SELECT filename FROM song WHERE ids = $ids AND user_id = $user_id");
    if (mysqli_num_rows($check) > 0) {
        $song_data = mysqli_fetch_assoc($check);
        @unlink("songs/" . $song_data['filename']);
        mysqli_query($link, "DELETE FROM playlistdatabase WHERE ids = $ids");
        mysqli_query($link, "DELETE FROM song WHERE ids = $ids");
    }
    header("Location: spotify.php");
    exit;
}

$gatunki = mysqli_query($link, "SELECT * FROM musictype");
$piosenki = mysqli_query($link, "SELECT s.*, m.name as gatunek FROM song s LEFT JOIN musictype m ON s.idmt = m.idmt ORDER BY s.datetime DESC");
$moje_playlisty = mysqli_query($link, "SELECT * FROM playlistname WHERE user_id = $user_id");
$publiczne_playlisty = mysqli_query($link, "SELECT p.*, u.username as autor FROM playlistname p JOIN users u ON p.user_id = u.id WHERE p.public = 1 AND p.user_id != $user_id");
?>
<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>MySpotify</title>
    <link rel="icon" type="image/png" href="/sd.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="twoj_css.css?v=2">
    <style>
        body { background-color: #121212 !important; color: #b3b3b3; }
        .spotify-card { background-color: #181818 !important; border: none !important; border-radius: 8px; transition: background-color 0.3s; }
        .spotify-card:hover { background-color: #282828 !important; }
        .spotify-header { background-color: transparent !important; border-bottom: 1px solid #282828 !important; color: #fff !important; font-weight: 700; font-size: 1.2rem; }
        .spotify-btn { background-color: #1DB954 !important; color: #fff !important; border: none !important; border-radius: 500px !important; font-weight: 700; padding: 10px 24px; transition: transform 0.1s, background-color 0.2s; }
        .spotify-btn:hover { background-color: #1ed760 !important; transform: scale(1.04); }
        .spotify-input { background-color: #282828 !important; border: none !important; color: #fff !important; border-radius: 4px; }
        .spotify-input:focus { box-shadow: 0 0 0 2px #1DB954 !important; }
        .table-spotify { color: #b3b3b3; }
        .table-spotify tbody tr { border-bottom: 1px solid #282828; transition: background-color 0.2s; }
        .table-spotify tbody tr:hover { background-color: #2a2a2a; }
        .table-spotify td { background-color: transparent !important; border: none !important; vertical-align: middle; }
        .text-white-hover:hover { color: #fff !important; }
        .list-group-item-spotify { background-color: transparent !important; border: none !important; color: #b3b3b3; padding: 10px 15px; border-radius: 4px !important; }
        .list-group-item-spotify:hover { background-color: #282828 !important; color: #fff; }
        audio { height: 40px; outline: none; border-radius: 20px; }
        audio::-webkit-media-controls-panel { background-color: #282828; }
        audio::-webkit-media-controls-current-time-display,
        audio::-webkit-media-controls-time-remaining-display { color: #b3b3b3; }
    </style>
</head>
<body>

<main class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <h2 class="text-white fw-bold mb-0 me-3"></h2>
            <span class="badge rounded-pill" style="background-color: #1DB954;">mySpotify</span>
        </div>
        <a href="home.php" class="btn btn-outline-light rounded-pill btn-sm fw-bold px-4 py-2">
            ← Wróć do Strony Głównej
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            
            <!-- 1. Biblioteka Playlist -->
            <div class="card spotify-card mb-4 shadow-sm">
                <div class="card-header spotify-header">Twoja Biblioteka</div>
                <ul class="list-group list-group-flush p-2">
                    <?php if ((!$moje_playlisty || mysqli_num_rows($moje_playlisty) == 0) && (!$publiczne_playlisty || mysqli_num_rows($publiczne_playlisty) == 0)): ?>
                        <li class="list-group-item-spotify text-center py-4 text-muted">Zbuduj swoją pierwszą playlistę.</li>
                    <?php endif; ?>

                    <?php if ($moje_playlisty) while($pl = mysqli_fetch_assoc($moje_playlisty)): ?>
                        <li class="list-group-item-spotify d-flex justify-content-between align-items-center mb-1">
                            <a href="player.php?idpl=<?php echo $pl['idpl']; ?>" class="text-decoration-none text-white fw-bold d-block flex-grow-1">
                                <?php echo htmlspecialchars($pl['name']); ?> 
                                <?php if($pl['public'] == 1) echo '<span class="badge bg-secondary ms-1" style="font-size: 0.6em;">Pub</span>'; ?>
                            </a>
                            <form method="post" class="m-0" onsubmit="return confirm('Na pewno usunąć całą playlistę?');">
                                <input type="hidden" name="idpl" value="<?php echo $pl['idpl']; ?>">
                                <button type="submit" name="delete_playlist" class="btn btn-link text-danger p-0 ms-2 text-decoration-none" title="Usuń playlistę">✕</button>
                            </form>
                        </li>
                    <?php endwhile; ?>
                    
                    <?php if ($publiczne_playlisty) while($pl = mysqli_fetch_assoc($publiczne_playlisty)): ?>
                        <li class="list-group-item-spotify d-flex justify-content-between align-items-center mb-1">
                            <a href="player.php?idpl=<?php echo $pl['idpl']; ?>" class="text-decoration-none d-block flex-grow-1" style="color: #1DB954;">
                                <?php echo htmlspecialchars($pl['name']); ?>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Utworzone przez: <?php echo $pl['autor']; ?></small>
                            </a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>

            <!-- 2. Stwórz Playlistę -->
            <div class="card spotify-card mb-4 shadow-sm">
                <div class="card-header spotify-header">Nowa Playlista</div>
                <div class="card-body">
                    <form method="post">
                        <input type="text" name="pl_name" class="form-control spotify-input mb-3" placeholder="Moja playlista #1" required>
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="is_public" id="isPublic" value="1">
                            <label class="form-check-label text-white-hover" for="isPublic">Udostępnij jako publiczną</label>
                        </div>
                        <button type="submit" name="create_playlist" class="btn btn-outline-light rounded-pill w-100 fw-bold">Utwórz playlistę</button>
                    </form>
                </div>
            </div>

            <!-- 3. Dodaj Utwór -->
            <div class="card spotify-card mb-4 shadow-sm">
                <div class="card-header spotify-header">Dodaj utwór do bazy</div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <input type="text" name="title" class="form-control spotify-input mb-3" placeholder="Tytuł utworu" required>
                        <input type="text" name="musician" class="form-control spotify-input mb-3" placeholder="Wykonawca" required>
                        <select name="idmt" class="form-select spotify-input mb-3" required>
                            <option value="">Wybierz gatunek...</option>
                            <?php if ($gatunki) while($g = mysqli_fetch_assoc($gatunki)): ?>
                                <option value="<?php echo $g['idmt']; ?>"><?php echo $g['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                        <textarea name="lyrics" class="form-control spotify-input mb-3" placeholder="Tekst piosenki (opcjonalnie)" rows="2"></textarea>
                        <input type="file" name="audio_file" accept=".mp3" class="form-control spotify-input mb-4" required>
                        <button type="submit" name="upload_song" class="btn spotify-btn w-100">Wgraj utwór</button>
                    </form>
                </div>
            </div>
            
        </div>

        <!-- Prawa kolumna: Główna baza utworów -->
        <div class="col-md-8">
            <div class="card spotify-card shadow-sm p-3">
                <div class="card-header spotify-header border-0 pb-0">Wszystkie Utwory</div>
                <div class="card-body p-0 mt-3">
                    <div class="table-responsive">
                        <table class="table table-spotify w-100 m-0">
                            <thead>
                                <tr style="border-bottom: 1px solid #282828;">
                                    <th class="text-muted fw-normal pb-3 ps-3">Tytuł</th>
                                    <th class="text-muted fw-normal pb-3">Odtwarzacz</th>
                                    <th class="text-muted fw-normal pb-3 ps-8">Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$piosenki || mysqli_num_rows($piosenki) == 0): ?>
                                    <tr><td colspan="3" class="text-center py-5">Brak muzyki. Bądź pierwszy i wgraj coś!</td></tr>
                                <?php endif; ?>

                                <?php if ($piosenki) while($song = mysqli_fetch_assoc($piosenki)): ?>
                                <tr>
                                    <td class="ps-3 py-3">
                                        <div class="text-white fw-bold" style="font-size: 1.05rem;"><?php echo htmlspecialchars($song['title']); ?></div>
                                        <div style="font-size: 0.85rem;"><?php echo htmlspecialchars($song['musician']); ?> • <?php echo $song['gatunek']; ?></div>
                                    </td>
                                    <td class="py-3">
                                        <audio controls src="songs/<?php echo $song['filename']; ?>" preload="none"></audio>
                                    </td>
                                    <td class="pe-3 py-3 text-end align-middle">
                                        <div class="d-flex justify-content-end align-items-center">
                                            <form method="post" class="d-flex me-2 m-0">
                                                <input type="hidden" name="ids" value="<?php echo $song['ids']; ?>">
                                                <select name="idpl" class="form-select form-select-sm spotify-input me-2" style="width: 140px;" required>
                                                    <option value="">Do playlisty...</option>
                                                    <?php 
                                                    if ($moje_playlisty) {
                                                        mysqli_data_seek($moje_playlisty, 0);
                                                        while($mpl = mysqli_fetch_assoc($moje_playlisty)): 
                                                    ?>
                                                        <option value="<?php echo $mpl['idpl']; ?>"><?php echo htmlspecialchars($mpl['name']); ?></option>
                                                    <?php 
                                                        endwhile; 
                                                    }
                                                    ?>
                                                </select>
                                                <button type="submit" name="add_to_playlist" class="btn btn-sm spotify-btn" style="padding: 4px 12px;">+</button>
                                            </form>

                                            <?php if($song['user_id'] == $user_id): ?>
                                            <form method="post" class="m-0" onsubmit="return confirm('Na pewno usunąć ten utwór ze Spotify?');">
                                                <input type="hidden" name="ids" value="<?php echo $song['ids']; ?>">
                                                <button type="submit" name="delete_song" class="btn btn-sm btn-outline-danger border-0" title="Usuń utwór">🗑️</button>
                                            </form>
                                            <?php else: ?>
                                            <div style="width: 32px;"></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Automatyczne ściszenie wszystkich odtwarzaczy na liście do 50%
    document.querySelectorAll('audio').forEach(function(audioElement) {
        audioElement.volume = 0.3;
    });
</script>
</body>
</html>