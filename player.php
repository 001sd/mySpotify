<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['idpl']) || empty($_GET['idpl'])) {
    die("<body style='background-color: #121212; color: white; text-align: center; margin-top: 50px; font-family: sans-serif;'><h2>Nie wybrano playlisty.</h2><br><a href='spotify.php' style='color: #1DB954;'>Wróć do MySpotify</a></body>");
}

$idpl = (int)$_GET['idpl'];

$username_session = mysqli_real_escape_string($link, $_SESSION['username']);
$user_query = mysqli_query($link, "SELECT id FROM users WHERE username = '$username_session'");
$user_id = ($user_query && mysqli_num_rows($user_query) > 0) ? (int)mysqli_fetch_assoc($user_query)['id'] : 0;

$playlist_query = mysqli_query($link, "SELECT name, user_id FROM playlistname WHERE idpl = $idpl");
$playlist = mysqli_fetch_assoc($playlist_query);

if (!$playlist) {
    die("<body style='background-color: #121212; color: white; text-align: center; margin-top: 50px; font-family: sans-serif;'><h2>Taka playlista nie istnieje.</h2><br><a href='spotify.php' style='color: #1DB954;'>Wróć do MySpotify</a></body>");
}

$is_owner = ($playlist['user_id'] == $user_id);

// OBSŁUGA ZAPISU KOLEJNOŚCI PO PRZECIĄGNIĘCIU (AJAX)
if (isset($_POST['update_order']) && $is_owner) {
    $new_order = json_decode($_POST['order'], true);
    if (is_array($new_order) && count($new_order) > 0) {
        mysqli_query($link, "DELETE FROM playlistdatabase WHERE idpl = $idpl");
        foreach ($new_order as $ids) {
            $ids = (int)$ids;
            mysqli_query($link, "INSERT INTO playlistdatabase (idpl, ids) VALUES ($idpl, $ids)");
        }
    }
    exit; // Przerywa dalsze ładowanie HTML, to tylko odpowiedź dla skryptu JS
}

// OBSŁUGA USUWANIA Z PLAYLISTY
if (isset($_POST['remove_from_playlist']) && $is_owner) {
    $remove_ids = (int)$_POST['remove_ids'];
    mysqli_query($link, "DELETE FROM playlistdatabase WHERE idpl = $idpl AND ids = $remove_ids");
    header("Location: player.php?idpl=$idpl");
    exit;
}

$songs_query = mysqli_query($link, "SELECT s.ids, s.title, s.musician, s.filename FROM playlistdatabase pld JOIN song s ON pld.ids = s.ids WHERE pld.idpl = $idpl");

$playlist_data = array();
if ($songs_query) {
    while($row = mysqli_fetch_assoc($songs_query)) {
        $playlist_data[] = array(
            'ids' => $row['ids'],
            'title' => $row['title'],
            'artist' => $row['musician'],
            'file' => 'songs/' . $row['filename']
        );
    }
}

