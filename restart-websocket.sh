# kill old websocket server
PID=$(ps aux | grep WebSocketServer.php  | grep -v grep | awk '{print $2}' | head -n1)

if [ ! -z "$PID" ]
then
    kill $PID
    echo "Process $PID killed"
else
    echo "No process found"
fi

# start new websocket server
cd /var/www/mod/hypercast/websockets
nohup php WebSocketServer.php > websocket.log &
echo "Started WebSocketServer"
