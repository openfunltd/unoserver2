<?php

if (!$_FILES) {
    echo '<form method="post" enctype="multipart/form-data">
        <input type="file" name="file">
        <input type="submit" value="Upload">
    </form>';
    exit;
}
if (!file_exists($_FILES['file']['tmp_name'])) {
    echo 'No file uploaded';
    exit;
}
$data_dir = $_FILES['file']['tmp_name'] . '.dir';
mkdir($data_dir);
chdir($data_dir);
$source_file = $data_dir . '/' . $_FILES['file']['name'];
move_uploaded_file($_FILES['file']['tmp_name'], $source_file);

$convert_to = $_GET['convert-to'] ?? 'html';
if (!in_array($convert_to, ['pdf', 'html', 'docx', 'doc'])) {
    echo 'Invalid convert-to';
    exit;
}

$cmd = sprintf("soffice --headless --convert-to %s %s 2>&1 ",
    escapeshellarg($convert_to),
    escapeshellarg($source_file)
);
// 記錄每次轉檔的開始與耗時（寫到 php-fpm stderr，用 docker logs 看），
// 用來確認轉檔超過兩分鐘是不是會自然發生，再決定 timeout 要設多少
$log = [
    'id' => basename($data_dir),
    'ext' => strtolower(pathinfo($source_file, PATHINFO_EXTENSION)),
    'size' => filesize($source_file),
    'convert_to' => $convert_to,
];
error_log('unoserver2 convert-start ' . json_encode($log));
$start = microtime(true);
exec($cmd, $output, $return_var);
$log['seconds'] = round(microtime(true) - $start, 2);
$log['return'] = $return_var;
error_log('unoserver2 convert-end ' . json_encode($log));

$target_file = basename($source_file, pathinfo($source_file, PATHINFO_EXTENSION)) . $convert_to;
if ('html' != $convert_to) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($target_file) . '"');
    readfile($target_file);
    exit;
}
if (!file_exists($target_file)) {
    echo 'Failed to convert:';
    exit;
}
$content = file_get_contents($target_file);
$name = str_replace('.', '_', $target_file);
$content = preg_replace_callback("#({$name}_[^\"]*)#", function ($matches) {
    if (!file_exists($matches[1])) {
        return $matches[0];
    }
    $mime = mime_content_type($matches[1]);
    return sprintf("data:%s;base64,%s", $mime, base64_encode(file_get_contents($matches[1])));
}, $content);

system("rm -rf $data_dir");
// support gzip
if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
    header('Content-Encoding: gzip');
    $content = gzencode($content);
    header('Content-Length: ' . strlen($content));
    echo $content;
    exit;
}
header('Content-Length: ' . strlen($content));
header('Content-Type: text/html');
echo $content;
