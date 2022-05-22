<?php
require_once('../../config.php');
require_once ($CFG->dirroot.'\repository\s3\S3.php');
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
require_once('classes/mad_dashboard.php');

\block_mad2api\mad_dashboard::enable();
