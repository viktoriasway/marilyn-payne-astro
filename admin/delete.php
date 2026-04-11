<?php
session_start();
if (empty($_SESSION['authed'])) { header('Location: /admin/'); exit; }

define('SHOWS_JSON', __DIR__ . '/../pdfs/shows.json');

function load_shows() {
  $data = file_get_contents(SHOWS_JSON);
  return json_decode($data, true) ?: ['ride_times' => [], 'results' => []];
}
function save_shows($data) {
  file_put_contents(SHOWS_JSON, json_encode($data, JSON_PRETTY_PRINT));
}

$type = $_POST['type'] ?? '';
$shows = load_shows();
if (!isset($shows['results']) || !is_array($shows['results'])) {
  $shows['results'] = [];
}

if ($type === 'ride_times') {
  $index = (int)($_POST['index'] ?? -1);
  if (isset($shows['ride_times'][$index])) {
    array_splice($shows['ride_times'], $index, 1);
    save_shows($shows);
    header('Location: /admin/?msg=del'); exit;
  }
} elseif ($type === 'results') {
  $slug = $_POST['slug'] ?? '';
  if (isset($shows['results'][$slug])) {
    unset($shows['results'][$slug]);
    save_shows($shows);
    header('Location: /admin/?msg=del'); exit;
  }
}

header('Location: /admin/?msg=err');
exit;
