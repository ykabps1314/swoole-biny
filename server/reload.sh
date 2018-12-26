echo "Reloading..."
cmd=$(pidof yb_biny_php)

kill -USR1 "$cmd"
echo "Reloaded"