$bezpieczna_nazwa = htmlspecialchars($playlist['name'] ?? 'Nieznana');
$ilosc_utworow = count($playlist_data);
?>
<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Odtwarzacz - <?php echo $bezpieczna_nazwa; ?></title>
    <link rel="icon" type="image/png" href="/sd.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Biblioteka SortableJS do przeciągania elementów -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <style>
        body { background-color: #121212 !important; color: #b3b3b3; display: flex; flex-direction: column; min-height: 100vh; }
        .spotify-card { background-color: #181818 !important; border: none !important; border-radius: 12px; }
        .control-btn { background-color: transparent; border: none; color: #b3b3b3; font-size: 2.5rem; cursor: pointer; transition: color 0.2s, transform 0.1s; }
        .control-btn:hover { color: #fff; transform: scale(1.1); }
        .control-btn:disabled { color: #333; cursor: not-allowed; transform: none; }
        audio { width: 100%; height: 45px; outline: none; border-radius: 20px; margin-top: 10px; }
        audio::-webkit-media-controls-panel { background-color: #282828; }
        audio::-webkit-media-controls-current-time-display, audio::-webkit-media-controls-time-remaining-display { color: #b3b3b3; }
        .album-art { width: 220px; height: 220px; background: linear-gradient(135deg, #282828, #121212); margin: 0 auto; border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(0,0,0,0.6); }
        .album-art span { font-size: 5rem; }
        
        .track-item { cursor: pointer; transition: background-color 0.2s; border-radius: 6px; }
        .track-item:hover { background-color: #2a2a2a !important; }
        .drag-handle { cursor: grab; font-size: 1.2rem; padding-right: 10px; opacity: 0.5; transition: opacity 0.2s; }
        .drag-handle:hover { opacity: 1; }
    </style>
</head>
<body>

<div class="container mt-4">
    <a href="spotify.php" class="btn btn-outline-light rounded-pill btn-sm fw-bold px-4 py-2">← Wróć do MySpotify</a>
</div>

<main class="container d-flex flex-grow-1 align-items-center justify-content-center mb-5 mt-3">
    <div class="card spotify-card shadow-lg p-4 p-md-5 text-center" style="width: 100%; max-width: 500px;">
        <h6 class="text-success fw-bold mb-4 text-uppercase">PLAYLISTA: <?php echo $bezpieczna_nazwa; ?></h6>
        
        <?php if($ilosc_utworow > 0): ?>
            <div class="album-art mb-4"><span>🎵</span></div>
            <h3 id="track-title" class="text-white fw-bold mb-1">Tytuł utworu</h3>
            <p id="track-artist" class="text-muted mb-4" style="font-size: 1.1rem;">Wykonawca</p>
            
            <div class="d-flex justify-content-center align-items-center mb-3 gap-4">
                <button id="prev-btn" class="control-btn" onclick="prevTrack()" title="Poprzedni utwór">⏮</button>
                <button id="next-btn" class="control-btn" onclick="nextTrack()" title="Następny utwór">⏭</button>
            </div>
            
            <audio id="audio-player" controls controlsList="nodownload" autoplay></audio>
            
            <div id="track-counter" class="mt-4 mb-4 text-muted fw-bold" style="font-size: 0.85rem; letter-spacing: 1px;">
                Utwór 1 z <?php echo $ilosc_utworow; ?>
            </div>

            <!-- Lista utworów umożliwiająca klikanie i przeciąganie -->
            <div class="mt-4 text-start w-100">
                <h6 class="text-white mb-3 border-bottom border-secondary pb-2">Utwory na liście:</h6>
                <ul class="list-group list-group-flush" id="playlist-items">
                    <?php foreach($playlist_data as $index => $track): ?>
                        <li class="list-group-item bg-transparent text-white d-flex justify-content-between align-items-center border-secondary px-2 py-2 track-item"
                            data-ids="<?php echo $track['ids']; ?>"
                            data-title="<?php echo htmlspecialchars($track['title']); ?>"
                            data-artist="<?php echo htmlspecialchars($track['artist']); ?>"
                            data-file="<?php echo htmlspecialchars($track['file']); ?>"
                            onclick="playSpecific(this)">
                            
                            <div class="d-flex align-items-center">
                                <?php if($is_owner): ?>
                                    <span class="drag-handle" title="Chwyć i przenieś">≡</span>
                                <?php endif; ?>
                                <strong class="track-name"><?php echo htmlspecialchars($track['title']); ?></strong>
                                <span class="text-muted ms-1" style="font-size: 0.9em;">- <?php echo htmlspecialchars($track['artist']); ?></span>
                            </div>
                            
                            <?php if($is_owner): ?>
                            <form method="post" class="m-0" onclick="event.stopPropagation();" onsubmit="return confirm('Usunąć ten utwór z playlisty?');">
                                <input type="hidden" name="remove_ids" value="<?php echo $track['ids']; ?>">
                                <button type="submit" name="remove_from_playlist" class="btn btn-sm btn-link text-danger text-decoration-none fs-5 p-0 ms-2" title="Usuń z playlisty">🗑️</button>
                            </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

        <?php else: ?>
            <div class="py-5">
                <h4 class="text-white mb-3">Ta playlista jest pusta.</h4>
                <p class="text-muted">Wróć do biblioteki MySpotify i dodaj do niej kilka utworów.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    let playlist = <?php echo json_encode($playlist_data); ?>;
    
    if (playlist && playlist.length > 0) {
        let currentTrack = 0;
        const audioPlayer = document.getElementById('audio-player');
        const trackTitle = document.getElementById('track-title');
        const trackArtist = document.getElementById('track-artist');
        const trackCounter = document.getElementById('track-counter');
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        const playlistItemsContainer = document.getElementById('playlist-items');

        audioPlayer.volume = 0.3;

        function loadTrack(index, updateAudio = true) {
            if(updateAudio) {
                audioPlayer.src = playlist[index].file;
            }
            trackTitle.innerText = playlist[index].title;
            trackArtist.innerText = playlist[index].artist;
            trackCounter.innerText = "UTWÓR " + (index + 1) + " Z " + playlist.length;
            
            prevBtn.disabled = (index === 0);
            nextBtn.disabled = (index === playlist.length - 1);

            // Zaznacza na zielono aktualnie grający utwór na liście poniżej
            let items = playlistItemsContainer.children;
            for(let i = 0; i < items.length; i++) {
                let nameElement = items[i].querySelector('.track-name');
                if(i === index) {
                    nameElement.style.color = '#1DB954';
                    items[i].classList.add('active-playing');
                } else {
                    nameElement.style.color = 'white';
                    items[i].classList.remove('active-playing');
                }
            }
        }

        function playTrack() {
            var playPromise = audioPlayer.play();
            if (playPromise !== undefined) {
                playPromise.catch(function(error) { console.log("Czekam na interakcję.", error); });
            }
        }

        // Kliknięcie w konkretny utwór na liście
        function playSpecific(element) {
            let nodes = Array.from(playlistItemsContainer.children);
            let index = nodes.indexOf(element);
            if (index !== currentTrack) {
                currentTrack = index;
                loadTrack(currentTrack);
                playTrack();
            }
        }

        function nextTrack() {
            if (currentTrack < playlist.length - 1) {
                currentTrack++; loadTrack(currentTrack); playTrack();
            }
        }

        function prevTrack() {
            if (currentTrack > 0) {
                currentTrack--; loadTrack(currentTrack); playTrack();
            }
        }

        audioPlayer.addEventListener('ended', function() {
            if (currentTrack < playlist.length - 1) {
                nextTrack();
            } else {
                audioPlayer.currentTime = 0; 
                trackTitle.innerText = "Koniec playlisty"; 
                trackArtist.innerText = "-";
            }
        });

        // Inicjalizacja przeciągania utworów (tylko dla właściciela)
        <?php if($is_owner): ?>
        new Sortable(playlistItemsContainer, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function (evt) {
                // Przebudowanie tablicy playlist na podstawie nowego układu DOM
                let newPlaylist = [];
                let newCurrentTrack = currentTrack;
                let items = playlistItemsContainer.children;
                
                for (let i = 0; i < items.length; i++) {
                    newPlaylist.push({
                        ids: items[i].getAttribute('data-ids'),
                        title: items[i].getAttribute('data-title'),
                        artist: items[i].getAttribute('data-artist'),
                        file: items[i].getAttribute('data-file')
                    });
                    if (items[i].classList.contains('active-playing')) {
                        newCurrentTrack = i; // Aktualizuje indeks grającej piosenki
                    }
                }
                
                playlist = newPlaylist;
                currentTrack = newCurrentTrack;
                loadTrack(currentTrack, false); // Aktualizuje UI licznika bez przerywania odtwarzania

                // Zapisz nową kolejność do bazy danych w tle
                let order = playlist.map(item => item.ids);
                let formData = new FormData();
                formData.append('update_order', '1');
                formData.append('order', JSON.stringify(order));
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
            }
        });
        <?php endif; ?>

        loadTrack(currentTrack);
        playTrack();
    }
</script>
</body>
</html>