<?php
/**
 * Usage example for HTTP_Request2 package: uploading a file to rapidshare.com
 *
 * Inspired by Perl usage example: http://images.rapidshare.com/software/rsapi.pl
 * Rapidshare API description: http://rapidshare.com/dev.html
 */

require_once 'HTTP/Request2.php';

// You'll probably want to change this
$filename = '/etc/passwd';

try {
    // First step: get an available upload server
    $request = new HTTP_Request2(
        'http://rapidshare.com/cgi-bin/rsapi.cgi?sub=nextuploadserver_v1'
    );
    $server  = $request->send()->getBody();
    if (!preg_match('/^(\\d+)$/', $server)) {
        throw new Exception("Invalid upload server: {$server}");
    }

    // Calculate file hash, we'll use it later to check upload
    if (false === ($hash = @md5_file($filename))) {
        throw new Exception("Cannot calculate MD5 hash of '{$filename}'");
    }

    // Second step: upload a file to the available server
    $uploader = new HTTP_Request2(
        "http://rs{$server}l3.rapidshare.com/cgi-bin/upload.cgi",
        HTTP_Request2::METHOD_POST
    );
    // Adding the file
    $uploader->addUpload('filecontent', $filename);
    // This will tell server to return program-friendly output
    $uploader->addPostParameter('rsapi_v1', '1');

    $response = $uploader->send()->getBody();
    if (!preg_match_all('/^(File[^=]+)=(.+)$/m', $response, $m, PREG_SET_ORDER)) {
        throw new Exception("Invalid response: {$response}");
    }
    $rspAry = array();
    foreach ($m as $item) {
        $rspAry[$item[1]] = $item[2];
    }
    // Check that uploaded file has the same hash
    if (empty($rspAry['File1.4'])) {
        throw new Exception("MD5 hash data not found in response");
    } elseif ($hash != strtolower($rspAry['File1.4'])) {
        throw new Exception("Upload failed, local MD5 is {$hash}, uploaded MD5 is {$rspAry['File1.4']}");
    }
    echo "Upload succeeded\nDownload link: {$rspAry['File1.1']}\nDelete link: {$rspAry['File1.2']}\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
