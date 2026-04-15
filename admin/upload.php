<?php
session_start();
if (empty($_SESSION['authed'])) { header('Location: /admin/'); exit; }

define('SHOWS_JSON', __DIR__ . '/../pdfs/shows.json');
define('PDFS_DIR',   __DIR__ . '/../pdfs/');

$valid_slugs = [
  'schooling-2026-may-13', 'schooling-2026-jun-17', 'schooling-2026-jul-08',
  'schooling-2026-aug-05', 'schooling-2026-sep-09', 'schooling-2026-oct-10',
  'mini-event-2026-jul-15', 'mini-event-2026-aug-12',
  'yeh-2026-jul-15', 'yeh-2026-aug-12',
];
$valid_prize_keys = ['schooling', 'mini-event', 'yeh'];

function load_shows() {
  $data = file_get_contents(SHOWS_JSON);
  return json_decode($data, true) ?: ['ride_times' => [], 'results' => []];
}
function save_shows($data) {
  file_put_contents(SHOWS_JSON, json_encode($data, JSON_PRETTY_PRINT));
}

$type = $_POST['type'] ?? '';

// Validate file upload
if (empty($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
  header('Location: /admin/?msg=err'); exit;
}
if ($_FILES['pdf']['size'] > 20 * 1024 * 1024) {
  header('Location: /admin/?msg=err'); exit; // 20MB max
}
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $_FILES['pdf']['tmp_name']);
finfo_close($finfo);
if ($mime !== 'application/pdf') {
  header('Location: /admin/?msg=err'); exit;
}

// Sanitize filename
$orig     = pathinfo($_FILES['pdf']['name'], PATHINFO_FILENAME);
$safe     = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $orig);
$safe     = trim($safe, '-');
$filename = $safe . '.pdf';
$dest     = PDFS_DIR . $filename;

if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $dest)) {
  header('Location: /admin/?msg=err'); exit;
}

$shows = load_shows();
if (!isset($shows['results'])     || !is_array($shows['results']))     $shows['results']     = [];
if (!isset($shows['prize_lists']) || !is_array($shows['prize_lists'])) $shows['prize_lists'] = [];

$show_labels = [
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

if ($type === 'ride_times') {
  $slug = $_POST['show_slug'] ?? '';
  if (!in_array($slug, $valid_slugs, true)) { header('Location: /admin/?msg=err'); exit; }
  $event = $show_labels[$slug];
  $shows['ride_times'][] = ['show_slug' => $slug, 'event' => $event, 'pdf' => '/pdfs/' . $filename];

} elseif ($type === 'results') {
  $slug = $_POST['show_slug'] ?? '';
  if (!in_array($slug, $valid_slugs, true)) { header('Location: /admin/?msg=err'); exit; }
  $shows['results'][$slug] = '/pdfs/' . $filename;

} elseif ($type === 'prize_list') {
  $key = $_POST['prize_key'] ?? '';
  if (!in_array($key, $valid_prize_keys, true)) { header('Location: /admin/?msg=err'); exit; }
  $shows['prize_lists'][$key] = '/pdfs/' . $filename;

} else {
  header('Location: /admin/?msg=err'); exit;
}

save_shows($shows);
header('Location: /admin/?msg=ok');
exit;
