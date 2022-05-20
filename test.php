<?php
require_once('../../config.php');
require_once ($CFG->dirroot.'\repository\s3\S3.php');
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;


$s3 = new S3("foo", "bar", false, "https://localhost:4566");
echo "S3::listBuckets(): ".print_r($s3->listBuckets(), 1)."\n";

echo "oi";
