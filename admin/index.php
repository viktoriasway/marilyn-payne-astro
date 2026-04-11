<?php
session_start();

// ── CREDENTIALS — change these before going live ──────────────────────────
define('ADMIN_USER', 'holly');
define('ADMIN_PASS', 'Applewood2026!');
// ──────────────────────────────────────────────────────────────────────────

define('SHOWS_JSON', __DIR__ . '/../pdfs/shows.json');
define('PDFS_DIR',   __DIR__ . '/../pdfs/');

// Show labels used in the Results dropdown — matches data-show in shows.html
$show_options = [
  'schooling-2026-may-13'  => 'Schooling Show — May 13',
  'schooling-2026-jun-17'  => 'Schooling Show — June 17',
  'schooling-2026-jul-08'  => 'Schooling Show — July 8',
  'schooling-2026-aug-05'  => 'Schooling Show — August 5',
  'schooling-2026-sep-09'  => 'Schooling Show — September 9',
  'schooling-2026-oct-10'  => 'Schooling Show — October 10',
  'mini-event-2026-jul-15' => 'Mini Event — July 15',
  'mini-event-2026-aug-12' => 'Mini Event — August 12',
  'yeh-2026-jul-15'        => 'Young Event Horse — July 15',
  'yeh-2026-aug-12'        => 'Young Event Horse — August 12',
];

// Handle login / logout
if (isset($_POST['login'])) {
  if ($_POST['username'] === ADMIN_USER && $_POST['password'] === ADMIN_PASS) {
    $_SESSION['authed'] = true;
  } else {
    $error = 'Incorrect username or password.';
  }
}
if (isset($_GET['logout'])) {
  session_destroy();
  header('Location: /admin/');
  exit;
}

$authed = !empty($_SESSION['authed']);

// Load / save shows data
function load_shows() {
  $data = file_get_contents(SHOWS_JSON);
  return json_decode($data, true) ?: ['ride_times' => [], 'results' => []];
}
function save_shows($data) {
  file_put_contents(SHOWS_JSON, json_encode($data, JSON_PRETTY_PRINT));
}

// Handle deletes (processed here so no separate delete.php needed)
if ($authed && isset($_POST['delete_type'])) {
  $shows_d = load_shows();
  if (!is_array($shows_d['results'])) $shows_d['results'] = [];

  if ($_POST['delete_type'] === 'ride_times') {
    $index = (int)($_POST['index'] ?? -1);
    if (isset($shows_d['ride_times'][$index])) {
      array_splice($shows_d['ride_times'], $index, 1);
      save_shows($shows_d);
    }
  } elseif ($_POST['delete_type'] === 'results') {
    $slug = $_POST['slug'] ?? '';
    if (isset($shows_d['results'][$slug])) {
      unset($shows_d['results'][$slug]);
      save_shows($shows_d);
    }
  }
  header('Location: /admin/?msg=del');
  exit;
}

