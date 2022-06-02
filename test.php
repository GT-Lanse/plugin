<?php
require_once('../../config.php');
require_once('classes/mad_dashboard.php');

echo "calling";

\block_mad2api\mad_dashboard::enable(2);