$shows = load_shows();
$message = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin — Marilyn Payne</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f3f3f3; color: #333; line-height: 1.5; }
    a { color: #72133E; text-decoration: none; }
    a:hover { text-decoration: underline; }

    .topbar { background: #72133E; color: #fff; padding: 0.9rem 2rem; display: flex; align-items: center; justify-content: space-between; }
    .topbar h1 { font-size: 1.1rem; font-weight: 600; letter-spacing: 0.02em; }
    .topbar a { color: #fff; font-size: 0.85rem; opacity: 0.8; }
    .topbar a:hover { opacity: 1; text-decoration: none; }

    .wrap { max-width: 800px; margin: 2.5rem auto; padding: 0 1.5rem; }

    /* Login */
    .login-box { background: #fff; border-radius: 8px; box-shadow: 0 2px 16px rgba(0,0,0,0.10); padding: 2.5rem; max-width: 380px; margin: 5rem auto; }
    .login-box h2 { font-size: 1.4rem; margin-bottom: 1.5rem; color: #72133E; }
    .login-box label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: #555; }
    .login-box input { width: 100%; padding: 0.6rem 0.8rem; border: 1.5px solid #ccc; border-radius: 6px; font-size: 0.95rem; margin-bottom: 1rem; }
    .login-box input:focus { outline: none; border-color: #72133E; }
    .login-box .error { color: #b00; font-size: 0.88rem; margin-bottom: 0.75rem; }

    /* Buttons */
    .btn { display: inline-block; padding: 0.5rem 1.2rem; border-radius: 6px; font-size: 0.88rem; font-weight: 600; cursor: pointer; border: none; transition: background 0.2s; }
    .btn-brand { background: #72133E; color: #fff; }
    .btn-brand:hover { background: #56102f; }
    .btn-del { background: transparent; color: #b00; border: 1.5px solid #e0a0a0; font-size: 0.8rem; padding: 0.25rem 0.7rem; }
    .btn-del:hover { background: #fff0f0; border-color: #b00; }

    /* Sections */
    .section { background: #fff; border-radius: 8px; box-shadow: 0 1px 8px rgba(0,0,0,0.07); padding: 2rem; margin-bottom: 2rem; }
    .section h2 { font-size: 1.15rem; font-weight: 700; color: #72133E; margin-bottom: 0.35rem; }
    .section .desc { font-size: 0.88rem; color: #777; margin-bottom: 1.25rem; }

    /* Current list */
    .pdf-list { list-style: none; margin-bottom: 1.5rem; }
    .pdf-list li { display: flex; align-items: center; justify-content: space-between; padding: 0.6rem 0; border-bottom: 1px solid #ebebeb; gap: 1rem; font-size: 0.92rem; }
    .pdf-list li:last-child { border-bottom: none; }
    .pdf-list .pdf-name { font-weight: 600; }
    .pdf-list .pdf-file { color: #888; font-size: 0.82rem; }
    .empty-note { font-size: 0.88rem; color: #aaa; font-style: italic; margin-bottom: 1rem; }

    /* Upload form */
    .upload-form { border-top: 1px solid #ebebeb; padding-top: 1.25rem; margin-top: 0.25rem; }
    .upload-form h3 { font-size: 0.92rem; font-weight: 700; margin-bottom: 0.75rem; color: #444; }
    .field { margin-bottom: 0.85rem; }
    .field label { display: block; font-size: 0.82rem; font-weight: 600; color: #555; margin-bottom: 0.3rem; }
    .field input[type=text], .field select { width: 100%; padding: 0.5rem 0.7rem; border: 1.5px solid #ccc; border-radius: 6px; font-size: 0.92rem; }
    .field input[type=text]:focus, .field select:focus { outline: none; border-color: #72133E; }
    .field input[type=file] { font-size: 0.88rem; }

    /* Message */
    .msg-ok  { background: #eafaf0; color: #1a6e3a; border: 1px solid #b2dfca; border-radius: 6px; padding: 0.65rem 1rem; margin-bottom: 1.5rem; font-size: 0.9rem; }
    .msg-err { background: #fff0f0; color: #b00; border: 1px solid #f0b0b0; border-radius: 6px; padding: 0.65rem 1rem; margin-bottom: 1.5rem; font-size: 0.9rem; }
  </style>
</head>
<body>

<?php if (!$authed): ?>
<!-- ── LOGIN FORM ── -->
<div class="topbar"><h1>Marilyn Payne — Admin</h1></div>
<div class="login-box">
  <h2>Sign In</h2>
  <?php if (!empty($error)): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  <form method="post">
    <label>Username</label>
    <input type="text" name="username" autocomplete="username" required>
    <label>Password</label>
    <input type="password" name="password" autocomplete="current-password" required>
    <button type="submit" name="login" class="btn btn-brand" style="width:100%;padding:0.65rem;">Sign In</button>
  </form>
</div>

<?php else: ?>
<!-- ── DASHBOARD ── -->
<div class="topbar">
  <h1>Marilyn Payne — PDF Admin</h1>
  <a href="?logout=1">Sign out</a>
</div>
<div class="wrap">

  <?php if ($message === 'ok'): ?>
    <p class="msg-ok">&#10003; Saved successfully.</p>
  <?php elseif ($message === 'del'): ?>
    <p class="msg-ok">&#10003; Entry removed.</p>
  <?php elseif ($message === 'err'): ?>
    <p class="msg-err">&#9888; Something went wrong. Please try again.</p>
  <?php endif; ?>

  <!-- ── RIDE TIMES ── -->
  <div class="section">
    <h2>Ride Times</h2>
    <p class="desc">These appear on the <a href="/ride-times/" target="_blank">Ride Times page</a>. Add one per active show. Delete when the show is over.</p>

    <?php if (empty($shows['ride_times'])): ?>
      <p class="empty-note">No ride times currently posted.</p>
    <?php else: ?>
      <ul class="pdf-list">
        <?php foreach ($shows['ride_times'] as $i => $rt): ?>
          <li>
            <div>
              <div class="pdf-name"><?= htmlspecialchars($rt['event']) ?></div>
              <div class="pdf-file"><a href="<?= htmlspecialchars($rt['pdf']) ?>" target="_blank">View PDF</a></div>
            </div>
            <form method="post" action="/admin/">
              <input type="hidden" name="delete_type" value="ride_times">
              <input type="hidden" name="index" value="<?= $i ?>">
              <button type="submit" class="btn btn-del" onclick="return confirm('Remove this ride times entry?')">Delete</button>
            </form>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <div class="upload-form">
      <h3>Add Ride Times PDF</h3>
      <form method="post" action="/admin/upload.php" enctype="multipart/form-data">
        <input type="hidden" name="type" value="ride_times">
        <div class="field">
          <label>Show</label>
          <select name="show_slug" required>
            <option value="">— Select a show —</option>
            <?php foreach ($show_options as $slug => $label): ?>
              <option value="<?= $slug ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>PDF File</label>
          <input type="file" name="pdf" accept=".pdf" required>
        </div>
        <button type="submit" class="btn btn-brand">Upload &amp; Post</button>
      </form>
    </div>
  </div>

  <!-- ── RESULTS ── -->
  <div class="section">
    <h2>Show Results</h2>
    <p class="desc">Select the show, upload the results PDF. The Results button for that show will go live on the <a href="/shows/" target="_blank">Shows page</a>.</p>

    <?php
    $results = is_array($shows['results']) ? $shows['results'] : (array)$shows['results'];
    ?>
    <?php if (empty($results)): ?>
      <p class="empty-note">No results currently posted.</p>
    <?php else: ?>
      <ul class="pdf-list">
        <?php foreach ($results as $slug => $pdf): ?>
          <li>
            <div>
              <div class="pdf-name"><?= htmlspecialchars($show_options[$slug] ?? $slug) ?></div>
              <div class="pdf-file"><a href="<?= htmlspecialchars($pdf) ?>" target="_blank">View PDF</a></div>
            </div>
            <form method="post" action="/admin/">
              <input type="hidden" name="delete_type" value="results">
              <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">
              <button type="submit" class="btn btn-del" onclick="return confirm('Remove results for this show?')">Delete</button>
            </form>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <div class="upload-form">
      <h3>Add Results PDF</h3>
      <form method="post" action="/admin/upload.php" enctype="multipart/form-data">
        <input type="hidden" name="type" value="results">
        <div class="field">
          <label>Show</label>
          <select name="show_slug" required>
            <option value="">— Select a show —</option>
            <?php foreach ($show_options as $slug => $label): ?>
              <option value="<?= $slug ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>PDF File</label>
          <input type="file" name="pdf" accept=".pdf" required>
        </div>
        <button type="submit" class="btn btn-brand">Upload &amp; Post</button>
      </form>
    </div>
  </div>

</div><!-- /wrap -->
<?php endif; ?>
</body>
</html>
